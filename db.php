<?php
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'caja_menor';

function getDb()
{
    global $pdo, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    try {
        if (!isset($pdo)) {
            throw new PDOException('No connection');
        }
        $pdo->query('SELECT 1');
    } catch (PDOException $e) {
        $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$DB_NAME`");
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS soportes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gasto_id INT NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
            archivo VARCHAR(255),
            descripcion VARCHAR(255),
            orden INT NOT NULL DEFAULT 0,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (gasto_id) REFERENCES gastos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        $count = $pdo->query("SELECT COUNT(*) FROM soportes")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("INSERT INTO soportes (gasto_id, tipo, archivo, descripcion, orden)
                SELECT id, tipo_soporte, soporte, NULL, 0 FROM gastos
                WHERE soporte IS NOT NULL AND soporte != ''");
        }
    } catch (PDOException $e) {
    }

    return $pdo;
}

$pdo = getDb();
