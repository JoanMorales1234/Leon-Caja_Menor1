<?php
require_once __DIR__ . '/db.php';
$pdo = getDb();

function loadHolidays($pdo) {
    $stmt = $pdo->query('SELECT fecha FROM festivos WHERE estado = "activo"');
    return array_column($stmt->fetchAll(), 'fecha');
}

function calculateCajaDate($today, $holidays) {
    $todayDate = new DateTime($today);
    if ($todayDate->format('w') === '0') {
        return null;
    }
    if ($todayDate->format('w') === '1') {
        $target = new DateTime($today);
        $target->modify('-1 day');
        if ($target->format('w') === '0') {
            $target->modify('-1 day');
        }
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

function recalculateCajaFinal($pdo, $cajaId) {
    $stmt = $pdo->prepare('SELECT valor_inicial FROM cajas WHERE id = ?');
    $stmt->execute([$cajaId]);
    $caja = $stmt->fetch();
    if (!$caja) {
        return;
    }
    $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) AS total FROM reintegros WHERE caja_id = ?');
    $stmt->execute([$cajaId]);
    $reintegros = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) AS total FROM gastos WHERE caja_id = ?');
    $stmt->execute([$cajaId]);
    $gastos = (float)$stmt->fetchColumn();

    $valorFinal = (float)$caja['valor_inicial'] + $reintegros - $gastos;
    $update = $pdo->prepare('UPDATE cajas SET valor_final = ? WHERE id = ?');
    $update->execute([$valorFinal, $cajaId]);
}

try {
    $cargos = ['Gerente', 'Sistemas', 'Logística', 'Contador'];
    foreach ($cargos as $cargo) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO cargos (nombre) VALUES (?)');
        $stmt->execute([$cargo]);
    }

    $empleados = [
        ['cedula' => '12345678', 'nombres' => 'Juan', 'apellidos' => 'Pérez', 'cargo' => 'Gerente', 'telefono' => '3001234567'],
        ['cedula' => '87654321', 'nombres' => 'María', 'apellidos' => 'González', 'cargo' => 'Contador', 'telefono' => '3007654321'],
        ['cedula' => '10293847', 'nombres' => 'Carlos', 'apellidos' => 'Ramírez', 'cargo' => 'Sistemas', 'telefono' => '3009876543'],
    ];
    foreach ($empleados as $empleado) {
        $stmt = $pdo->prepare('SELECT id FROM cargos WHERE nombre = ? LIMIT 1');
        $stmt->execute([$empleado['cargo']]);
        $cargoId = $stmt->fetchColumn();
        $stmt = $pdo->prepare('INSERT IGNORE INTO empleados (cedula, nombres, apellidos, cargo_id, telefono) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$empleado['cedula'], $empleado['nombres'], $empleado['apellidos'], $cargoId, $empleado['telefono']]);
    }

    $proveedores = [
        ['nit' => '900123456', 'nombre' => 'Distribuciones ABC', 'telefono' => '3012345678', 'direccion' => 'Calle 1 # 2-3'],
        ['nit' => '800987654', 'nombre' => 'Servicios XYZ', 'telefono' => '3019876543', 'direccion' => 'Carrera 4 # 5-6'],
    ];
    foreach ($proveedores as $proveedor) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO proveedores (nit, nombre, telefono, direccion) VALUES (?, ?, ?, ?)');
        $stmt->execute([$proveedor['nit'], $proveedor['nombre'], $proveedor['telefono'], $proveedor['direccion']]);
    }

    $festivos = [
        ['nombre' => 'Día del Trabajo', 'fecha' => '2026-05-01'],
        ['nombre' => 'Independencia', 'fecha' => '2026-07-20'],
    ];
    foreach ($festivos as $festivo) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO festivos (nombre, fecha) VALUES (?, ?)');
        $stmt->execute([$festivo['nombre'], $festivo['fecha']]);
    }

    $holidays = loadHolidays($pdo);
    $fechaCaja = calculateCajaDate(date('Y-m-d'), $holidays);
    if ($fechaCaja) {
        foreach (['menor', 'mayor'] as $tipo) {
            $stmt = $pdo->prepare('SELECT id FROM cajas WHERE tipo_caja = ? AND estado = "abierta" LIMIT 1');
            $stmt->execute([$tipo]);
            $cajaId = $stmt->fetchColumn();
            if (!$cajaId) {
                $valorInicial = getLastClosedSaldo($pdo, $tipo);
                $stmt = $pdo->prepare('INSERT INTO cajas (tipo_caja, fecha_caja, valor_inicial) VALUES (?, ?, ?)');
                $stmt->execute([$tipo, $fechaCaja, $valorInicial]);
                $cajaId = $pdo->lastInsertId();
                recalculateCajaFinal($pdo, $cajaId);
            }
            if ($tipo === 'mayor') {
                $stmt = $pdo->prepare('INSERT INTO reintegros (caja_id, valor, descripcion, soporte, fecha_reintegro) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$cajaId, 232976.00, 'Reintegro de ejemplo para caja mayor', 'Ticket 232976', date('Y-m-d')]);
                recalculateCajaFinal($pdo, $cajaId);
            }
        }
    }

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Datos de ejemplo cargados</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-success"><h4 class="alert-heading">Datos de ejemplo cargados</h4><p>Se cargaron cargos, empleados, proveedores, festivos y un reintegro de 232.976,00 en la caja mayor abierta.</p><hr><p class="mb-0"><a href="index.php">Volver al inicio</a></p></div></div></body></html>';
} catch (Exception $e) {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error al cargar datos</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><h4 class="alert-heading">Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p><hr><p class="mb-0"><a href="index.php">Volver al inicio</a></p></div></div></body></html>';
}
