<?php
require_once __DIR__ . '/db.php';
try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new Exception('No se pudo leer schema.sql');
    }
    $pdo->exec($sql);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Instalación completada</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-success"><h4 class="alert-heading">Instalación completada</h4><p>La base de datos y las tablas se han creado correctamente.</p><hr><p class="mb-0">Visita <a href="index.php">index.php</a> para comenzar.</p></div></div></body></html>';
} catch (Exception $e) {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error de instalación</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><h4 class="alert-heading">Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div></div></body></html>';
}
