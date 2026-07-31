<?php
set_time_limit(600);
require_once 'db.php';
require_once 'helpers.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;

$pdo = getDb();

$cajaId = isset($_GET['caja_id']) ? (int)$_GET['caja_id'] : 0;
$tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor','mayor']) ? $_GET['tipo'] : '';
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

if (!$tipo && !$cajaId) die('Debe especificar ?tipo=menor, ?tipo=mayor o ?caja_id=N');

if ($cajaId) {
    $stmt = $pdo->prepare('SELECT c.*,
        IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
        IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
        FROM cajas c WHERE c.id = ?');
    $stmt->execute([$cajaId]);
    $cajas = $stmt->fetchAll();
    if (!$cajas) die('Caja no encontrada');
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
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cajas = $stmt->fetchAll();
}
if (!$cajas) die('No hay cajas para exportar');

$mesesEsp = [1=>'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
$plantilla = __DIR__ . '/RCM.xls';
if (!file_exists($plantilla)) die('Plantilla RCM.xls no encontrada');

$tempDir = __DIR__;
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

    $stmt = $pdo->prepare('SELECT g.*, p.nit AS prov_nit
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

    $fo = new DateTime($caja['fecha_caja']);
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
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
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
