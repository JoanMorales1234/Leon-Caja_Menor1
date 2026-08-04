<?php

function url($route = '', array $params = [])
{
    $query = 'index.php?url=' . ltrim($route, '/');
    if (!empty($params)) {
        $query .= '&' . http_build_query($params);
    }
    return BASE_URL . $query;
}

function asset($path)
{
    return BASE_URL . ltrim($path, '/');
}

function flash($message, $type = 'info')
{
    return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($message)
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>';
}

function calculateCajaDate($today, $holidays)
{
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

function normalizeNumber($valorStr)
{
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

function handleFotoUpload($file)
{
    $uploadDir = ROOT_PATH . '/uploads';
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
