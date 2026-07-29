<?php
require_once __DIR__ . '/db.php';
$pdo = getDb();
$message = null;
$type = 'success';

function getCargo($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM cargos WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'nuevo_cargo') {
                $stmt = $pdo->prepare('INSERT INTO cargos (nombre) VALUES (?)');
                $stmt->execute([$_POST['nombre']]);
                $message = 'Cargo creado correctamente.';
            }
            if ($_POST['action'] === 'editar_cargo') {
                $stmt = $pdo->prepare('UPDATE cargos SET nombre = ? WHERE id = ?');
                $stmt->execute([$_POST['nombre'], $_POST['id']]);
                $message = 'Cargo actualizado correctamente.';
            }
            if ($_POST['action'] === 'inactivar_cargo') {
                $stmt = $pdo->prepare('UPDATE cargos SET estado = "inactivo" WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Cargo inactivado correctamente.';
            }
            if ($_POST['action'] === 'eliminar_cargo') {
                $stmt = $pdo->prepare('DELETE FROM cargos WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Cargo eliminado permanentemente.';
            }
        }
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    $type = 'danger';
}

$cargos = $pdo->query('SELECT * FROM cargos ORDER BY nombre')->fetchAll();
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$viewId = isset($_GET['view']) ? intval($_GET['view']) : 0;
$editCargo = $editId ? getCargo($pdo, $editId) : null;
$viewCargo = $viewId ? getCargo($pdo, $viewId) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cargos - Caja Menor/Mayor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4">Administración de cargos</h1>
            <p class="text-muted mb-0">Agrega, edita y revisa roles internos de la empresa.</p>
        </div>
        <div>
            <a class="btn btn-secondary" href="index.php">Volver al inicio</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="accordion mb-4" id="accordionCargos">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNuevoCargo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNuevoCargo" aria-expanded="false" aria-controls="collapseNuevoCargo">
                    Nuevo cargo
                </button>
            </h2>
            <div id="collapseNuevoCargo" class="accordion-collapse collapse" aria-labelledby="headingNuevoCargo" data-bs-parent="#accordionCargos">
                <div class="accordion-body">
                    <form method="post">
                        <input type="hidden" name="action" value="nuevo_cargo">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del cargo</label>
                                <input class="form-control" name="nombre" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar cargo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($editCargo): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEditarCargo">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditarCargo" aria-expanded="true" aria-controls="collapseEditarCargo">
                        Editar cargo: <?= htmlspecialchars($editCargo['nombre']) ?>
                    </button>
                </h2>
                <div id="collapseEditarCargo" class="accordion-collapse collapse show" aria-labelledby="headingEditarCargo" data-bs-parent="#accordionCargos">
                    <div class="accordion-body">
                        <form method="post">
                            <input type="hidden" name="action" value="editar_cargo">
                            <input type="hidden" name="id" value="<?= $editCargo['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del cargo</label>
                                    <input class="form-control" name="nombre" value="<?= htmlspecialchars($editCargo['nombre']) ?>" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success">Actualizar cargo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($viewCargo): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingVerCargo">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVerCargo" aria-expanded="true" aria-controls="collapseVerCargo">
                        Detalles de cargo: <?= htmlspecialchars($viewCargo['nombre']) ?>
                    </button>
                </h2>
                <div id="collapseVerCargo" class="accordion-collapse collapse show" aria-labelledby="headingVerCargo" data-bs-parent="#accordionCargos">
                    <div class="accordion-body">
                        <dl class="row">
                            <dt class="col-sm-4">Nombre</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewCargo['nombre']) ?></dd>
                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewCargo['estado']) ?></dd>
                            <dt class="col-sm-4">Creado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewCargo['creado_en']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Lista de cargos</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cargos as $cargo): ?>
                            <tr>
                                <td><?= $cargo['id'] ?></td>
                                <td><?= htmlspecialchars($cargo['nombre']) ?></td>
                                <td><?= htmlspecialchars($cargo['estado']) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="cargos.php?view=<?= $cargo['id'] ?>">Ver</a>
                                    <a class="btn btn-sm btn-outline-success" href="cargos.php?edit=<?= $cargo['id'] ?>">Editar</a>
                                    <?php if ($cargo['estado'] === 'activo'): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este cargo? Ya no se podrá reactivar.')">
                                            <input type="hidden" name="action" value="inactivar_cargo">
                                            <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Inactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este cargo?')">
                                            <input type="hidden" name="action" value="eliminar_cargo">
                                            <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
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
