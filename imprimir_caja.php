<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

$cajaId = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
$tipoPrint = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor', 'mayor']) ? $_GET['tipo'] : '';

if ($cajaId) {
    $stmt = $pdo->prepare('SELECT c.*,
        IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
        IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
        FROM cajas c WHERE c.id = ?');
    $stmt->execute([$cajaId]);
    $caja = $stmt->fetch();
} elseif ($tipoPrint) {
    $caja = getCajaDetails($pdo, $tipoPrint);
} else {
    die('Debe especificar ?caja_id=N o ?tipo=menor|mayor');
}

if (!$caja) die('Caja no encontrada');

$gastos = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, ca.nombre AS cargo, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
    FROM gastos g
    LEFT JOIN empleados e ON g.empleado_id = e.id
    LEFT JOIN cargos ca ON e.cargo_id = ca.id
    LEFT JOIN proveedores p ON g.proveedor_id = p.id
    WHERE g.caja_id = ? ORDER BY g.fecha_gasto ASC, g.id ASC');
$gastos->execute([$caja['id']]);
$gastosData = $gastos->fetchAll();

$totalGastos = (float)$caja['total_gastos'];
$totalReintegros = (float)$caja['total_reintegros'];
$valorInicial = (float)$caja['valor_inicial'];

$saldoAnterior = $valorInicial;

$saldoActual = $saldoAnterior + $totalReintegros;
$nuevoSaldo = $saldoActual - $totalGastos;

$tipoMay = strtoupper($caja['tipo_caja']);
$fechaObj = DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
$nomMes = strtoupper($fechaObj->format('F'));
$dia = $fechaObj->format('d');
$anio = $fechaObj->format('Y');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>CAJA <?= $tipoMay ?> — <?= $nomMes ?> <?= $dia ?> <?= $anio ?></title>
    <style>
        @page { margin: 0; size: letter landscape; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 9pt; color: #000; padding: 1.2cm; padding-top: 0.8cm; }
        .header { margin-bottom: 12px; display: flex; align-items: center; gap: 12px;  }
        .header-logo { flex-shrink: 0; }
        .header-logo img { max-width: 200px; max-height: 200px; display: block; }
        .header-text h1 { font-size: 38pt; font-weight: bold; letter-spacing: 2px; margin-bottom: 2px;  position: relative; align-self: flex-start; }
        .header-text h2 { font-size: 12pt; font-weight: normal; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th { border: 1px solid #000; padding: 4px 5px; text-align: center; font-weight: bold; background: #e0e0e0; font-size: 7.5pt; text-transform: uppercase; }
        td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; white-space: nowrap; }
        .num { text-align: right; white-space: nowrap; }
        .fecha { text-align: center; white-space: nowrap; }
        .cedula { text-align: center; white-space: nowrap; }
        .total-row td { font-weight: bold; padding: 4px 5px; }
        .saldo-anterior td { font-weight: bold; background: #D9E2F3; }
        .reintegro-row td { font-weight: bold; background: #E2EFDA; color: #006100; }
        .saldo-actual td { font-weight: bold; background: #D9E2F3; font-size: 10pt; }
        .total-gastos td { font-weight: bold; background: #FCE4D6; }
        .nuevo-saldo td { font-weight: bold; background: #E2EFDA; font-size: 10pt; }
        .footer { margin-top: 30px; text-align: center; font-size: 9pt; }
        .footer .firma { display: inline-block; margin: 0 30px; text-align: center; }
        .footer .firma .linea { border-top: 1px solid #000; width: 180px; margin-top: 40px; padding-top: 6px; }
        @media print {
            body { padding: 1.2cm; padding-top: 0.8cm; }
            .no-print { display: none; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;text-align:center">
        <button onclick="window.print()" style="padding:8px 24px;font-size:14px;cursor:pointer;background:#0d6efd;color:#fff;border:none;border-radius:4px;">&#128424; Imprimir</button>
        <button onclick="window.close()" style="padding:8px 24px;font-size:14px;cursor:pointer;background:#6c757d;color:#fff;border:none;border-radius:4px;margin-left:8px;">Cerrar</button>
    </div>

    <?php $tieneLogo = file_exists(__DIR__ . '/uploads/logo.png'); ?>
    <div class="header">
        <?php if ($tieneLogo): ?>
            <div class="header-logo">
                <img src="uploads/logo.png?t=<?= time() ?>" alt="Logo">
            </div>
        <?php endif; ?>
        <div class="header-text">
            <h1>CAJA <?= $tipoMay ?></h1>
            <h2><?= $nomMes ?> <?= $dia ?> <?= $anio ?></h2>
        </div>
    </div>

    <table>
        <!-- SALDO ANTERIOR (above header) -->
        <tr class="saldo-anterior">
            <td colspan="7" style="text-align:right;">SALDO ANTERIOR</td>
            <td class="num"><?= number_format($saldoAnterior, 2, ',', '.') ?></td>
        </tr>
        <?php if ($totalReintegros > 0): ?>
        <tr class="reintegro-row">
            <td colspan="7" style="text-align:right;">REINTEGRO</td>
            <td class="num"><?= number_format($totalReintegros, 2, ',', '.') ?></td>
        </tr>
        <tr class="saldo-actual">
            <td colspan="7" style="text-align:right;">SALDO ACTUAL</td>
            <td class="num"><?= number_format($saldoActual, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <!-- Header -->
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
        <?php if (empty($gastosData)): ?>
            <tr><td colspan="8" style="text-align:center;font-style:italic;">Sin movimientos</td></tr>
        <?php else:
            foreach ($gastosData as $g):
                $cedula = $g['cedula'] ?? '';
                $nombre = trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? ''));
                $cargo = $g['cargo'] ?? '';
                $desc = $g['descripcion'] ?? '';
                $provNit = $g['proveedor_nit'] ?? '';
                $provNombre = $g['proveedor_nombre'] ?? '';
        ?>
            <tr>
                <td class="fecha"><?= date('j/n/Y', strtotime($g['fecha_gasto'])) ?></td>
                <td class="cedula"><?= htmlspecialchars($cedula) ?></td>
                <td><?= htmlspecialchars($nombre) ?></td>
                <td><?= htmlspecialchars($cargo) ?></td>
                <td><?= htmlspecialchars($desc) ?></td>
                <td class="cedula"><?= htmlspecialchars($provNit) ?></td>
                <td><?= htmlspecialchars($provNombre) ?></td>
                <td class="num">$ <?= number_format($g['valor'], 2, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-gastos">
            <td colspan="7" style="text-align:right;">TOTAL GASTOS</td>
            <td class="num">$ <?= number_format($totalGastos, 2, ',', '.') ?></td>
        </tr>
        <tr class="nuevo-saldo">
            <td colspan="7" style="text-align:right;">NUEVO SALDO</td>
            <td class="num">$ <?= number_format($nuevoSaldo, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="footer">
        <div style="border-top:1px solid #000;margin:20px 0 10px;"></div>
        <div class="firma">
            <div class="linea">FIRMA RESPONSABLE</div>
        </div>
        <div class="firma">
            <div class="linea">FIRMA AUTORIZA</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            var printBtn = document.querySelector('.no-print');
            if (printBtn && window.print) {
                setTimeout(function() { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>
