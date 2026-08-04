<?php

namespace App\Models;

class Festivo
{
    public static function allActive($pdo)
    {
        return $pdo->query('SELECT nombre, fecha FROM festivos WHERE estado = "activo" ORDER BY fecha DESC')->fetchAll();
    }

    public static function loadHolidays($pdo)
    {
        $stmt = $pdo->query('SELECT fecha FROM festivos WHERE estado = "activo"');
        return array_column($stmt->fetchAll(), 'fecha');
    }

    public static function create($pdo, $nombre, $fecha)
    {
        $stmt = $pdo->prepare('INSERT INTO festivos (nombre, fecha) VALUES (?, ?)');
        $stmt->execute([$nombre, $fecha]);
    }
}
