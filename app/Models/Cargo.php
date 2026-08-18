<?php

namespace App\Models;

class Cargo
{
    public static function all($pdo)
    {
        return $pdo->query('SELECT * FROM cargos ORDER BY nombre')->fetchAll();
    }

    public static function activeSelect($pdo)
    {
        return $pdo->query('SELECT id, nombre FROM cargos WHERE estado = "activo" ORDER BY nombre ASC')->fetchAll();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM cargos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($pdo, $nombre)
    {
        $stmt = $pdo->prepare('INSERT INTO cargos (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
    }

    public static function update($pdo, $id, $nombre)
    {
        $stmt = $pdo->prepare('UPDATE cargos SET nombre = ? WHERE id = ?');
        $stmt->execute([$nombre, $id]);
    }

    public static function setEstado($pdo, $id, $estado)
    {
        $stmt = $pdo->prepare('UPDATE cargos SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
    }

    public static function delete($pdo, $id)
    {
        $stmt = $pdo->prepare('DELETE FROM cargos WHERE id = ?');
        $stmt->execute([$id]);
    }
}
