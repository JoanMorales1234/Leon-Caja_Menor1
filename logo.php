<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

$message = '';
$type = 'info';

$logoPath = 'uploads/logo.png';
$logoExists = file_exists(__DIR__ . '/' . $logoPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar']) && $logoExists) {
        @unlink(__DIR__ . '/' . $logoPath);
        $message = 'Logo eliminado.';
        $type = 'success';
        $logoExists = false;
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $message = 'Formato no permitido. Usa JPG, PNG, GIF o WEBP.';
            $type = 'danger';
        } else {
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $tempFile = $_FILES['logo']['tmp_name'];
            if (function_exists('getimagesize')) {
                $imgInfo = getimagesize($tempFile);
                if (!$imgInfo) {
                    $message = 'El archivo no es una imagen válida.';
                    $type = 'danger';
                } elseif (function_exists('imagecreatefrompng')) {
                    $srcW = $imgInfo[0];
                    $srcH = $imgInfo[1];
                    switch ($imgInfo[2]) {
                        case IMAGETYPE_JPEG: $srcImg = imagecreatefromjpeg($tempFile); break;
                        case IMAGETYPE_PNG: $srcImg = imagecreatefrompng($tempFile); break;
                        case IMAGETYPE_GIF: $srcImg = imagecreatefromgif($tempFile); break;
                        case IMAGETYPE_WEBP: $srcImg = imagecreatefromwebp($tempFile); break;
                        default: $srcImg = null;
                    }
                    if (!$srcImg) {
                        $message = 'Error al procesar la imagen.';
                        $type = 'danger';
                    } else {
                        $maxW = 300;
                        $maxH = 100;
                        $ratio = min($maxW / $srcW, $maxH / $srcH, 1);
                        $dstW = (int)round($srcW * $ratio);
                        $dstH = (int)round($srcH * $ratio);
                        $dstImg = imagecreatetruecolor($dstW, $dstH);
                        imagealphablending($dstImg, false);
                        imagesavealpha($dstImg, true);
                        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
                        imagepng($dstImg, __DIR__ . '/' . $logoPath);
                        imagedestroy($srcImg);
                        imagedestroy($dstImg);
                        $message = 'Logo subido y redimensionado correctamente.';
                        $type = 'success';
                        $logoExists = true;
                    }
                } else {
                    if (move_uploaded_file($tempFile, __DIR__ . '/' . $logoPath)) {
                        $message = 'Logo subido (sin redimensionar, extensión GD no disponible).';
                        $type = 'success';
                        $logoExists = true;
                    } else {
                        $message = 'Error al guardar el archivo.';
                        $type = 'danger';
                    }
                }
            } else {
                if (move_uploaded_file($tempFile, __DIR__ . '/' . $logoPath)) {
                    $message = 'Logo subido (sin redimensionar).';
                    $type = 'success';
                    $logoExists = true;
                } else {
                    $message = 'Error al guardar el archivo.';
                    $type = 'danger';
                }
            }
        }
    } else {
        $message = 'Selecciona un archivo de imagen.';
        $type = 'danger';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar Logo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index_nuevo.php"><i class="bi bi-cash-stack"></i> Caja Menor / Mayor</a>
        <div>
            <a class="btn btn-outline-light btn-sm" href="index_nuevo.php"><i class="bi bi-house"></i> Inicio</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div class="card shadow-sm" style="max-width:600px;margin:0 auto;">
        <div class="card-body">
            <h4 class="card-title mb-3"><i class="bi bi-image"></i> Logo de la empresa</h4>
            <?php if ($message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <div class="text-center mb-4 p-4 bg-light rounded" style="min-height:120px;display:flex;align-items:center;justify-content:center;">
                <?php if ($logoExists): ?>
                    <img src="<?= $logoPath ?>?t=<?= time() ?>" alt="Logo" style="max-width:300px;max-height:100px;">
                <?php else: ?>
                    <span class="text-muted"><i class="bi bi-building"></i> Sin logo — se mostrará el nombre de la empresa</span>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Subir nuevo logo</label>
                    <input class="form-control" type="file" name="logo" accept="image/*">
                    <small class="text-muted">Se redimensionará automáticamente a 300x100 px max. Formatos: JPG, PNG, GIF, WEBP.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Subir logo</button>
                <?php if ($logoExists): ?>
                    <button type="submit" name="eliminar" value="1" class="btn btn-outline-danger" onclick="return confirm('¿Eliminar el logo actual?')"><i class="bi bi-trash"></i> Eliminar logo</button>
                <?php endif; ?>
                <a href="index_nuevo.php" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
