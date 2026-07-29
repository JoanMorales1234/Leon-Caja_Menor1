<?php
set_time_limit(600);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

$message = '';
$type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $message = 'Error al subir el archivo.';
        $type = 'danger';
    } elseif (!in_array(strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)), ['xls', 'xlsx'])) {
        $message = 'Solo se permiten archivos .xls o .xlsx.';
        $type = 'danger';
    } else {
        $psScriptFile = __DIR__ . '\\temp_import.ps1';
        $jsonOutputFile = __DIR__ . '\\temp_json_output.txt';
        $localExcelFile = __DIR__ . '\\temp_' . uniqid() . '.xlsx';

        @unlink($psScriptFile);
        @unlink($jsonOutputFile);
        if (!move_uploaded_file($archivo['tmp_name'], $localExcelFile)) {
            $message = 'Error al copiar el archivo Excel al servidor.';
            $type = 'danger';
        } else {

        $psCode = <<<'PS'
$ErrorActionPreference = "Stop"
$path = "__TMP_PATH__"
$jsonOut = "__JSON_OUT__"

Get-Process -Name "Excel" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 3

try {
    $xl = New-Object -ComObject Excel.Application
    $xl.Visible = $false
    $xl.DisplayAlerts = $false
    $xl.ScreenUpdating = $false
    $xl.EnableEvents = $false
    $xl.Interactive = $false
    $xl.AskToUpdateLinks = $false

    $wb = $xl.Workbooks.Open($path, $false, $true)

    $sheetCount = $wb.Worksheets.Count
    $readRows = 300; $readCols = 8

    foreach ($ws in $wb.Worksheets) {
        $rows = @()
        $range = $ws.Range($ws.Cells(1,1), $ws.Cells($readRows, $readCols))
        $data = $range.Value2
        if ($data -is [array]) {
            $rCount = [Math]::Min($data.GetLength(0), $readRows)
            $cCount = [Math]::Min($data.GetLength(1), $readCols)
            for ($r = 1; $r -le $rCount; $r++) {
                $rowData = @{}
                for ($c = 1; $c -le $cCount; $c++) {
                    $v = $data[$r, $c]
                    if ($v -eq $null) { $s = "" }
                    elseif ($v -is [DateTime]) { $s = $v.ToString("d/M/yyyy") }
                    else { $s = [string]$v }
                    $rowData["c$c"] = $s
                }
                $rows += $rowData
            }
        }
        $sheetObj = @{ sheetName = $ws.Name; rows = $rows }
        $sheetJson = $sheetObj | ConvertTo-Json -Depth 10 -Compress
        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::AppendAllText($jsonOut, $sheetJson + "`n", $utf8NoBom)
    }

    $wb.Close($false)
    $xl.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) > $null
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()

    Get-Process -Name "Excel" -ErrorAction SilentlyContinue | Stop-Process -Force

    Write-Host "OK,$sheetCount"
} catch {
    try { $wb.Close($false) } catch {}
    try { $xl.Quit() } catch {}
    try { [System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) > $null } catch {}
    try { Get-Process -Name "Excel" -ErrorAction SilentlyContinue | Stop-Process -Force } catch {}
    Write-Host "ERROR: $_"
}
PS;
        $psCode = str_replace('__TMP_PATH__', $localExcelFile, $psCode);
        $psCode = str_replace('__JSON_OUT__', $jsonOutputFile, $psCode);
        file_put_contents($psScriptFile, $psCode);

        $output = shell_exec('powershell -NoProfile -ExecutionPolicy Bypass -File "' . $psScriptFile . '" 2>&1');
        @unlink($psScriptFile);

        if (strpos(trim($output), 'ERROR') === 0) {
            $message = 'Error leyendo Excel: ' . htmlspecialchars($output);
            $type = 'danger';
        } elseif (!file_exists($jsonOutputFile)) {
            $message = 'No se pudo leer el archivo Excel (no se generó JSON).';
            $type = 'danger';
        } else {
            $lines = file($jsonOutputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            @unlink($jsonOutputFile);
            $sheets = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (!$decoded) {
                    $line = ltrim($line, "\xEF\xBB\xBF");
                    $decoded = json_decode($line, true);
                }
                if ($decoded && isset($decoded['sheetName'])) {
                    $sheets[] = $decoded;
                }
            }

            if (empty($sheets)) {
                $message = "Error al interpretar los datos del Excel.";
                $type = 'danger';
            } else {
                $meses = [
                    'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'APRIL' => 4, 'MAY' => 5, 'JUNE' => 6,
                    'JULY' => 7, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OCTOBER' => 10, 'NOVEMBER' => 11, 'DECEMBER' => 12,
                    'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4, 'MAYO' => 5, 'JUNIO' => 6,
                    'JULIO' => 7, 'AGOSTO' => 8, 'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12,
                    'FEBRORO' => 2,
                ];
                function excelSerialToDateStr($val) {
                    if (is_numeric($val) && $val > 40000) {
                        return gmdate('j/n/Y', ($val - 25569) * 86400);
                    }
                    return $val;
                }
                $importados = 0;
                $cachedTipo = '';
                $errores = [];
                $mesFiltro = isset($_POST['mes_filtro']) ? intval($_POST['mes_filtro']) : 0;

                foreach ($sheets as $sheet) {
                    $sheetName = trim(strtoupper($sheet['sheetName']));
                    $rows = $sheet['rows'] ?? [];
                    if (count($rows) < 10) continue;

                    $titleVal = '';
                    $titleRows = [0, 1, 3, 2, 4, 5, 6];
                    foreach ($titleRows as $ti) {
                        if (!isset($rows[$ti])) continue;
                        $v = trim($rows[$ti]['c4'] ?? '');
                        if (stripos($v, 'CAJA MENOR') !== false || stripos($v, 'CAJA MAYOR') !== false) {
                            $titleVal = $v; break;
                        }
                    }
                    if (!$titleVal) {
                        foreach ($rows as $r) {
                            $v = trim($r['c4'] ?? '');
                            if (stripos($v, 'CAJA MENOR') !== false || stripos($v, 'CAJA MAYOR') !== false) {
                                $titleVal = $v; break;
                            }
                        }
                    }
                    $tipoCaja = '';
                    if (stripos($titleVal, 'CAJA MENOR') !== false) $tipoCaja = 'menor';
                    elseif (stripos($titleVal, 'CAJA MAYOR') !== false) $tipoCaja = 'mayor';
                    if (!$tipoCaja) {
                        if ($cachedTipo) {
                            $tipoCaja = $cachedTipo;
                        } else {
                            for ($hi = 6; $hi <= 8; $hi++) {
                                if (!isset($rows[$hi])) continue;
                                $hText = implode(' ', $rows[$hi]);
                                if (preg_match('/FECHA|CEDULA|NIT|MENOR/', $hText)) {
                                    $tipoCaja = 'menor';
                                    $cachedTipo = 'menor';
                                    break;
                                }
                            }
                            if (!$tipoCaja) continue;
                        }
                    } else {
                        $cachedTipo = $tipoCaja;
                    }

                    $parts = explode(' ', $sheetName);
                    $dia = 0; $mes = 0; $anio = 0;
                    foreach ($parts as $p) {
                        $pNum = (int)$p;
                        if ($pNum > 1900 && $pNum < 2100) $anio = $pNum;
                        elseif ($pNum >= 1 && $pNum <= 31) $dia = $pNum;
                    }
                    foreach ($parts as $p) {
                        $pU = strtoupper($p);
                        if (isset($meses[$pU])) { $mes = $meses[$pU]; break; }
                    }
                    if (!$dia || !$mes || !$anio) { $errores[] = "Hoja '$sheetName': fecha inválida"; continue; }
                    $fechaCaja = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

                    if ($mesFiltro > 0 && $mes !== $mesFiltro) { continue; }

                    $stmt = $pdo->prepare('SELECT id FROM cajas WHERE fecha_caja = ? AND tipo_caja = ?');
                    $stmt->execute([$fechaCaja, $tipoCaja]);
                    if ($stmt->fetch()) { $errores[] = "Hoja '$sheetName': ya existe caja $tipoCaja para $fechaCaja"; continue; }

                    $valorInicial = 0;
                    foreach ($rows as $r) {
                        $encontro = false;
                        foreach (['c1','c2','c3','c4','c5'] as $ck) {
                            $cv = trim($r[$ck] ?? '');
                            if (stripos($cv, 'SALDO') !== false) { $encontro = true; break; }
                        }
                        if ($encontro) {
                            foreach (['c8','c7','c6'] as $vk) {
                                $vv = trim($r[$vk] ?? '');
                                if ($vv !== '') {
                                    $valorInicial = normalizeNumber($vv);
                                    break 2;
                                }
                            }
                        }
                    }
                    if ($valorInicial == 0) {
                        foreach ($rows as $rIdx => $r) {
                            if ($rIdx < 8 || $rIdx > 12) continue;
                            $f = trim($r['c1'] ?? '');
                            $v = trim($r['c8'] ?? '');
                            if ($v !== '' && $f === '' && is_numeric(str_replace(['.', ','], '', $v))) {
                                $valorInicial = normalizeNumber($v);
                                break;
                            }
                        }
                    }

                    $gastosData = [];
                    $reintegrosData = [];
                    $reintegroResumen = 0;
                    foreach ($rows as $rIdx => $r) {
                        if ($rIdx < 8 || $rIdx > 12) continue;
                        $reintTexto = '';
                        foreach (['c1','c2','c3','c4','c5','c6','c7'] as $ck) {
                            $cv = trim($r[$ck] ?? '');
                            if (stripos($cv, 'REINTEGRO') !== false) { $reintTexto = $cv; break; }
                        }
                        if ($reintTexto !== '') {
                            foreach (['c8','c7','c6'] as $vk) {
                                $vv = trim($r[$vk] ?? '');
                                if ($vv !== '' && is_numeric(str_replace(['.', ','], '', $vv))) {
                                    $reintegroResumen = normalizeNumber($vv);
                                    break 2;
                                }
                            }
                        }
                    }
                    foreach ($rows as $rIdx => $row) {
                        if ($rIdx < 11) continue;
                        $filaFecha = trim($row['c1'] ?? '');
                        $filaFecha = excelSerialToDateStr($filaFecha);
                        $desc = trim($row['c5'] ?? '');
                        $valorStr = trim($row['c8'] ?? '');
                        if ($filaFecha === '' && $desc === '' && $valorStr === '') continue;
                        if (preg_match('/^(TOTAL|SALDO|NUEVO)/i', $filaFecha)) continue;
                        if (stripos($desc, 'SALDO') !== false) continue;

                        $cedula = trim($row['c2'] ?? '');
                        $nombre = trim($row['c3'] ?? '');
                        $cargo = trim($row['c4'] ?? '');
                        $detalle = $desc;
                        $provNit = trim($row['c6'] ?? '');
                        $provNombre = trim($row['c7'] ?? '');
                        $valor = normalizeNumber($valorStr);
                        if ($valor <= 0) continue;

                        $esReintegro = (stripos($cedula, 'REINTEGRO') !== false)
                            || (stripos($desc, 'REINTEGRO') !== false)
                            || (stripos($nombre, 'REINTEGRO') !== false)
                            || (stripos($provNombre, 'REINTEGRO') !== false);

                        if ($esReintegro) {
                            $reintegrosData[] = [
                                'fecha' => $filaFecha ?: $fechaCaja, 'detalle' => $detalle, 'valor' => $valor,
                            ];
                            continue;
                        }

                        if (!preg_match('#\d{1,2}/\d{1,2}/\d{2,4}#', $filaFecha)) continue;

                        $gastosData[] = [
                            'fecha' => $filaFecha, 'cedula' => $cedula, 'nombre' => $nombre,
                            'cargo' => $cargo, 'detalle' => $detalle,
                            'prov_nit' => $provNit, 'prov_nombre' => $provNombre, 'valor' => $valor,
                        ];
                    }

                    if ($reintegroResumen > 0 && empty($reintegrosData)) {
                        $reintegrosData[] = ['fecha' => $fechaCaja, 'detalle' => 'REINTEGRO', 'valor' => $reintegroResumen];
                    }

                    if (empty($gastosData) && empty($reintegrosData)) {
                        $errores[] = "Hoja '$sheetName': no se encontraron gastos ni reintegros válidos"; continue;
                    }

                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare('INSERT INTO cajas (tipo_caja, fecha_caja, valor_inicial, estado, fecha_cierre) VALUES (?, ?, ?, "cerrada", NOW())');
                        $stmt->execute([$tipoCaja, $fechaCaja, $valorInicial]);
                        $cajaId = (int)$pdo->lastInsertId();

                        foreach ($gastosData as $g) {
                            $empleadoId = null;
                            if ($g['cedula']) {
                                $stmt = $pdo->prepare('SELECT id FROM empleados WHERE cedula = ?');
                                $stmt->execute([$g['cedula']]);
                                $emp = $stmt->fetch();
                                if ($emp) {
                                    $empleadoId = $emp['id'];
                                } else {
                                    $parts = explode(' ', $g['nombre'], 2);
                                    $nombres = $parts[0] ?? '';
                                    $apellidos = $parts[1] ?? '';
                                    $cargoId = null;
                                    if ($g['cargo']) {
                                        $stmt = $pdo->prepare('SELECT id FROM cargos WHERE nombre = ?');
                                        $stmt->execute([$g['cargo']]);
                                        $cargoRow = $stmt->fetch();
                                        if ($cargoRow) {
                                            $cargoId = $cargoRow['id'];
                                        } else {
                                            $stmt = $pdo->prepare('INSERT INTO cargos (nombre) VALUES (?)');
                                            $stmt->execute([$g['cargo']]);
                                            $cargoId = (int)$pdo->lastInsertId();
                                        }
                                    }
                                    $stmt = $pdo->prepare('INSERT INTO empleados (cedula, nombres, apellidos, cargo_id) VALUES (?, ?, ?, ?)');
                                    $stmt->execute([$g['cedula'], $nombres, $apellidos, $cargoId]);
                                    $empleadoId = (int)$pdo->lastInsertId();
                                }
                            }

                            $proveedorId = null;
                            if ($g['prov_nit']) {
                                $nitLimpio = strtoupper(trim($g['prov_nit']));
                                $stmt = $pdo->prepare('SELECT id FROM proveedores WHERE nit = ?');
                                $stmt->execute([$nitLimpio]);
                                $prov = $stmt->fetch();
                                if ($prov) {
                                    $proveedorId = $prov['id'];
                                } else {
                                    $stmt = $pdo->prepare('INSERT INTO proveedores (nit, nombre) VALUES (?, ?)');
                                    $stmt->execute([$nitLimpio, $g['prov_nombre']]);
                                    $proveedorId = (int)$pdo->lastInsertId();
                                }
                            }

                            $fechaGastoObj = DateTime::createFromFormat('j/n/Y', $g['fecha']);
                            if (!$fechaGastoObj) $fechaGastoObj = DateTime::createFromFormat('n/j/Y', $g['fecha']);
                            if (!$fechaGastoObj) $fechaGastoObj = new DateTime($g['fecha']);
                            $fechaGastoSQL = $fechaGastoObj ? $fechaGastoObj->format('Y-m-d') : $fechaCaja;

                            $stmt = $pdo->prepare('INSERT INTO gastos (caja_id, empleado_id, proveedor_id, valor, descripcion, fecha_gasto, soporte, creado_en) VALUES (?, ?, ?, ?, ?, ?, "", NOW())');
                            $stmt->execute([$cajaId, $empleadoId, $proveedorId, $g['valor'], $g['detalle'], $fechaGastoSQL]);
                        }

                        foreach ($reintegrosData as $r) {
                            $fechaObj = DateTime::createFromFormat('j/n/Y', $r['fecha']);
                            if (!$fechaObj) $fechaObj = DateTime::createFromFormat('n/j/Y', $r['fecha']);
                            if (!$fechaObj) $fechaObj = new DateTime($r['fecha']);
                            $fechaSQL = $fechaObj ? $fechaObj->format('Y-m-d') : $fechaCaja;

                            $stmt = $pdo->prepare('INSERT INTO reintegros (caja_id, valor, descripcion, fecha_reintegro, creado_en) VALUES (?, ?, ?, ?, NOW())');
                            $stmt->execute([$cajaId, $r['valor'], $r['detalle'], $fechaSQL]);
                        }

                        recalculateCajaFinal($pdo, $cajaId);
                        $pdo->commit();
                        $importados++;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errores[] = "Hoja '$sheetName': " . $e->getMessage();
                    }
                }

                $totalHojas = count($sheets);
                if ($importados > 0) {
                    $message = "Importación completada: $importados de $totalHojas hoja(s) importada(s).";
                    $type = 'success';
                } else {
                    $message = "No se importó ninguna caja (de $totalHojas hoja(s) encontradas).";
                    $type = 'warning';
                }
                if ($errores) {
                    $message .= ' | ' . implode(' | ', array_slice($errores, 0, 10));
                    if ($importados == 0) $type = 'danger';
                }
            }
            @unlink($localExcelFile);
        }
    }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importar Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index_nuevo.php"><i class="bi bi-cash-stack"></i> Caja Menor / Mayor</a>
        <a class="btn btn-outline-light btn-sm" href="index_nuevo.php"><i class="bi bi-house"></i> Inicio</a>
    </div>
</nav>
<div class="container py-4">
    <h1 class="h3 mb-3"><i class="bi bi-file-earmark-excel"></i> Importar desde Excel</h1>
    <?php if ($message): ?>
        <?= flash($message, $type) ?>
    <?php endif; ?>
            <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Selecciona un archivo .xls o .xlsx con el formato de CAJA MENOR/MAYOR (una hoja por día).</p>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivo Excel</label>
                    <input class="form-control" type="file" id="archivo" name="archivo" accept=".xls,.xlsx" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Filtrar por mes</label>
                    <select class="form-select" name="mes_filtro">
                        <option value="0" selected>Todos los meses</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                    <small class="form-text text-muted">Si seleccionas un mes, solo se importarán las hojas de ese mes. Si ya existe una caja para una fecha, se salta automáticamente.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Importar</button>
                <a href="index_nuevo.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white"><h5 class="mb-0">Formato esperado</h5></div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Una hoja por día, nombre de hoja = fecha (ej: "MAYO 02 2026")</li>
                <li>Fila 4: "CAJA MENOR" o "CAJA MAYOR" (columna D)</li>
                <li>Fila 7: Encabezados</li>
                <li>Filas 12+: Datos con fecha, cédula, nombre, funcionario, descripción, NIT, proveedor, valor</li>
                <li>Empleados y proveedores nuevos se crean automáticamente</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>