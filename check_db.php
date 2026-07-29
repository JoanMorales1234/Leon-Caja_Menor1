<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

echo "=== CAJAS ===\n";
$stmt = $pdo->query('SELECT id, tipo_caja, fecha_caja, estado, valor_inicial FROM cajas ORDER BY id DESC');
foreach ($stmt->fetchAll() as $c) {
    echo 'ID:' . $c['id'] . ' Tipo:' . $c['tipo_caja'] . ' Fecha:' . $c['fecha_caja'] . ' Estado:' . $c['estado'] . ' VI:' . $c['valor_inicial'] . "\n";
}

echo "\n=== GASTOS ===\n";
$stmt = $pdo->query('SELECT id, caja_id, descripcion, valor FROM gastos ORDER BY id DESC');
foreach ($stmt->fetchAll() as $g) {
    echo 'ID:' . $g['id'] . ' CajaID:' . $g['caja_id'] . ' Desc:' . $g['descripcion'] . ' Valor:' . $g['valor'] . "\n";
}

echo "\n=== getCajaDetails(menor) ===\n";
$c = getCajaDetails($pdo, 'menor');
if ($c) {
    echo 'OK - ID:' . $c['id'] . ' estado:' . $c['estado'] . "\n";
} else {
    echo "NULL - no hay caja menor abierta\n";
}

echo "\n=== getOpenCaja(menor) ===\n";
$c = getOpenCaja($pdo, 'menor');
if ($c) {
    echo 'OK - ID:' . $c['id'] . ' estado:' . $c['estado'] . "\n";
} else {
    echo "NULL - no hay caja menor abierta via getOpenCaja\n";
}
