<?php

namespace App\Core;

class Database
{
    private static $pdo = null;

    public static function getPdo()
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
        }
        return self::$pdo;
    }

    private static function connect()
    {
        $host = '127.0.0.1';
        $user = 'root';
        $pass = '';
        $name = 'caja_menor';

        if (self::$pdo instanceof \PDO) {
            try {
                self::$pdo->query('SELECT 1');
                return self::$pdo;
            } catch (\PDOException $e) {
                self::$pdo = null;
            }
        }

        $pdo = new \PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$name`");
        self::$pdo = $pdo;

        try {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS soportes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gasto_id INT NOT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
                archivo VARCHAR(255),
                descripcion VARCHAR(255),
                orden INT NOT NULL DEFAULT 0,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (gasto_id) REFERENCES gastos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");

            $count = self::$pdo->query("SELECT COUNT(*) FROM soportes")->fetchColumn();
            if ($count == 0) {
                self::$pdo->exec("INSERT INTO soportes (gasto_id, tipo, archivo, descripcion, orden)
                    SELECT id, tipo_soporte, soporte, NULL, 0 FROM gastos
                    WHERE soporte IS NOT NULL AND soporte != ''");
            }
        } catch (\PDOException $e) {
        }

        return self::$pdo;
    }
}
