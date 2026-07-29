<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

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
    $stmt = $pdo->prepare('SELECT c.*,
        IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
        IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
        FROM cajas c WHERE c.id = ?');
    $stmt->execute([$cajaId]);
    $cajas = $stmt->fetchAll();
    if (!$cajas) {
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
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cajas = $stmt->fetchAll();
    $skipFuncionarios = false;
}

foreach ($cajas as $i => $caja) {
    $cajas[$i]['valor_final'] = (float)$caja['valor_inicial'] + (float)$caja['total_reintegros'] - (float)$caja['total_gastos'];
}

function getMovs($pdo, $cajaId, $tipoMov) {
    if ($tipoMov === 'gastos') {
        $stmt = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, ca.nombre AS cargo, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
            FROM gastos g
            LEFT JOIN empleados e ON g.empleado_id = e.id
            LEFT JOIN cargos ca ON e.cargo_id = ca.id
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.creado_en ASC');
    } else {
        $stmt = $pdo->prepare('SELECT r.* FROM reintegros r WHERE r.caja_id = ? ORDER BY r.creado_en ASC');
    }
    $stmt->execute([$cajaId]);
    return $stmt->fetchAll();
}

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
?>
<html xml:lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Calibri, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #999; padding: 4px 6px; font-size: 10pt; vertical-align: top; }
        th { background: #4472C4; color: #fff; font-weight: bold; text-align: center; }
        .title-row td { font-size: 28pt; font-weight: bold; text-align: center; border: none; padding: 8px; }
        .section-title td { font-size: 12pt; font-weight: bold; text-align: left; border: none; padding: 6px 0; }
        .summary-reintegro td { font-weight: bold; background: #E2EFDA; }
        .summary-saldo-actual td { font-weight: bold; background: #D9E2F3; font-size: 12pt; }
        .saldo-anterior td { font-weight: bold; background: #D9E2F3; }
        .total-gastos td { font-weight: bold; background: #FCE4D6; }
        .nuevo-saldo td { font-weight: bold; background: #E2EFDA; }
        .funcionarios th { background: #70AD47; }
        .page-break { page-break-before: always; }
        .valor-col { text-align: right; white-space: nowrap; }
        .fecha-col { text-align: center; white-space: nowrap; }
        .cedula-col { text-align: center; }
        .no-data td { text-align: center; color: #999; font-style: italic; }
    </style>
</head>
<body>
    <?php foreach ($cajas as $cajaIdx => $caja):
        $gastos = getMovs($pdo, $caja['id'], 'gastos');
        $reintegros = getMovs($pdo, $caja['id'], 'reintegros');
        $tieneReintegros = !empty($reintegros);
        $movs = $gastos;
        $totalGastos = (float)$caja['total_gastos'];
        $totalReintegros = (float)$caja['total_reintegros'];
        $saldoAnterior = 0;
        if ($cajaIdx + 1 < count($cajas)) {
            $prev = $cajas[$cajaIdx + 1];
            $saldoAnterior = (float)$prev['valor_inicial'] + (float)$prev['total_reintegros'] - (float)$prev['total_gastos'];
        } else {
            $saldoAnterior = (float)$caja['valor_inicial'];
        }
        $nuevoSaldo = (float)$caja['valor_final'];
        if ($cajaIdx > 0) { echo '<div class="page-break"></div>'; }
        $fechaObj = DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
        $nombreMes = strtoupper($fechaObj->format('F'));
        $dia = $fechaObj->format('d');
        $anio = $fechaObj->format('Y');
        $mesNum = $fechaObj->format('m');
    ?>
    <table>
        <?php if (file_exists(__DIR__ . '/uploads/logo.png')): ?>
        <tr>
            <td colspan="8" style="text-align:center;border:none;padding:4px;">
                <img src="uploads/logo.png?t=<?= time() ?>" alt="Logo" style="max-width:300px;max-height:80px;">
            </td>
        </tr>
        <?php endif; ?>
        <tr class="title-row">
            <td colspan="8">CAJA <?= $nombreTipoMay ?></td>
        </tr>
        <tr class="section-title">
            <td colspan="8"><?= $nombreMes ?> <?= $dia ?> <?= $anio ?></td>
        </tr>
        <tr>
            <th>FECHA</th>
            <th>CEDULA(NIT)</th>
            <th>NOMBRE</th>
            <th>FUNCIONARIO</th>
            <th>DESCRIPCION O DETALLE</th>
            <th>NIT</th>
            <th>PROVEEDOR</th>
            <th>VALOR</th>
        </tr>
        <tr class="saldo-anterior">
            <td colspan="7" style="text-align:right">SALDO ANTERIOR</td>
            <td class="valor-col"><?= number_format($saldoAnterior, 2, ',', '.') ?></td>
        </tr>
        <?php if ($totalReintegros > 0): ?>
        <tr class="summary-reintegro">
            <td colspan="7" style="text-align:right">REINTEGRO</td>
            <td class="valor-col" style="color:#006100"><?= number_format($totalReintegros, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($totalReintegros > 0): ?>
        <tr class="summary-saldo-actual">
            <td colspan="7" style="text-align:right">SALDO ACTUAL</td>
            <td class="valor-col"><?= number_format($saldoAnterior + $totalReintegros, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (empty($movs)): ?>
            <tr class="no-data">
                <td colspan="8">No hay movimientos registrados</td>
            </tr>
        <?php else:
            foreach ($movs as $g):
                $cedula = $g['cedula'] ?? '';
                $nombre = htmlspecialchars(trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')));
                $cargo = htmlspecialchars($g['cargo'] ?? '');
                $desc = htmlspecialchars($g['descripcion'] ?? '');
                $provNit = htmlspecialchars($g['proveedor_nit'] ?? '');
                $provNombre = htmlspecialchars($g['proveedor_nombre'] ?? '');
        ?>
            <tr>
                <td class="fecha-col"><?= date('j/n/Y', strtotime($g['fecha_gasto'] ?? $caja['fecha_caja'])) ?></td>
                <td class="cedula-col"><?= $cedula ?></td>
                <td><?= $nombre ?></td>
                <td><?= $cargo ?></td>
                <td><?= $desc ?></td>
                <td class="cedula-col"><?= $provNit ?></td>
                <td><?= $provNombre ?></td>
                <td class="valor-col">$ <?= number_format($g['valor'], 2, ',', '.') ?></td>
            </tr>
        <?php endforeach;
        endif; ?>
        <?php if ($totalGastos > 0 || $totalReintegros > 0): ?>
        <tr class="total-gastos">
            <td colspan="7" style="text-align:right">TOTAL GASTOS</td>
            <td class="valor-col">$ <?= number_format($totalGastos, 2, ',', '.') ?></td>
        </tr>

        <tr class="nuevo-saldo">
            <td colspan="7" style="text-align:right">NUEVO SALDO</td>
            <td class="valor-col">$ <?= number_format($nuevoSaldo, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php endforeach; ?>
</body>
</html>
