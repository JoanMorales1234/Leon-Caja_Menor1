<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

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
        set_time_limit(1200);
        require_once ROOT_PATH . '/vendor/autoload.php';

        $cajaId = isset($_GET['caja_id']) ? (int)$_GET['caja_id'] : 0;
        $tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor', 'mayor']) ? $_GET['tipo'] : '';
        $estado = isset($_GET['estado']) && in_array($_GET['estado'], ['abierta', 'cerrada', 'todas']) ? $_GET['estado'] : 'todas';
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
        $desde = $_GET['desde'] ?? '';
        $hasta = $_GET['hasta'] ?? '';
        $ultimos = isset($_GET['ultimos']) ? (int)$_GET['ultimos'] : 0;

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
            if ($estado !== 'todas') { $sql .= ' AND c.estado = ?'; $params[] = $estado; }
            if ($mes > 0) { $sql .= ' AND MONTH(c.fecha_caja) = ?'; $params[] = $mes; }
            if ($anio > 0) { $sql .= ' AND YEAR(c.fecha_caja) = ?'; $params[] = $anio; }
            if ($desde) { $sql .= ' AND c.fecha_caja >= ?'; $params[] = $desde; }
            if ($hasta) { $sql .= ' AND c.fecha_caja <= ?'; $params[] = $hasta; }
            $sql .= ' ORDER BY c.fecha_caja ASC';
            if ($ultimos > 0) { $sql .= ' LIMIT ' . (int)$ultimos; }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $cajas = $stmt->fetchAll();
        }
        if (!$cajas) die('No hay cajas para exportar');

        $mesesEsp = [1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $plantilla = ROOT_PATH . '/documentos/RCM.xls';
        if (!file_exists($plantilla)) die('Plantilla RCM.xls no encontrada');

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
        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $vM = $sheet->getCell("M$r")->getValue();
            $vE = $sheet->getCell("E$r")->getValue();
            if (
                ($vE !== null && str_starts_with(strtoupper(trim($vE)), 'RCM')) ||
                ($vM !== null && stripos(trim($vM), 'LEGALIZACION') !== false)
            ) {
                for ($c = 1; $c <= 19; $c++) {
                    $cl = Coordinate::stringFromColumnIndex($c);
                    $legalData[$c] = $sheet->getCell("$cl$r")->getValue();
                }
                break;
            }
        }

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $sheet->removeRow($headerRow + 1, 1);
        }

        $gastosStmt = $this->pdo->prepare('SELECT g.*, p.nit AS prov_nit
            FROM gastos g
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.orden DESC, g.id ASC');

        $row = $headerRow;
        foreach ($cajas as $caja) {
            $gastosStmt->execute([$caja['id']]);
            $gastos = $gastosStmt->fetchAll();

            $totalDebito = 0;
            foreach ($gastos as $g) {
                $valor = (float)$g['valor'];
                $totalDebito += $valor;
                $row++;
                $sheet->setCellValueExplicit("A$row", '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B$row", $g['prov_nit'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C$row", '001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("K$row", $valor);
                $sheet->setCellValueExplicit("M$row", $g['descripcion'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $row++;
            $legalRow = $row;

            $fo = new \DateTime($caja['fecha_caja']);
            $dia = $fo->format('d');
            $nomMes = strtoupper($mesesEsp[(int)$fo->format('m')]);
            $anioNum = $fo->format('Y');
            $textoLegal = "LEGALIZACION REEMBOLSO DE CAJA " . strtoupper($caja['tipo_caja'])
                . "  $dia  AL $dia  $nomMes  $anioNum";

            if ($row > 1) {
                $sheet->getStyle('B2:B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }

            if (!empty($legalData[1]))  $sheet->setCellValueExplicit("A$legalRow", $legalData[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if (!empty($legalData[2]))  $sheet->setCellValueExplicit("B$legalRow", $legalData[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if (!empty($legalData[5]))  $sheet->setCellValueExplicit("E$legalRow", $legalData[5], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("H$legalRow", 0);
            $sheet->setCellValue("I$legalRow", 0);
            $sheet->setCellValue("J$legalRow", 0);
            $sheet->setCellValue("K$legalRow", 0);
            $sheet->setCellValue("L$legalRow", $totalDebito);
            $sheet->setCellValueExplicit("M$legalRow", $textoLegal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        if ($cajaId) {
            $f = $cajas[0];
            $nombreArchivo = 'syscafe_' . strtoupper($f['tipo_caja']) . '_' . $f['fecha_caja'];
        } else {
            $nombreArchivo = 'syscafe_' . $tipo;
            if ($desde && $hasta) $nombreArchivo .= '_' . $desde . '_' . $hasta;
            elseif ($mes > 0) $nombreArchivo .= '_' . $mesesEsp[$mes];
            elseif ($anio > 0) $nombreArchivo .= '_' . $anio;
            elseif ($ultimos > 0) $nombreArchivo .= '_ultimos_' . $ultimos;
        }

        $tempDir = sys_get_temp_dir() ?: ROOT_PATH;
        $outFile = rtrim($tempDir, DIRECTORY_SEPARATOR) . '/syscafe_' . uniqid('', true) . '.xls';

        $sheet->getAutoFilter()->setRange('A1:S' . $row);

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($outFile);

        while (ob_get_level() > 0) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');
        header('Content-Length: ' . filesize($outFile));
        header('Pragma: no-cache');
        header('Expires: 0');
        @readfile($outFile);
        @unlink($outFile);
        exit;
    }
}
