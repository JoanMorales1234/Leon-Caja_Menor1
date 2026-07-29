<?php
require_once __DIR__ . '/db.php';
$pdo = getDb();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = ['gastos', 'reintegros', 'cajas', 'festivos', 'role_permissions', 'users', 'empleados', 'proveedores', 'cargos', 'permissions', 'roles'];
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `$table`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $pdo->exec("INSERT INTO roles (nombre, descripcion) VALUES
            ('admin', 'Administrador del sistema'),
            ('aprendiz', 'Usuario aprendiz'),
            ('facturador', 'Usuario facturador'),
            ('contador', 'Usuario contador')");

        $pdo->exec("INSERT INTO permissions (nombre, descripcion) VALUES
            ('crear_gasto', 'Crear gastos'),
            ('editar_gasto', 'Editar gastos'),
            ('exportar_excel', 'Exportar a Excel'),
            ('cerrar_caja', 'Cerrar caja')");

        $message = 'Base de datos restablecida correctamente. Todos los datos fueron eliminados y los contadores reiniciados.';
    } catch (Exception $e) {
        $error = 'Error al restablecer: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer base de datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:500px">
        <div class="card-body text-center">
            <h4 class="card-title text-danger"><i class="bi bi-exclamation-triangle"></i> Restablecer base de datos</h4>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <a class="btn btn-primary" href="index_nuevo.php">Ir al inicio</a>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <a class="btn btn-secondary" href="reset_db.php">Intentar de nuevo</a>
            <?php else: ?>
                <p class="text-muted">Esto <strong>eliminará todos los datos</strong> (gastos, cajas, empleados, proveedores, etc.) y reiniciará los contadores de ID desde 1.</p>
                <p class="text-muted">Los roles y permisos se volverán a crear.</p>
                <form method="post">
                    <input type="hidden" name="confirmar" value="si">
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('¿Estás seguro? Se borrarán TODOS los datos.')">
                        <i class="bi bi-trash"></i> Sí, restablecer todo
                    </button>
                </form>
                <a class="btn btn-secondary mt-2 w-100" href="index_nuevo.php">Cancelar</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
