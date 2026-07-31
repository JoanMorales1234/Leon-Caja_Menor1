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
    WHERE g.caja_id = ? ORDER BY g.orden ASC, g.id ASC');
$gastos->execute([$cajaId]);
$todosGastos = $gastos->fetchAll();
$sopStmt = $pdo->prepare('SELECT * FROM soportes WHERE gasto_id = ? ORDER BY orden, id');
foreach ($todosGastos as &$g) {
    $sopStmt->execute([$g['id']]);
    $g['soportes'] = $sopStmt->fetchAll();
}
unset($g);

// Build a flat list of soporte items with employee info
$todosSoportes = [];
foreach ($todosGastos as $g) {
    $nombreEmp = trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? ''));
    if (!empty($g['soportes'])) {
        foreach ($g['soportes'] as $sop) {
            if ($sop['archivo']) {
                $todosSoportes[] = [
                    'id' => $g['id'],
                    'nombre' => $nombreEmp,
                    'archivo' => $sop['archivo'],
                    'orden' => $sop['orden'],
                ];
            }
        }
    } elseif ($g['soporte']) {
        $todosSoportes[] = [
            'id' => $g['id'],
            'nombre' => $nombreEmp,
            'archivo' => $g['soporte'],
            'orden' => 0,
        ];
    }
}
$totalGastos = count($todosSoportes);
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

$orientacion = isset($_GET['orientacion']) && $_GET['orientacion'] === 'vertical' ? 'vertical' : 'horizontal';
$pageW = $orientacion === 'vertical' ? '21.6cm' : '27.9cm';
$pageH = $orientacion === 'vertical' ? '27.9cm' : '21.6cm';
$pageSize = $orientacion === 'vertical' ? 'letter portrait' : 'letter landscape';
$chunks = array_chunk($todosSoportes, 4);
$totalPaginas = count($chunks);
$storageKey = "sop_pos_c{$cajaId}_o{$orientacion}";
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>CAJA <?= $tipoMay ?> — <?= $fechaCajaStr ?> — SOPORTES</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #000; background: #d8d8d8; }

.tbar {
    position: sticky; top: 0; z-index: 9999;
    background: #fff; border-bottom: 1px solid #bbb;
    padding: 8px 16px; display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.tbar .btn-c {
    padding: 5px 12px; font-size: 12px; cursor: pointer;
    border: 1px solid #bbb; border-radius: 4px; background: #f8f9fa;
    display: inline-flex; align-items: center; gap: 4px;
    text-decoration: none; color: #333;
}
.tbar .btn-c:hover { background: #e9ecef; }
.tbar .btn-c.prim { background: #0d6efd; color: #fff; border-color: #0d6efd; }
.tbar .btn-c.prim:hover { background: #0b5ed7; }
.tbar .btn-c.act { background: #0d6efd; color: #fff; border-color: #0d6efd; }
.tbar label { font-size: 11px; color: #555; }
.tbar input[type="range"] { width: 80px; vertical-align: middle; }
.tbar .sep { width: 1px; height: 24px; background: #ddd; display: inline-block; }
.tbar .val { font-size: 11px; color: #666; min-width: 30px; display: inline-block; text-align: center; }
.tbar .hint { font-size: 11px; color: #999; }
.tbar .badge-items { font-size: 11px; background: #eee; padding: 2px 10px; border-radius: 10px; color: #555; }

.cwrap {
    display: flex; flex-direction: column; align-items: center;
    padding: 20px 10px; gap: 30px;
}

.cpage {
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.2);
    width: <?= $pageW ?>; min-height: <?= $pageH ?>;
    padding: 0.8cm;
    position: relative;
    transform-origin: top center;
    transition: width 0.2s;
}
.cpage:last-child { margin-bottom: 30px; }

.phdr {
    display: flex; align-items: center; justify-content: center;
    position: relative; margin-bottom: 15px; padding-bottom: 6px;
    border-bottom: 2px solid #111; min-height: 60px;
}
.phdr-logo { position: absolute; left: 0; top: 50%; transform: translateY(-50%); }
.phdr-logo img { max-width: 140px; max-height: 80px; display: block; filter: brightness(0.6) contrast(1.3); }
.phdr-text { text-align: center; }
.phdr h2 { font-size: 10pt; font-weight: bold; letter-spacing: 1px; font-family: 'Courier New', Courier, monospace; }
.phdr h3 { font-size: 7pt; font-weight: normal; font-family: 'Courier New', Courier, monospace; }

/* ---- SOPORTE ITEM ---- */
.sop {
    position: absolute;
    cursor: move;
    user-select: none;
    background: #fff;
    border: 1px solid #333;
    padding: 2px;
    z-index: 10;
    min-width: 100px;
    touch-action: none;
    display: inline-flex; flex-direction: column;
}
.sop:hover { z-index: 20; box-shadow: 0 2px 10px rgba(0,0,0,0.13); }
.sop.sel { z-index: 30; border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,0.25); }

.sop .nom {
    font-size: 7pt; margin-bottom: 1px;
    font-family: 'Courier New', Courier, monospace;
    font-weight: bold; flex-shrink: 0;
}

.sop .foto {
    line-height: 0;
    display: inline-block; align-self: flex-start;
    position: relative; margin: 1px 0;
    background: transparent;
}
.sop .foto img { display: block; max-width: none; max-height: none; }
.sop .foto .empty {
    display: inline-block; padding: 6px; color: #aaa;
    font-style: italic; font-size: 7pt; line-height: 1.2;
}

/* ---- Word-like resize handles ---- */
.sop .foto .fh {
    position: absolute; width: 10px; height: 10px;
    background: #fff; border: 2px solid #0d6efd;
    z-index: 40; border-radius: 1px;
    display: none;
}
.sop.sel .foto .fh { display: block; }
.sop .foto .fh-rb { right: -5px; bottom: -5px; cursor: nwse-resize; }
.sop .foto .fh-rt { right: -5px; top: -5px; cursor: nesw-resize; }
.sop .foto .fh-lb { left: -5px; bottom: -5px; cursor: nesw-resize; }
.sop .foto .fh-lt { left: -5px; top: -5px; cursor: nwse-resize; }
.sop .foto .fh-t  { left: 50%; top: -5px; margin-left: -5px; cursor: ns-resize; }
.sop .foto .fh-b  { left: 50%; bottom: -5px; margin-left: -5px; cursor: ns-resize; }
.sop .foto .fh-l  { left: -5px; top: 50%; margin-top: -5px; cursor: ew-resize; }
.sop .foto .fh-r  { right: -5px; top: 50%; margin-top: -5px; cursor: ew-resize; }

/* Word-like rotation handle — circular, with connecting line */
.sop .foto .fh-rot {
    position: absolute; width: 18px; height: 18px;
    background: #fff; border: 2px solid #0d6efd;
    border-radius: 50%; z-index: 41;
    cursor: grab; top: -22px; left: 50%; margin-left: -9px;
    display: none;
}
.sop.sel .foto .fh-rot { display: block; }
.sop .foto .fh-rot::after {
    content: '↻'; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%); font-size: 11px;
    color: #0d6efd; line-height: 1;
}
.sop .foto .fh-line {
    position: absolute; width: 2px; height: 18px;
    background: #0d6efd; z-index: 41; pointer-events: none;
    left: 50%; margin-left: -1px; top: -22px;
    display: none;
}
.sop.sel .foto .fh-line { display: block; }

/* ---- Controls bar ---- */
.sop .ctrl {
    position: absolute; top: -22px; left: 0;
    display: none; gap: 2px; background: #333; padding: 1px 4px;
    border-radius: 3px; z-index: 50; white-space: nowrap;
}
.sop.sel .ctrl { display: inline-flex; }
.sop .ctrl button, .sop .ctrl select {
    padding: 0 4px; font-size: 9px; cursor: pointer;
    border: none; border-radius: 2px; background: #555; color: #fff; height: 18px;
}
.sop .ctrl button:hover { background: #777; }
.sop .ctrl select { background: #555; color: #fff; }
.sop .ctrl input[type="number"] {
    width: 36px; padding: 0 2px; font-size: 9px;
    border: none; border-radius: 2px; height: 18px; text-align: center; background: #fff; color: #000;
}

.pgnum {
    position: absolute; bottom: 5px; right: 0.8cm;
    font-size: 7pt; color: #999;
    font-family: 'Courier New', Courier, monospace;
}



@page { margin: 0; size: <?= $pageSize ?>; }
@media print {
    body { background: #fff; }
    .tbar { display: none; }
    #newpage-btn { display: none !important; }
    .cwrap { padding: 0; gap: 0; }
    .cpage {
        box-shadow: none; padding: 0.8cm;
        margin: 0 !important; page-break-after: always;
    }
    .cpage:last-child { page-break-after: auto; margin-bottom: 0; }
    .sop {
        position: absolute !important;
        cursor: default; box-shadow: none;
        border: 1px solid #333;
    }
    .sop .ctrl { display: none !important; }
    .sop .fh { display: none !important; }
    .sop .foto { background: transparent; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

<div class="tbar">
    <button class="btn-c prim" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    <div class="sep"></div>
    <span style="display:inline-flex;gap:3px;">
        <a href="?caja_id=<?= $cajaId ?>&orientacion=horizontal" class="btn-c <?= $orientacion==='horizontal'?'act':'' ?>"><i class="bi bi-arrows-angle-expand"></i> Horizontal</a>
        <a href="?caja_id=<?= $cajaId ?>&orientacion=vertical" class="btn-c <?= $orientacion==='vertical'?'act':'' ?>"><i class="bi bi-arrows-angle-contract"></i> Vertical</a>
    </span>
    <div class="sep"></div>
    <label>Zoom</label>
    <input type="range" id="zm" min="50" max="100" value="70" step="5" oninput="setZoom(this.value)">
    <span class="val" id="zmV">70%</span>
    <div class="sep"></div>
    <span class="hint"><i class="bi bi-arrows-move"></i> Arrastra para mover | Esquinas: redimension proporcional | Lados: ancho/alto | Círculo blanco: rotar</span>
    <span class="sep"></span>
    <span class="badge-items"><?= $totalGastos ?> soporte(s) — <?= $totalPaginas ?> pág(s)</span>
    <span class="sep"></span>
    <button class="btn-c" onclick="window.close()"><i class="bi bi-x-lg"></i> Cerrar</button>
</div>

<div class="cwrap" id="cwrap">

<?php if (empty($todosGastos)): ?>
    <div class="cpage" style="display:flex;align-items:center;justify-content:center;">
        <p style="font-size:16pt;color:#999;">No hay soportes para esta caja.</p>
    </div>
<?php else:
    $numPag = 1;
    foreach ($chunks as $chunk):
        $topBase = 45;
        $leftBase = 20;
        $i = 0;
?>
    <div class="cpage" data-page="<?= $numPag ?>">
        <div class="phdr">
            <?php if (file_exists(__DIR__ . '/uploads/logo.png')): ?>
                <div class="phdr-logo"><img src="uploads/logo.png?t=<?= time() ?>" alt="Logo"></div>
            <?php endif; ?>
            <div class="phdr-text">
                <h2>CAJA <?= $tipoMay ?> — <?= $fechaCajaStr ?></h2>
                <h3>SOPORTES</h3>
            </div>
        </div>

        <?php foreach ($chunk as $s):
            $imgSrc = ($s['archivo'] && strpos($s['archivo'], 'uploads/') === 0) ? htmlspecialchars($s['archivo']) : '';
        ?>
        <div class="sop" id="sop-<?= $s['id'] ?>-p<?= $numPag ?>-i<?= $i ?>"
             data-id="<?= $s['id'] ?>"
             style="top:<?= $topBase + $i*170 ?>px; left:<?= $leftBase + ($i%2)*10 ?>px;">
            <div class="ctrl">
                <button onclick="sopFwd(this)" title="Al frente"><i class="bi bi-arrow-up"></i></button>
                <button onclick="sopBwd(this)" title="Atrás"><i class="bi bi-arrow-down"></i></button>
                <span style="color:#aaa;margin:0 1px;">|</span>
                <button onclick="imgRotLeft(this)" title="Rotar izq 90°">↺</button>
                <input type="number" class="irot" value="0" min="-360" max="360" onchange="imgSetRot(this)" style="width:32px;font-size:8px;">
                <button onclick="imgRotRight(this)" title="Rotar der 90°">↻</button>
                <span style="color:#aaa;font-size:7px;">°</span>
                <span style="color:#aaa;margin:0 1px;">|</span>
                <select onchange="moveToPage(this)" class="pmv" style="width:40px;font-size:8px;"></select>
                <span style="color:#aaa;margin:0 1px;">|</span>
                <button onclick="delSop(this)" style="background:#dc3545;">✕</button>
            </div>
            <div class="nom"><?= htmlspecialchars($s['nombre'] ?: '-') ?></div>
            <?php if ($imgSrc): ?>
                <div class="foto">
                    <img src="<?= $imgSrc ?>" alt="S" class="simg" style="width:180px;" data-rotate="0">
                    <div class="fh fh-t"></div>
                    <div class="fh fh-b"></div>
                    <div class="fh fh-l"></div>
                    <div class="fh fh-r"></div>
                    <div class="fh fh-lt"></div>
                    <div class="fh fh-rt"></div>
                    <div class="fh fh-lb"></div>
                    <div class="fh fh-rb"></div>
                    <div class="fh fh-line"></div>
                    <div class="fh fh-rot"></div>
                </div>
            <?php else: ?>
                <div class="foto"><span class="empty">Sin imagen</span></div>
            <?php endif; ?>
        </div>
        <?php $i++; endforeach; ?>

        <div class="pgnum">Página <?= $numPag ?> de <?= $totalPaginas ?></div>
    </div>
<?php
        $numPag++;
    endforeach;
    endif;
?>
    <div id="newpage-btn" style="position:fixed;bottom:30px;left:50%;transform:translateX(-50%);z-index:10000;text-align:center;">
        <button class="btn-c prim" onclick="createNewPage()" style="font-size:14px;padding:10px 24px;box-shadow:0 2px 12px rgba(0,0,0,0.3);"><i class="bi bi-plus-lg"></i> Nueva hoja</button>
    </div>
</div>

<script>
(function() {
    var page = document.querySelector('.cpage');
    if (!page) return;

    // ===== DRAG (mover todo el soporte) =====
    var dragEl = null, dOffX = 0, dOffY = 0;
    document.addEventListener('mousedown', function(e) {
        var sop = e.target.closest('.sop');
        if (!sop) return;
        if (e.target.closest('.ctrl') || e.target.closest('.fh') || e.target.closest('select') || e.target.closest('input') || e.target.closest('button')) return;
        dragEl = sop;
        var r = sop.getBoundingClientRect();
        dOffX = e.clientX - r.left;
        dOffY = e.clientY - r.top;
        document.querySelectorAll('.sop').forEach(function(s) { s.classList.remove('sel'); });
        sop.classList.add('sel');
        var fotoSel = sop.querySelector('.foto');
        if (fotoSel) repositionHandles(fotoSel);
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragEl) return;
        var cpage = dragEl.closest('.cpage');
        if (!cpage) return;
        var pr = cpage.getBoundingClientRect();
        var sr = dragEl.getBoundingClientRect();
        var scale = pr.width / cpage.offsetWidth;
        var l = (e.clientX - pr.left - dOffX) / scale;
        var t = (e.clientY - pr.top - dOffY) / scale;
        var sopW = dragEl.offsetWidth;
        var sopH = dragEl.offsetHeight;
        if (l < -15 || l > pr.width / scale - sopW + 15 ||
            t < -15 || t > pr.height / scale - sopH + 10) {
            var allPages = document.querySelectorAll('.cpage');
            for (var pi = 0; pi < allPages.length; pi++) {
                var p = allPages[pi];
                if (p === cpage) continue;
                var vr = p.getBoundingClientRect();
                if (e.clientX >= vr.left && e.clientX <= vr.right &&
                    e.clientY >= vr.top && e.clientY <= vr.bottom) {
                    var nl = (e.clientX - vr.left - dOffX) / scale;
                    var nt = (e.clientY - vr.top - dOffY) / scale;
                    nl = Math.max(5, Math.min(nl, vr.width / scale - sopW - 5));
                    nt = Math.max(25, Math.min(nt, vr.height / scale - sopH - 10));
                    cpage.removeChild(dragEl);
                    var pgn = p.querySelector('.pgnum');
                    p.insertBefore(dragEl, pgn);
                    dragEl.style.left = nl + 'px';
                    dragEl.style.top = nt + 'px';
                    updatePageSelects();
                    break;
                }
            }
        } else {
            l = Math.max(5, Math.min(l, pr.width / scale - sopW - 5));
            t = Math.max(25, Math.min(t, pr.height / scale - sopH - 10));
            dragEl.style.left = l + 'px';
            dragEl.style.top = t + 'px';
        }
    });
    document.addEventListener('mouseup', function() { dragEl = null; });

    // ===== RESIZE (8 handles — Word-like proportional corners) =====
    var rEl = null, rFoto = null, rType = '', rSX = 0, rSY = 0, rSW = 0, rSH = 0, rAspect = 1, rQuad = 0;
    document.addEventListener('mousedown', function(e) {
        var fh = e.target.closest('.fh');
        if (!fh) return;
        if (fh.classList.contains('fh-rot') || fh.classList.contains('fh-line')) return;
        rFoto = fh.closest('.foto');
        if (!rFoto) return;
        rEl = rFoto.closest('.sop');
        if (!rEl) return;
        var cl = fh.classList;
        if (cl.contains('fh-rb')) rType = 'se';
        else if (cl.contains('fh-rt')) rType = 'ne';
        else if (cl.contains('fh-lb')) rType = 'sw';
        else if (cl.contains('fh-lt')) rType = 'nw';
        else if (cl.contains('fh-r')) rType = 'e';
        else if (cl.contains('fh-l')) rType = 'w';
        else if (cl.contains('fh-b')) rType = 's';
        else if (cl.contains('fh-t')) rType = 'n';
        if (!rType) return;
        rSX = e.clientX; rSY = e.clientY;
        var img = rFoto.querySelector('.simg');
        rSW = 180; rSH = 0; rAspect = 1; rQuad = 0;
        if (img) {
            var degRot = parseFloat(img.getAttribute('data-rotate')) || 0;
            rQuad = Math.round((((degRot % 360) + 360) % 360) / 90) % 4;
            var w = parseInt(img.style.width);
            if (w && w > 0) rSW = w;
            var h = parseInt(img.style.height);
            if (h && h > 0) rSH = h;
            else if (img.offsetHeight > 0) rSH = img.offsetHeight;
            if (rSH > 0) rAspect = rSW / rSH;
        }
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!rEl || !rType) return;
        var dx = e.clientX - rSX;
        var dy = e.clientY - rSY;
        var img;
        if (rFoto) img = rFoto.querySelector('.simg');
        var nw = rSW, nh = rSH;
        var q = rQuad, isCorner = (rType === 'se' || rType === 'ne' || rType === 'sw' || rType === 'nw');
        if (isCorner) {
            // Outward component on the dominant axis for this quadrant
            var visR = (q === 0 || q === 2) ? dx : dy;
            var rComp;
            // Even q (0,2): se/ne add; Odd q (1,3): se/sw add
            var add = (q % 2 === 0)
                ? (rType === 'se' || rType === 'ne')
                : (rType === 'se' || rType === 'sw');
            rComp = rSW + (add ? visR : -visR);
            nw = Math.max(20, Math.min(rComp, 800));
            nh = nw / rAspect;
        } else {
            // Side handles: always +delta for outward (right/down/bottom), -delta for inward
            // Dimension mapping per quadrant keeps the correct axis
            switch (rType) {
                case 'e':
                    if (q === 0) nw = rSW + dx;
                    else if (q === 1) nh = rSH + dx;
                    else if (q === 2) nw = rSW + dx;
                    else nh = rSH + dx;
                    break;
                case 'w':
                    if (q === 0) nw = rSW - dx;
                    else if (q === 1) nh = rSH - dx;
                    else if (q === 2) nw = rSW - dx;
                    else nh = rSH - dx;
                    break;
                case 's':
                    if (q === 0) nh = rSH + dy;
                    else if (q === 1) nw = rSW + dy;
                    else if (q === 2) nh = rSH + dy;
                    else nw = rSW + dy;
                    break;
                case 'n':
                    if (q === 0) nh = rSH - dy;
                    else if (q === 1) nw = rSW - dy;
                    else if (q === 2) nh = rSH - dy;
                    else nw = rSW - dy;
                    break;
            }
        }
        nw = Math.max(20, Math.min(nw, 800));
        nh = Math.max(20, Math.min(nh, 800));
        if (img) {
            img.style.width = nw + 'px';
            img.style.height = nh + 'px';
        }
        if (rEl) { repositionHandles(rFoto); syncSopSize(rEl); }
    });
    document.addEventListener('mouseup', function() { rEl = null; rFoto = null; rType = ''; });

    // ===== REPOSITION HANDLES + LINE TO VISUAL CORNERS =====
    function repositionHandles(foto, forceReset) {
        var simg = foto.querySelector('.simg');
        if (!simg) return;
        var deg = (parseFloat(simg.getAttribute('data-rotate')) % 360 + 360) % 360;
        if (deg === 0 && !forceReset) {
            var allHandles = foto.querySelectorAll('.fh');
            allHandles.forEach(function(h) {
                h.style.left = '';
                h.style.top = '';
                h.style.right = '';
                h.style.bottom = '';
            });
            foto.style.width = '';
            foto.style.height = '';
            foto.style.marginLeft = '';
            foto.style.marginTop = '';
            return;
        }
        var w = simg.offsetWidth, h = simg.offsetHeight;
        if (w === 0 || h === 0) return;
        var cx = w / 2, cy = h / 2;
        var rad = deg * Math.PI / 180;
        var cosA = Math.cos(rad), sinA = Math.sin(rad);
        function rot(x, y) {
            var dx = x - cx, dy = y - cy;
            return { x: cx + dx * cosA + dy * sinA, y: cy - dx * sinA + dy * cosA };
        }
        var c = [rot(0,0), rot(w,0), rot(w,h), rot(0,h)];
        var minX = c[0].x, maxX = c[0].x, minY = c[0].y, maxY = c[0].y;
        for (var i = 1; i < 4; i++) {
            if (c[i].x < minX) minX = c[i].x;
            if (c[i].x > maxX) maxX = c[i].x;
            if (c[i].y < minY) minY = c[i].y;
            if (c[i].y > maxY) maxY = c[i].y;
        }
        var visW = maxX - minX, visH = maxY - minY;
        foto.style.width = visW + 'px';
        foto.style.height = visH + 'px';
        foto.style.marginLeft = (-minX) + 'px';
        foto.style.marginTop = (-minY) + 'px';
        // Handles are positioned in the IMAGE coordinate space (origin at (0,0) inside foto).
        // The visual bounding box spans (minX..maxX, minY..maxY) in that space.
        var vcx = (minX + maxX) / 2, vcy = (minY + maxY) / 2;
        var handleInfo = [
            { sel: '.fh-r',  x: maxX, y: vcy },
            { sel: '.fh-l',  x: minX, y: vcy },
            { sel: '.fh-t',  x: vcx,  y: minY },
            { sel: '.fh-b',  x: vcx,  y: maxY },
            { sel: '.fh-rt', x: maxX, y: minY },
            { sel: '.fh-lt', x: minX, y: minY },
            { sel: '.fh-rb', x: maxX, y: maxY },
            { sel: '.fh-lb', x: minX, y: maxY },
            { sel: '.fh-rot',x: vcx,  y: minY - 14, w:18, h:18 }
        ];
        handleInfo.forEach(function(info) {
            var el = foto.querySelector(info.sel);
            if (!el) return;
            var hw = info.w || 10, hh = info.h || 10;
            el.style.left = (info.x - hw / 2) + 'px';
            el.style.top = (info.y - hh / 2) + 'px';
            el.style.right = '';
            el.style.bottom = '';
        });
        var line = foto.querySelector('.fh-line');
        if (line) {
            var rotH = foto.querySelector('.fh-rot');
            if (rotH) {
                var rT = parseFloat(rotH.style.top);
                var rB = rT + 18;
                var imgT = minY;
                line.style.left = (vcx - 1) + 'px';
                line.style.top = Math.min(rB, imgT) + 'px';
                line.style.height = Math.max(1, Math.abs(rB - imgT)) + 'px';
                line.style.right = '';
                line.style.bottom = '';
            }
        }
    }

    // ===== SYNC .sop DIMENSIONS TO ROTATED IMAGE =====
    function syncSopSize(item) {
        var foto = item.querySelector('.foto');
        if (!foto) return;
        var simg = foto.querySelector('.simg');
        if (!simg) return;
        var deg = (parseFloat(simg.getAttribute('data-rotate')) % 360 + 360) % 360;
        var isRot = (deg !== 0);
        var nom = item.querySelector('.nom');
        var nomH = nom ? nom.offsetHeight : 0;
        var nomM = nom ? parseFloat(getComputedStyle(nom).marginBottom) || 0 : 0;
        if (isRot) {
            var w = simg.offsetWidth, h = simg.offsetHeight;
            if (w === 0 || h === 0) return;
            var cx = w / 2, cy = h / 2;
            var rad = deg * Math.PI / 180;
            var cosA = Math.cos(rad), sinA = Math.sin(rad);
            function rot(x, y) {
                var dx = x - cx, dy = y - cy;
                return { x: cx + dx * cosA + dy * sinA, y: cy - dx * sinA + dy * cosA };
            }
            var c = [rot(0,0), rot(w,0), rot(w,h), rot(0,h)];
            var minX = c[0].x, maxX = c[0].x, minY = c[0].y, maxY = c[0].y;
            for (var i = 1; i < 4; i++) {
                if (c[i].x < minX) minX = c[i].x;
                if (c[i].x > maxX) maxX = c[i].x;
                if (c[i].y < minY) minY = c[i].y;
                if (c[i].y > maxY) maxY = c[i].y;
            }
            var visW = maxX - minX, visH = maxY - minY;
            var pad = 2;
            item.style.width = visW + pad * 2 + 'px';
            item.style.height = nomH + nomM + visH + pad * 2 + 'px';
        } else {
            item.style.width = '';
            item.style.height = '';
            foto.style.width = '';
            foto.style.height = '';
            foto.style.marginLeft = '';
            foto.style.marginTop = '';
        }
    }

    // ===== ROTATION (manija central superior — rota solo el <img>) =====
    var rotEl = null, rotImg = null, rotCX = 0, rotCY = 0, rotStartA = 0, rotBaseD = 0;
    document.addEventListener('mousedown', function(e) {
        var fh = e.target.closest('.fh-rot');
        if (!fh) return;
        var foto = fh.closest('.foto');
        if (!foto) return;
        rotEl = foto.closest('.sop');
        if (!rotEl) return;
        rotImg = foto.querySelector('.simg');
        if (!rotImg) return;
        var r = rotImg.getBoundingClientRect();
        rotCX = r.left + r.width / 2;
        rotCY = r.top + r.height / 2;
        rotStartA = Math.atan2(e.clientY - rotCY, e.clientX - rotCX) * (180 / Math.PI);
        rotBaseD = parseFloat(rotImg.getAttribute('data-rotate')) || 0;
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!rotEl || !rotImg) return;
        var a = Math.atan2(e.clientY - rotCY, e.clientX - rotCX) * (180 / Math.PI);
        var d = rotBaseD + (a - rotStartA);
        rotImg.style.transform = 'rotate(' + d + 'deg)';
        rotImg.setAttribute('data-rotate', d);
        var inp = rotEl.querySelector('.irot');
        if (inp) inp.value = Math.round(((d % 360) + 360) % 360);
        repositionHandles(rotImg.closest('.foto'));
        syncSopSize(rotEl);
    });
    document.addEventListener('mouseup', function() { rotEl = null; rotImg = null; });

    // ===== CONTROLS =====
    window.sopFwd = function(b) { var s=b.closest('.sop'); if(!s)return; var m=10; document.querySelectorAll('.sop').forEach(function(e){var z=parseInt(e.style.zIndex)||10;if(z>m)m=z;}); s.style.zIndex=m+1; };
    window.sopBwd = function(b) { var s=b.closest('.sop'); if(!s)return; var m=999; document.querySelectorAll('.sop').forEach(function(e){var z=parseInt(e.style.zIndex)||10;if(z<m)m=z;}); s.style.zIndex=m-1; };
    window.delSop = function(b) { if(!confirm('Eliminar este soporte de la hoja?'))return; var s=b.closest('.sop'); if(s)s.remove(); };

    // ===== ROTATION CONTROLS =====
    function applyRot(item, deg) {
        var foto = item.querySelector('.foto');
        if (!foto) return;
        var simg = foto.querySelector('.simg');
        if (!simg) return;
        simg.style.transform = 'rotate(' + deg + 'deg)';
        simg.setAttribute('data-rotate', deg);
        var inp = item.querySelector('.irot');
        if (inp) inp.value = Math.round(((deg % 360) + 360) % 360);
        repositionHandles(foto);
        syncSopSize(item);
    }
    window.imgRotLeft = function(b) {
        var item = b.closest('.sop'); if (!item) return;
        var simg = item.querySelector('.simg'); if (!simg) return;
        var cur = parseFloat(simg.getAttribute('data-rotate')) || 0;
        applyRot(item, cur - 90);
    };
    window.imgRotRight = function(b) {
        var item = b.closest('.sop'); if (!item) return;
        var simg = item.querySelector('.simg'); if (!simg) return;
        var cur = parseFloat(simg.getAttribute('data-rotate')) || 0;
        applyRot(item, cur + 90);
    };
    window.imgSetRot = function(inp) {
        var item = inp.closest('.sop'); if (!item) return;
        applyRot(item, parseFloat(inp.value) || 0);
    };

    // ===== PAGE MANAGEMENT =====
    function updatePageNums() {
        var pages = document.querySelectorAll('.cpage');
        pages.forEach(function(p, idx) {
            p.setAttribute('data-page', idx + 1);
            var pn = p.querySelector('.pgnum');
            if (pn) pn.textContent = 'Página ' + (idx + 1) + ' de ' + pages.length;
        });
    }
    function updatePageSelects() {
        var pages = document.querySelectorAll('.cpage');
        var selects = document.querySelectorAll('.pmv');
        selects.forEach(function(sel) {
            var sop = sel.closest('.sop');
            var curPage = sop ? sop.closest('.cpage') : null;
            var curIdx = curPage ? Array.from(pages).indexOf(curPage) + 1 : 1;
            sel.innerHTML = '';
            pages.forEach(function(p, idx) {
                var opt = document.createElement('option');
                opt.value = idx + 1;
                opt.textContent = 'P' + (idx + 1);
                if (idx + 1 === curIdx) opt.selected = true;
                sel.appendChild(opt);
            });
            var optN = document.createElement('option');
            optN.value = 'new';
            optN.textContent = 'N+';
            sel.appendChild(optN);
        });
    }
    window.moveToPage = function(sel) {
        var sop = sel.closest('.sop'); if (!sop) return;
        var val = sel.value;
        var curPage = sop.closest('.cpage');
        if (val === 'new') {
            var np = createNewPage();
            curPage.removeChild(sop);
            np.insertBefore(sop, np.querySelector('.pgnum'));
            updatePageSelects();
            return;
        }
        var idx = parseInt(val) - 1;
        var pages = document.querySelectorAll('.cpage');
        var target = pages[idx];
        if (!target || target === curPage) return;
        curPage.removeChild(sop);
        target.insertBefore(sop, target.querySelector('.pgnum'));
        updatePageSelects();
    };
    window.createNewPage = function() {
        var cwrap = document.getElementById('cwrap');
        var pages = document.querySelectorAll('.cpage');
        if (!pages.length) return null;
        var tmpl = pages[0].cloneNode(true);
        tmpl.querySelectorAll('.sop').forEach(function(s) { s.remove(); });
        var newNum = pages.length + 1;
        tmpl.setAttribute('data-page', newNum);
        var pn = tmpl.querySelector('.pgnum');
        if (pn) pn.textContent = 'Página ' + newNum + ' de ' + newNum;
        cwrap.appendChild(tmpl);
        updatePageNums();
        updatePageSelects();
        return tmpl;
    };

    window.setZoom = function(v) {
        var scale = v / 100;
        var pages = document.querySelectorAll('.cpage');
        pages.forEach(function(p) {
            p.style.transform = 'scale(' + scale + ')';
            p.style.transformOrigin = 'top center';
            p.style.marginBottom = (-p.offsetHeight * (1 - scale)) + 'px';
        });
        document.getElementById('zmV').textContent = v + '%';
    };

    // Click outside deselects
    document.addEventListener('click', function(e) {
        var sop=e.target.closest('.sop');
        if(!sop || e.target.closest('.ctrl')) return;
        document.querySelectorAll('.sop').forEach(function(s){ s.classList.remove('sel'); });
        sop.classList.add('sel');
        var fotoSel = sop.querySelector('.foto');
        if (fotoSel) repositionHandles(fotoSel);
    });
    document.addEventListener('click', function(e) {
        if(e.target.closest('.sop')||e.target.closest('.tbar')) return;
        document.querySelectorAll('.sop').forEach(function(s){ s.classList.remove('sel'); });
    });

    // Init page selects
    updatePageSelects();
    // Init zoom
    setZoom(70);
})();
</script>
</body>
</html>
