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
    return $pdo;
}

$pdo = getDb();
