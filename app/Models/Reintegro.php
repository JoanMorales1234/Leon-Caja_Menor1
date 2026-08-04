<?php

namespace App\Models;

class Reintegro
{
    public static function create($pdo, array $data)
    {
        $stmt = $pdo->prepare('INSERT INTO reintegros (caja_id, valor, descripcion, soporte, fecha_reintegro) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['caja_id'],
            $data['valor'],
            $data['descripcion'] ?: null,
            $data['soporte'] ?: null,
            $data['fecha'] ?: date('Y-m-d'),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM reintegros WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getCajaId($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT caja_id FROM reintegros WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['caja_id'] : null;
    }

    public static function update($pdo, $id, array $data)
    {
        $stmt = $pdo->prepare('UPDATE reintegros SET valor = ?, descripcion = ?, soporte = ?, fecha_reintegro = ? WHERE id = ?');
        $stmt->execute([
            $data['valor'],
            $data['descripcion'] ?: null,
            $data['soporte'] ?: null,
            $data['fecha'] ?: date('Y-m-d'),
            $id,
        ]);
    }

    public static function delete($pdo, $id)
    {
        $stmt = $pdo->prepare('DELETE FROM reintegros WHERE id = ?');
        $stmt->execute([$id]);
    }
}
