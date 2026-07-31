<?php

function flash($message, $type = 'info') {
    return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
}

function loadHolidays($pdo) {
    $stmt = $pdo->query('SELECT fecha FROM festivos WHERE estado = "activo"');
    return array_column($stmt->fetchAll(), 'fecha');
}

function calculateCajaDate($today, $holidays) {
    $todayDate = new DateTime($today);
    if ($todayDate->format('w') === '0') return null;
    if ($todayDate->format('w') === '1') {
        $target = new DateTime($today);
        $target->modify('-1 day');
        if ($target->format('w') === '0') $target->modify('-1 day');
        while (in_array($target->format('Y-m-d'), $holidays, true) || $target->format('w') === '0') {
            $target->modify('-1 day');
        }
        return $target->format('Y-m-d');
    }
    $target = new DateTime($today);
    $target->modify('-1 day');
    while ($target->format('w') === '0' || in_array($target->format('Y-m-d'), $holidays, true)) {
        $target->modify('-1 day');
    }
    return $target->format('Y-m-d');
}

function getLastClosedSaldo($pdo, $tipo) {
    $stmt = $pdo->prepare('SELECT valor_final FROM cajas WHERE tipo_caja = ? AND estado = "cerrada" ORDER BY fecha_caja DESC LIMIT 1');
    $stmt->execute([$tipo]);
    $row = $stmt->fetch();
    return $row ? (float)$row['valor_final'] : 0.00;
}

function getOpenCaja($pdo, $tipo) {
    $stmt = $pdo->prepare('SELECT * FROM cajas WHERE tipo_caja = ? AND estado = "abierta" ORDER BY fecha_caja DESC LIMIT 1');
    $stmt->execute([$tipo]);
    return $stmt->fetch();
}

function recalculateCajaFinal($pdo, $cajaId) {
    $stmt = $pdo->prepare('SELECT valor_inicial FROM cajas WHERE id = ?');
    $stmt->execute([$cajaId]);
    $caja = $stmt->fetch();
    if (!$caja) return;
    $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM reintegros WHERE caja_id = ?');
    $stmt->execute([$cajaId]);
    $reintegros = (float)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM gastos WHERE caja_id = ?');
    $stmt->execute([$cajaId]);
    $gastos = (float)$stmt->fetchColumn();
    $valorFinal = (float)$caja['valor_inicial'] + $reintegros - $gastos;
    $stmt = $pdo->prepare('UPDATE cajas SET valor_final = ? WHERE id = ?');
    $stmt->execute([$valorFinal, $cajaId]);
}

function closeCaja($pdo, $cajaId) {
    $stmt = $pdo->prepare('SELECT id FROM cajas WHERE id = ? AND estado = "abierta"');
    $stmt->execute([$cajaId]);
    if (!$stmt->fetch()) throw new Exception('Caja no encontrada o ya está cerrada.');
    recalculateCajaFinal($pdo, $cajaId);
    $stmt = $pdo->prepare('UPDATE cajas SET estado = "cerrada", fecha_cierre = NOW() WHERE id = ?');
    $stmt->execute([$cajaId]);
}

function getCajaDetails($pdo, $tipo) {
    $stmt = $pdo->prepare('SELECT c.*,
        IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
        IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
        FROM cajas c WHERE c.tipo_caja = ? AND c.estado = "abierta" ORDER BY c.fecha_caja DESC LIMIT 1');
    $stmt->execute([$tipo]);
    $caja = $stmt->fetch();
    if ($caja) $caja['saldo_actual'] = (float)$caja['valor_inicial'] + (float)$caja['total_reintegros'] - (float)$caja['total_gastos'];
    return $caja;
}

function getCajaMovements($pdo, $cajaId) {
    $gastos = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, p.nombre AS proveedor_nombre
        FROM gastos g
        LEFT JOIN empleados e ON g.empleado_id = e.id
        LEFT JOIN proveedores p ON g.proveedor_id = p.id
        WHERE g.caja_id = ? ORDER BY g.orden ASC, g.id DESC');
    $gastos->execute([$cajaId]);
    $gastos = $gastos->fetchAll();
    $soportesStmt = $pdo->prepare('SELECT * FROM soportes WHERE gasto_id = ? ORDER BY orden, id');
    foreach ($gastos as &$gasto) {
        $soportesStmt->execute([$gasto['id']]);
        $gasto['soportes'] = $soportesStmt->fetchAll();
    }
    unset($gasto);
    $reintegros = $pdo->prepare('SELECT * FROM reintegros WHERE caja_id = ? ORDER BY creado_en DESC');
    $reintegros->execute([$cajaId]);
    return ['gastos' => $gastos, 'reintegros' => $reintegros->fetchAll()];
}

function getOpenCajas($pdo) {
    return $pdo->query('SELECT * FROM cajas WHERE estado = "abierta" ORDER BY fecha_caja DESC')->fetchAll();
}

function getMovements($pdo) {
    $gastos = $pdo->query('SELECT g.*, c.tipo_caja, c.fecha_caja FROM gastos g JOIN cajas c ON g.caja_id = c.id ORDER BY g.creado_en DESC LIMIT 10')->fetchAll();
    $reintegros = $pdo->query('SELECT r.*, c.tipo_caja, c.fecha_caja FROM reintegros r JOIN cajas c ON r.caja_id = c.id ORDER BY r.creado_en DESC LIMIT 10')->fetchAll();
    return ['gastos' => $gastos, 'reintegros' => $reintegros];
}

function getBoxesSummary($pdo, $tipo) {
    $stmt = $pdo->prepare('SELECT c.*, IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros, IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos FROM cajas c WHERE c.tipo_caja = ? ORDER BY c.fecha_caja DESC LIMIT 1');
    $stmt->execute([$tipo]);
    return $stmt->fetch();
}

function normalizeNumber($valorStr) {
    $clean = preg_replace('/[^0-9.,\-]/', '', $valorStr);
    if ($clean === '' || $clean === '-') return 0;
    $lastDot = strrpos($clean, '.');
    $lastComma = strrpos($clean, ',');
    if ($lastDot !== false && $lastComma !== false) {
        if ($lastDot > $lastComma) {
            $clean = str_replace(',', '', $clean);
        } else {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
    } elseif ($lastComma !== false) {
        $clean = str_replace(',', '.', $clean);
    }
    return (float)$clean;
}

function handleFotoUpload($file) {
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    if (!in_array($ext, $allowed)) {
        throw new Exception('Formato de imagen no permitido. Usa JPG, PNG, GIF, WEBP o BMP.');
    }
    $filename = uniqid('recibo_') . '.' . $ext;
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Error al guardar la imagen.');
    }
    return 'uploads/' . $filename;
}

function getOpenCajaDetails($pdo) {
    $cajas = getOpenCajas($pdo);
    foreach ($cajas as $i => $caja) {
        $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM gastos WHERE caja_id = ?');
        $stmt->execute([$caja['id']]);
        $cajas[$i]['total_gastos'] = (float)$stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM reintegros WHERE caja_id = ?');
        $stmt->execute([$caja['id']]);
        $cajas[$i]['total_reintegros'] = (float)$stmt->fetchColumn();
        $cajas[$i]['saldo_actual'] = (float)$caja['valor_inicial'] + $cajas[$i]['total_reintegros'] - $cajas[$i]['total_gastos'];
    }
    return $cajas;
}
