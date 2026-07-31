<?php
require_once __DIR__ . '/db.php';
$pdo = getDb();
$message = null;
$type = 'success';

function getCargos($pdo) {
    return $pdo->query('SELECT id, nombre FROM cargos WHERE estado = "activo" ORDER BY nombre ASC')->fetchAll();
}

function getEmpleado($pdo, $id) {
    $stmt = $pdo->prepare('SELECT e.*, c.nombre AS cargo_nombre FROM empleados e LEFT JOIN cargos c ON e.cargo_id = c.id WHERE e.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'nuevo_empleado') {
                $stmt = $pdo->prepare('INSERT INTO empleados (cedula, nombres, apellidos, cargo_id, telefono) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['cedula'],
                    $_POST['nombres'],
                    $_POST['apellidos'],
                    $_POST['cargo_id'] ?: null,
                    $_POST['telefono'] ?: null,
                ]);
                $message = 'Empleado creado correctamente.';
            }
            if ($_POST['action'] === 'editar_empleado') {
                $stmt = $pdo->prepare('UPDATE empleados SET cedula = ?, nombres = ?, apellidos = ?, cargo_id = ?, telefono = ? WHERE id = ?');
                $stmt->execute([
                    $_POST['cedula'],
                    $_POST['nombres'],
                    $_POST['apellidos'],
                    $_POST['cargo_id'] ?: null,
                    $_POST['telefono'] ?: null,
                    $_POST['id'],
                ]);
                $message = 'Empleado actualizado correctamente.';
            }
            if ($_POST['action'] === 'inactivar_empleado') {
                $stmt = $pdo->prepare('UPDATE empleados SET estado = "inactivo" WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Empleado inactivado correctamente.';
            }
            if ($_POST['action'] === 'activar_empleado') {
                $stmt = $pdo->prepare('UPDATE empleados SET estado = "activo" WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Empleado activado correctamente.';
            }
            if ($_POST['action'] === 'eliminar_empleado') {
                $stmt = $pdo->prepare('DELETE FROM empleados WHERE id = ?');
                $stmt->execute([$_POST['id']]);
                $message = 'Empleado eliminado permanentemente.';
            }
        }
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    $type = 'danger';
}

$empleados = $pdo->query('SELECT e.*, c.nombre AS cargo_nombre FROM empleados e LEFT JOIN cargos c ON e.cargo_id = c.id ORDER BY e.apellidos, e.nombres')->fetchAll();
$cargos = getCargos($pdo);
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$viewId = isset($_GET['view']) ? intval($_GET['view']) : 0;
$editEmpleado = $editId ? getEmpleado($pdo, $editId) : null;
$viewEmpleado = $viewId ? getEmpleado($pdo, $viewId) : null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Empleados - Caja Menor/Mayor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4">Administración de empleados</h1>
            <p class="text-muted mb-0">Agrega, edita y revisa detalles de los empleados.</p>
        </div>
        <div>
            <a class="btn btn-secondary" href="index.php">Volver al inicio</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="accordion mb-4" id="accordionEmpleados">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingNuevoEmpleado">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNuevoEmpleado" aria-expanded="false" aria-controls="collapseNuevoEmpleado">
                    Nuevo empleado
                </button>
            </h2>
            <div id="collapseNuevoEmpleado" class="accordion-collapse collapse" aria-labelledby="headingNuevoEmpleado" data-bs-parent="#accordionEmpleados">
                <div class="accordion-body">
                    <form method="post">
                        <input type="hidden" name="action" value="nuevo_empleado">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cédula</label>
                                <input class="form-control" name="cedula" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombres</label>
                                <input class="form-control" name="nombres" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apellidos</label>
                                <input class="form-control" name="apellidos" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cargo</label>
                                <select class="form-select" name="cargo_id">
                                    <option value="">Sin cargo</option>
                                    <?php foreach ($cargos as $cargo): ?>
                                        <option value="<?= $cargo['id'] ?>"><?= htmlspecialchars($cargo['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input class="form-control" name="telefono">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Guardar empleado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php if ($editEmpleado): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEditarEmpleado">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditarEmpleado" aria-expanded="true" aria-controls="collapseEditarEmpleado">
                        Editar empleado: <?= htmlspecialchars($editEmpleado['nombres'] . ' ' . $editEmpleado['apellidos']) ?>
                    </button>
                </h2>
                <div id="collapseEditarEmpleado" class="accordion-collapse collapse show" aria-labelledby="headingEditarEmpleado" data-bs-parent="#accordionEmpleados">
                    <div class="accordion-body">
                        <form method="post">
                            <input type="hidden" name="action" value="editar_empleado">
                            <input type="hidden" name="id" value="<?= $editEmpleado['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Cédula</label>
                                    <input class="form-control" name="cedula" value="<?= htmlspecialchars($editEmpleado['cedula']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombres</label>
                                    <input class="form-control" name="nombres" value="<?= htmlspecialchars($editEmpleado['nombres']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Apellidos</label>
                                    <input class="form-control" name="apellidos" value="<?= htmlspecialchars($editEmpleado['apellidos']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cargo</label>
                                    <select class="form-select" name="cargo_id">
                                        <option value="">Sin cargo</option>
                                        <?php foreach ($cargos as $cargo): ?>
                                            <option value="<?= $cargo['id'] ?>" <?= $cargo['id'] === (int)$editEmpleado['cargo_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cargo['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input class="form-control" name="telefono" value="<?= htmlspecialchars($editEmpleado['telefono']) ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success">Actualizar empleado</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($viewEmpleado): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingVerEmpleado">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVerEmpleado" aria-expanded="true" aria-controls="collapseVerEmpleado">
                        Detalles de empleado: <?= htmlspecialchars($viewEmpleado['nombres'] . ' ' . $viewEmpleado['apellidos']) ?>
                    </button>
                </h2>
                <div id="collapseVerEmpleado" class="accordion-collapse collapse show" aria-labelledby="headingVerEmpleado" data-bs-parent="#accordionEmpleados">
                    <div class="accordion-body">
                        <dl class="row">
                            <dt class="col-sm-4">Cédula</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['cedula']) ?></dd>
                            <dt class="col-sm-4">Nombre completo</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['nombres'] . ' ' . $viewEmpleado['apellidos']) ?></dd>
                            <dt class="col-sm-4">Cargo</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['cargo_nombre'] ?: 'Sin cargo') ?></dd>
                            <dt class="col-sm-4">Teléfono</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['telefono'] ?: '-') ?></dd>
                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['estado']) ?></dd>
                            <dt class="col-sm-4">Creado</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($viewEmpleado['creado_en']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Lista de empleados</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cédula</th>
                            <th>Empleado</th>
                            <th>Cargo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                            <tr>
                                <td><?= $empleado['id'] ?></td>
                                <td><?= htmlspecialchars($empleado['cedula']) ?></td>
                                <td><?= htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']) ?></td>
                                <td><?= htmlspecialchars($empleado['cargo_nombre'] ?: 'Sin cargo') ?></td>
                                <td><?= htmlspecialchars($empleado['telefono'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($empleado['estado']) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="empleados.php?view=<?= $empleado['id'] ?>">Ver</a>
                                    <a class="btn btn-sm btn-outline-success" href="empleados.php?edit=<?= $empleado['id'] ?>">Editar</a>
                                    <?php if ($empleado['estado'] === 'activo'): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Inactivar este empleado?')">
                                            <input type="hidden" name="action" value="inactivar_empleado">
                                            <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Inactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Activar este empleado?')">
                                            <input type="hidden" name="action" value="activar_empleado">
                                            <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este empleado?')">
                                            <input type="hidden" name="action" value="eliminar_empleado">
                                            <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
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
