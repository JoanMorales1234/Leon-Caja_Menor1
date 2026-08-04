<?php

namespace App\Models;

class Proveedor
{
    public static function all($pdo)
    {
        return $pdo->query('SELECT * FROM proveedores ORDER BY nombre')->fetchAll();
    }

    public static function activeSelect($pdo)
    {
        return $pdo->query('SELECT id, nit, nombre FROM proveedores WHERE estado = "activo" ORDER BY nombre ASC')->fetchAll();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($pdo, array $data)
    {
        $stmt = $pdo->prepare('INSERT INTO proveedores (nit, nombre, telefono, direccion) VALUES (?, ?, ?, ?)');
        $stmt->execute([$data['nit'], $data['nombre'], $data['telefono'] ?: null, $data['direccion'] ?: null]);
    }

    public static function update($pdo, $id, array $data)
    {
        $stmt = $pdo->prepare('UPDATE proveedores SET nit = ?, nombre = ?, telefono = ?, direccion = ? WHERE id = ?');
        $stmt->execute([$data['nit'], $data['nombre'], $data['telefono'] ?: null, $data['direccion'] ?: null, $id]);
    }

    public static function setEstado($pdo, $id, $estado)
    {
        $stmt = $pdo->prepare('UPDATE proveedores SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
    }

    public static function delete($pdo, $id)
    {
        $stmt = $pdo->prepare('DELETE FROM proveedores WHERE id = ?');
        $stmt->execute([$id]);
    }
}
