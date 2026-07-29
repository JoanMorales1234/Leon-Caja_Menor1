<?php
require_once __DIR__ . '/db.php';
$pdo = getDb();
$message = null;
$type = 'success';

function getProveedor($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'nuevo_proveedor') {
                $stmt = $pdo->prepare('INSERT INTO proveedores (nit, nombre, telefono, direccion) VALUES (?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['nit'],
                    $_POST['nombre'],
                    $_POST['telefono'] ?: null,
                    $_POST['direccion'] ?: null,
                ]);
                $message = 'Proveedor creado correctamente.';
            }
            if ($_POST['action'] === 'editar_proveedor') {
                $stmt = $pdo->prepare('UPDATE proveedores SET nit = ?, nombre = ?, telefono = ?, direccion = ? WHERE id = ?');
                $stmt->execute([
                    $_POST['nit'],
                    $_POST['nombre'],
                    $_POST['telefono'] ?: null,
                    $_POST['direccion'] ?: null,
                    $_POST['id'],
                ]);
                $message = 'Proveedor actualizado correctamente.';
            }
            if ($_POST['action'] === 'inactivar_proveedor') {
                $stmt = $pdo->prepare('UPDATE proveedores SET estado = "inactivo" WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Proveedor inactivado correctamente.';
            }
        }
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    $type = 'danger';
}

$proveedores = $pdo->query('SELECT * FROM proveedores ORDER BY nombre')->fetchAll();
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$viewId = isset($_GET['view']) ? intval($_GET['view']) : 0;
$editProveedor = $editId ? getProveedor($pdo, $editId) : null;
$viewProveedor = $viewId ? getProveedor($pdo, $viewId) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores - Caja Menor/Mayor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4">Administración de proveedores</h1>
            <p class="text-muted mb-0">Agrega, edita y revisa detalles de los proveedores.</p>
        </div>
        <div>
            <a class="btn btn-secondary" href="index.php">Volver al inicio</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="accordion mb-4" id="accordionProveedores">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNuevoProveedor">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNuevoProveedor" aria-expanded="false" aria-controls="collapseNuevoProveedor">
                    Nuevo proveedor
                </button>
            </h2>
            <div id="collapseNuevoProveedor" class="accordion-collapse collapse" aria-labelledby="headingNuevoProveedor" data-bs-parent="#accordionProveedores">
                <div class="accordion-body">
                    <form method="post">
                        <input type="hidden" name="action" value="nuevo_proveedor">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">NIT</label>
                                <input class="form-control" name="nit" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono</label>
                                <input class="form-control" name="telefono">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección</label>
                                <input class="form-control" name="direccion">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar proveedor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($editProveedor): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEditarProveedor">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditarProveedor" aria-expanded="true" aria-controls="collapseEditarProveedor">
                        Editar proveedor: <?= htmlspecialchars($editProveedor['nombre']) ?>
                    </button>
                </h2>
                <div id="collapseEditarProveedor" class="accordion-collapse collapse show" aria-labelledby="headingEditarProveedor" data-bs-parent="#accordionProveedores">
                    <div class="accordion-body">
                        <form method="post">
                            <input type="hidden" name="action" value="editar_proveedor">
                            <input type="hidden" name="id" value="<?= $editProveedor['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">NIT</label>
                                    <input class="form-control" name="nit" value="<?= htmlspecialchars($editProveedor['nit']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre</label>
                                    <input class="form-control" name="nombre" value="<?= htmlspecialchars($editProveedor['nombre']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono</label>
                                    <input class="form-control" name="telefono" value="<?= htmlspecialchars($editProveedor['telefono']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección</label>
                                    <input class="form-control" name="direccion" value="<?= htmlspecialchars($editProveedor['direccion']) ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success">Actualizar proveedor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($viewProveedor): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingVerProveedor">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVerProveedor" aria-expanded="true" aria-controls="collapseVerProveedor">
                        Detalles de proveedor: <?= htmlspecialchars($viewProveedor['nombre']) ?>
                    </button>
                </h2>
                <div id="collapseVerProveedor" class="accordion-collapse collapse show" aria-labelledby="headingVerProveedor" data-bs-parent="#accordionProveedores">
                    <div class="accordion-body">
                        <dl class="row">
                            <dt class="col-sm-4">NIT</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['nit']) ?></dd>
                            <dt class="col-sm-4">Nombre</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['nombre']) ?></dd>
                            <dt class="col-sm-4">Teléfono</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['telefono'] ?: '-') ?></dd>
                            <dt class="col-sm-4">Dirección</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['direccion'] ?: '-') ?></dd>
                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['estado']) ?></dd>
                            <dt class="col-sm-4">Creado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewProveedor['creado_en']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Lista de proveedores</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NIT</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td><?= $proveedor['id'] ?></td>
                                <td><?= htmlspecialchars($proveedor['nit']) ?></td>
                                <td><?= htmlspecialchars($proveedor['nombre']) ?></td>
                                <td><?= htmlspecialchars($proveedor['telefono'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($proveedor['direccion'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($proveedor['estado']) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="proveedores.php?view=<?= $proveedor['id'] ?>">Ver</a>
                                    <a class="btn btn-sm btn-outline-success" href="proveedores.php?edit=<?= $proveedor['id'] ?>">Editar</a>
                                    <?php if ($proveedor['estado'] === 'activo'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="inactivar_proveedor">
                                            <input type="hidden" name="id" value="<?= $proveedor['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Inactivar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
