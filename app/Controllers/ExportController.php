<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;

class ExportController extends Controller
{
    public function excel()
    {
        $tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor', 'mayor']) ? $_GET['tipo'] : '';
        $estado = isset($_GET['estado']) && in_array($_GET['estado'], ['abierta', 'cerrada', 'todas']) ? $_GET['estado'] : 'todas';
        $cajaId = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;

        $mes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
        $anio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;
        $desde = isset($_GET['desde']) ? $_GET['desde'] : '';
        $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';
        $ultimos = isset($_GET['ultimos']) ? intval($_GET['ultimos']) : 0;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $porPagina = 15;

        if (!$tipo && !$cajaId) {
            die('Debe especificar ?tipo=menor o ?tipo=mayor, o ?caja_id=N');
        }

        if ($cajaId) {
            $cajas = [Caja::findById($this->pdo, $cajaId)];
            if (!$cajas[0]) {
                die('Caja no encontrada');
            }
            $skipFuncionarios = true;
        } else {
            $sql = 'SELECT c.*,
                IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
                IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
                FROM cajas c WHERE c.tipo_caja = ?';
            $params = [$tipo];
            if ($estado !== 'todas') {
                $sql .= ' AND c.estado = ?';
                $params[] = $estado;
            }
            if ($mes > 0) {
                $sql .= ' AND MONTH(c.fecha_caja) = ?';
                $params[] = $mes;
            }
            if ($anio > 0) {
                $sql .= ' AND YEAR(c.fecha_caja) = ?';
                $params[] = $anio;
            }
            if ($desde && $hasta) {
                $sql .= ' AND c.fecha_caja BETWEEN ? AND ?';
                $params[] = $desde;
                $params[] = $hasta;
            } elseif ($desde) {
                $sql .= ' AND c.fecha_caja >= ?';
                $params[] = $desde;
            } elseif ($hasta) {
                $sql .= ' AND c.fecha_caja <= ?';
                $params[] = $hasta;
            }
            if ($ultimos > 0) {
                $fechaLimite = date('Y-m-d', strtotime("-{$ultimos} days"));
                $sql .= ' AND c.fecha_caja >= ?';
                $params[] = $fechaLimite;
            }
            $sql .= ' ORDER BY c.fecha_caja DESC';
            if ($ultimos <= 0) {
                $offset = ($pagina - 1) * $porPagina;
                $sql .= ' LIMIT ' . (int)$porPagina . ' OFFSET ' . (int)$offset;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $cajas = $stmt->fetchAll();
            $skipFuncionarios = false;
        }

        foreach ($cajas as $i => $caja) {
            $cajas[$i]['valor_final'] = (float)$caja['valor_inicial'] + (float)$caja['total_reintegros'] - (float)$caja['total_gastos'];
        }

        $getMovs = function ($cajaId, $tipoMov) {
            if ($tipoMov === 'gastos') {
                $stmt = $this->pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, ca.nombre AS cargo, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
                    FROM gastos g
                    LEFT JOIN empleados e ON g.empleado_id = e.id
                    LEFT JOIN cargos ca ON e.cargo_id = ca.id
                    LEFT JOIN proveedores p ON g.proveedor_id = p.id
                    WHERE g.caja_id = ? ORDER BY g.orden DESC, g.id ASC');
            } else {
                $stmt = $this->pdo->prepare('SELECT r.* FROM reintegros r WHERE r.caja_id = ? ORDER BY r.creado_en ASC');
            }
            $stmt->execute([$cajaId]);
            return $stmt->fetchAll();
        };

        $nombreTipo = $cajaId ? ucfirst($cajas[0]['tipo_caja']) : ucfirst($tipo);
        $nombreTipoMay = $cajaId ? strtoupper($cajas[0]['tipo_caja']) : strtoupper($tipo);
        if ($cajaId) {
            $fechaCaja = $cajas[0]['fecha_caja'];
            $nombreArchivo = 'CAJA_' . $nombreTipoMay . '_' . $fechaCaja;
        } else {
            $fechaDesde = $desde ?: ($ultimos > 0 ? date('Y-m-d', strtotime("-{$ultimos} days")) : '');
            $fechaHasta = $hasta ?: date('Y-m-d');
            $nombreArchivo = 'CAJA_' . $nombreTipoMay;
            if ($fechaDesde && $fechaHasta) {
                $nombreArchivo .= '_' . $fechaDesde . '_' . $fechaHasta;
            } else {
                $nombreArchivo .= '_' . $fechaHasta;
            }
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $this->view('exportar_excel', compact('cajas', 'getMovs', 'nombreTipo', 'nombreTipoMay', 'cajaId', 'skipFuncionarios', 'tipo', 'desde', 'hasta', 'ultimos'));
        exit;
    }

    public function syscafe()
    {
        set_time_limit(600);
        require_once ROOT_PATH . '/vendor/autoload.php';

        $cajaId = isset($_GET['caja_id']) ? (int)$_GET['caja_id'] : 0;
        $tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor', 'mayor']) ? $_GET['tipo'] : '';
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';

        if (!$tipo && !$cajaId) die('Debe especificar ?tipo=menor, ?tipo=mayor o ?caja_id=N');

        if ($cajaId) {
            $cajas = [Caja::findById($this->pdo, $cajaId)];
            if (!$cajas[0]) die('Caja no encontrada');
        } else {
            $sql = 'SELECT c.*,
                IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
                IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
                FROM cajas c WHERE c.tipo_caja = ?';
            $params = [$tipo];
            if ($mes > 0) { $sql .= ' AND MONTH(c.fecha_caja) = ?'; $params[] = $mes; }
            if ($anio > 0) { $sql .= ' AND YEAR(c.fecha_caja) = ?'; $params[] = $anio; }
            if ($desde) { $sql .= ' AND c.fecha_caja >= ?'; $params[] = $desde; }
            if ($hasta) { $sql .= ' AND c.fecha_caja <= ?'; $params[] = $hasta; }
            $sql .= ' ORDER BY c.fecha_caja ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $cajas = $stmt->fetchAll();
        }
        if (!$cajas) die('No hay cajas para exportar');

        $mesesEsp = [1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $plantilla = ROOT_PATH . '/documentos/RCM.xls';
        if (!file_exists($plantilla)) die('Plantilla RCM.xls no encontrada');

        $tempDir = ROOT_PATH;
        $archivos = [];

        foreach ($cajas as $caja) {
            $reader = IOFactory::createReader('Xls');
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($plantilla);
            $sheet = $spreadsheet->getActiveSheet();
            $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            if ($highestColIdx > 19) {
                $sheet->removeColumn('T', $highestColIdx - 19);
            }

            $highestRow = $sheet->getHighestRow();

            $headerRow = 0;
            for ($r = 1; $r <= $highestRow; $r++) {
                $v = $sheet->getCell("A$r")->getValue();
                if ($v !== null && strtolower(trim($v)) === 'codpuc') { $headerRow = $r; break; }
            }
            if (!$headerRow) $headerRow = 1;

            $legalData = [];
            $foundLegal = false;
            for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                $vM = $sheet->getCell("M$r")->getValue();
                $vE = $sheet->getCell("E$r")->getValue();
                if (
                    ($vE !== null && str_starts_with(strtoupper(trim($vE)), 'RCM')) ||
                    ($vM !== null && stripos(trim($vM), 'LEGALIZACION') !== false)
                ) {
                    if (!$foundLegal) {
                        for ($c = 1; $c <= 19; $c++) {
                            $cl = Coordinate::stringFromColumnIndex($c);
                            $legalData[$c] = $sheet->getCell("$cl$r")->getValue();
                        }
                        $foundLegal = true;
                    }
                }
            }

            for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                $sheet->removeRow($headerRow + 1, 1);
            }

            $legalRow = $headerRow + 1;

            $stmt = $this->pdo->prepare('SELECT g.*, p.nit AS prov_nit
                FROM gastos g
                LEFT JOIN proveedores p ON g.proveedor_id = p.id
                WHERE g.caja_id = ? ORDER BY g.orden DESC, g.id ASC');
            $stmt->execute([$caja['id']]);
            $gastos = $stmt->fetchAll();

            $totalDebito = 0;
            $gastosData = [];
            foreach ($gastos as $g) {
                $valor = (float)$g['valor'];
                $totalDebito += $valor;
                $gastosData[] = [
                    'tercero' => $g['prov_nit'] ?? '',
                    'debito'  => $valor,
                    'detalle' => $g['descripcion'] ?? ''
                ];
            }

            if (!empty($gastosData)) {
                $sheet->insertNewRowBefore($legalRow, count($gastosData));
                for ($i = 0; $i < count($gastosData); $i++) {
                    $rn = $headerRow + 1 + $i;
                    $g = $gastosData[$i];
                    $sheet->setCellValueExplicit("A$rn", '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B$rn", $g['tercero'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("C$rn", '001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("K$rn", $g['debito']);
                    $sheet->setCellValueExplicit("M$rn", $g['detalle'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $legalRow = $headerRow + 1 + count($gastosData);
            }

            $fo = new \DateTime($caja['fecha_caja']);
            $dia = $fo->format('d');
            $nomMes = strtoupper($mesesEsp[(int)$fo->format('m')]);
            $anioNum = $fo->format('Y');
            $textoLegal = "LEGALIZACION REEMBOLSO DE CAJA " . strtoupper($caja['tipo_caja'])
                . "  $dia  AL $dia  $nomMes  $anioNum";

            if (!empty($legalData[1]))  $sheet->setCellValueExplicit("A$legalRow", $legalData[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if (!empty($legalData[2]))  $sheet->setCellValueExplicit("B$legalRow", $legalData[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if (!empty($legalData[5]))  $sheet->setCellValueExplicit("E$legalRow", $legalData[5], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("H$legalRow", 0);
            $sheet->setCellValue("I$legalRow", 0);
            $sheet->setCellValue("J$legalRow", 0);
            $sheet->setCellValue("K$legalRow", 0);
            $sheet->setCellValue("L$legalRow", $totalDebito);
            $sheet->setCellValueExplicit("M$legalRow", $textoLegal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $out = "$tempDir/syscafe_{$caja['id']}.xls";
            $writer = new XlsWriter($spreadsheet);
            $writer->save($out);
            $archivos[] = $out;
        }

        if (count($archivos) === 1) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . basename($archivos[0]) . '"');
            header('Content-Length: ' . filesize($archivos[0]));
            readfile($archivos[0]);
            @unlink($archivos[0]);
            exit;
        }

        $zipFile = "$tempDir/syscafe_cajas.zip";
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            foreach ($archivos as $f) { $zip->addFile($f, basename($f)); }
            $zip->close();
            foreach ($archivos as $f) @unlink($f);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="syscafe_cajas.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            @unlink($zipFile);
            exit;
        }

        foreach ($archivos as $f) @unlink($f);
        die('Error al generar archivos');
    }
}
