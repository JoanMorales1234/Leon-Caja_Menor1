<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
$pdo = getDb();

echo "<h2>Debug - Caja Menor</h2>";
echo "<pre>";

echo "<h3>1. Sesión POST recibida:</h3>";
print_r($_POST);

echo "<h3>2. Sesión FILES recibida:</h3>";
print_r($_FILES);

echo "<h3>3. getCajaDetails('menor'):</h3>";
$cajaMenor = getCajaDetails($pdo, 'menor');
if ($cajaMenor) {
    echo "ID: " . $cajaMenor['id'] . "\n";
    echo "Tipo: " . $cajaMenor['tipo_caja'] . "\n";
    echo "Estado: " . $cajaMenor['estado'] . "\n";
    echo "Fecha: " . $cajaMenor['fecha_caja'] . "\n";
    echo "Valor Inicial: " . $cajaMenor['valor_inicial'] . "\n";
    echo "Valor Final: " . $cajaMenor['valor_final'] . "\n";
    echo "Total Gastos: " . $cajaMenor['total_gastos'] . "\n";
    echo "Total Reintegros: " . $cajaMenor['total_reintegros'] . "\n";
    echo "Saldo Actual: " . $cajaMenor['saldo_actual'] . "\n";
} else {
    echo "NO HAY CAJA ABIERTA\n";
}

echo "<h3>4. getOpenCaja('menor'):</h3>";
$openCaja = getOpenCaja($pdo, 'menor');
if ($openCaja) {
    echo "ID: " . $openCaja['id'] . "\n";
    echo "Estado: " . $openCaja['estado'] . "\n";
} else {
    echo "NO HAY CAJA ABIERTA VIA getOpenCaja\n";
}

echo "<h3>5. Todas las cajas:</h3>";
$stmt = $pdo->query('SELECT id, tipo_caja, fecha_caja, estado FROM cajas ORDER BY id DESC');
$cajas = $stmt->fetchAll();
foreach ($cajas as $c) {
    echo "ID:{$c['id']} Tipo:{$c['tipo_caja']} Fecha:{$c['fecha_caja']} Estado:{$c['estado']}\n";
}

echo "<h3>6. Todos los gastos:</h3>";
$stmt = $pdo->query('SELECT id, caja_id, descripcion, valor FROM gastos ORDER BY id DESC');
$gastos = $stmt->fetchAll();
foreach ($gastos as $g) {
    echo "ID:{$g['id']} CajaID:{$g['caja_id']} Desc:{$g['descripcion']} Valor:{$g['valor']}\n";
}

echo "<h3>7. helper uploads path:</h3>";
echo "__DIR__ de helpers.php: " . __DIR__ . "\n";
echo "Ruta uploads: " . (__DIR__ . '/uploads') . "\n";
echo "Existe? " . (is_dir(__DIR__ . '/uploads') ? 'SI' : 'NO') . "\n";

echo "</pre>";
