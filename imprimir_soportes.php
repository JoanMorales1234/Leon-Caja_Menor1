<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

$cajaId = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
if (!$cajaId) die('Debe especificar ?caja_id=N');

$caja = $pdo->prepare('SELECT * FROM cajas WHERE id = ?');
$caja->execute([$cajaId]);
$caja = $caja->fetch();
if (!$caja) die('Caja no encontrada');

$gastos = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula
    FROM gastos g
    LEFT JOIN empleados e ON g.empleado_id = e.id
    WHERE g.caja_id = ? ORDER BY g.fecha_gasto ASC, g.id ASC');
$gastos->execute([$cajaId]);
$todosGastos = $gastos->fetchAll();

$tipoMay = strtoupper($caja['tipo_caja']);
$fechaObj = DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
$mesEsp = [
    'January' => 'ENERO', 'February' => 'FEBRERO', 'March' => 'MARZO',
    'April' => 'ABRIL', 'May' => 'MAYO', 'June' => 'JUNIO',
    'July' => 'JULIO', 'August' => 'AGOSTO', 'September' => 'SEPTIEMBRE',
    'October' => 'OCTUBRE', 'November' => 'NOVIEMBRE', 'December' => 'DICIEMBRE'
];
$nomMes = $mesEsp[$fechaObj->format('F')] ?? strtoupper($fechaObj->format('F'));
$dia = $fechaObj->format('d');
$anio = $fechaObj->format('Y');
$fechaCajaStr = "$dia DE $nomMes $anio";

$orientacion = isset($_GET['orientacion']) && $_GET['orientacion'] === 'horizontal' ? 'horizontal' : 'vertical';
$pageSize = $orientacion === 'horizontal' ? 'letter landscape' : 'letter portrait';
$itemsPorPagina = $orientacion === 'horizontal' ? 3 : 2;
$maxImgHeight = $orientacion === 'horizontal' ? '150px' : '350px';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>CAJA <?= $tipoMay ?> — <?= $fechaCajaStr ?> — SOPORTES</title>
    <style>
        @page { margin: 0; size: <?= $pageSize ?>; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 10pt; color: #000; padding: 0.6cm; }
        .no-print { text-align: center; margin-bottom: 10px; }
        .no-print button { padding: 8px 24px; font-size: 14px; cursor: pointer; border: none; border-radius: 4px; }
        .page { page-break-after: always; width: 100%; padding: 4px 8px; }
        .page:last-child { page-break-after: auto; }
        .page-header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 6px; }
        .page-header-logo { flex-shrink: 0; }
        .page-header-logo img { max-width: 80px; max-height: 40px; display: block; }
        .page-header-text { }
        .page-header h2 { font-size: 13pt; font-weight: bold; letter-spacing: 1px; }
        .page-header h3 { font-size: 10pt; font-weight: normal; }
        .soporte-item { border: 1px solid #000; padding: 6px 8px; margin-bottom: 6px; page-break-inside: avoid; }
        .soporte-item .info-row { display: flex; justify-content: space-between; font-size: 8pt; margin-bottom: 2px; }
        .soporte-item .desc { font-size: 8pt; margin: 2px 0; }
        .soporte-item .foto-wrap { text-align: center; margin: 4px 0; max-height: <?= $maxImgHeight ?>; overflow: hidden; }
        .soporte-item .foto-wrap img { max-width: 100%; max-height: <?= $maxImgHeight ?>; object-fit: contain; display: block; margin: 0 auto; }
        .soporte-item .valor { font-size: 9pt; font-weight: bold; text-align: right; }
        .soporte-item .soporte-ref { text-align: center; font-size: 11pt; color: #333; padding: 10px 0; }
        .pagina-num { text-align: center; margin-top: 4px; font-size: 8pt; color: #666; }
        @media screen {
            .page { min-height: 90vh; border: 1px solid #ccc; margin-bottom: 10px; border-radius: 4px; }
        }
        @media print {
            body { padding: 0.6cm; }
            .no-print { display: none; }
            .page { border: none; margin: 0; min-height: 0; padding: 4px 8px; }
            .soporte-item { margin-bottom: 4px; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-size:14px;cursor:pointer;background:#0d6efd;color:#fff;border:none;border-radius:4px;">&#128424; Imprimir</button>
        <a href="?caja_id=<?= $cajaId ?>&orientacion=<?= $orientacion === 'horizontal' ? 'vertical' : 'horizontal' ?>" style="padding:8px 20px;font-size:14px;cursor:pointer;background:#198754;color:#fff;border:none;border-radius:4px;text-decoration:none;display:inline-block;">
            Ver <?= $orientacion === 'horizontal' ? 'Vertical' : 'Horizontal' ?>
        </a>
        <button onclick="window.close()" style="padding:8px 20px;font-size:14px;cursor:pointer;background:#6c757d;color:#fff;border:none;border-radius:4px;">Cerrar</button>
    </div>

    <?php if (empty($todosGastos)): ?>
    <div class="page" style="display:flex;align-items:center;justify-content:center;">
        <p style="font-size:16pt;color:#999;">No hay soportes registrados para esta caja.</p>
    </div>
    <?php else:
        $chunks = array_chunk($todosGastos, $itemsPorPagina);
        $pagina = 1;
        foreach ($chunks as $chunk):
    ?>
    <div class="page">
        <div class="page-header">
            <?php if (file_exists(__DIR__ . '/uploads/logo.png')): ?>
                <div class="page-header-logo">
                    <img src="uploads/logo.png?t=<?= time() ?>" alt="Logo">
                </div>
            <?php endif; ?>
            <div class="page-header-text">
                <h2>CAJA <?= $tipoMay ?> — <?= $fechaCajaStr ?></h2>
                <h3>SOPORTES</h3>
            </div>
        </div>

        <div class="soporte-grid">
            <?php foreach ($chunk as $g):
                $nombreEmp = trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? ''));
            ?>
            <div class="soporte-item">
                <div class="info-row">
                    <span><strong># <?= $g['id'] ?></strong> — <?= date('d/m/Y', strtotime($g['fecha_gasto'])) ?></span>
                    <span><?= htmlspecialchars($nombreEmp ?: '-') ?></span>
                </div>
                <div class="desc"><?= nl2br(htmlspecialchars($g['descripcion'] ?? '')) ?></div>
                <?php if ($g['soporte'] && strpos($g['soporte'], 'uploads/') === 0): ?>
                    <div class="foto-wrap">
                        <img src="<?= htmlspecialchars($g['soporte']) ?>" alt="Soporte">
                    </div>
                <?php elseif ($g['soporte']): ?>
                    <div class="soporte-ref"><?= htmlspecialchars($g['soporte']) ?></div>
                <?php else: ?>
                    <div class="soporte-ref"><span style="color:#999;font-style:italic;">Sin soporte</span></div>
                <?php endif; ?>
                <div class="valor">$ <?= number_format($g['valor'], 2, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
            <?php for ($i = count($chunk); $i < $itemsPorPagina; $i++): ?>
                <div class="soporte-item" style="border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; color:#ccc;">
                    <span style="font-size:18pt;">- - -</span>
                </div>
            <?php endfor; ?>
        </div>

        <div class="pagina-num">Página <?= $pagina++ ?></div>
    </div>
    <?php endforeach;
    endif; ?>
</body>
</html>
