<?php

namespace App\Models;

class Empleado
{
    public static function all($pdo)
    {
        return $pdo->query('SELECT e.*, c.nombre AS cargo_nombre FROM empleados e LEFT JOIN cargos c ON e.cargo_id = c.id ORDER BY e.apellidos, e.nombres')->fetchAll();
    }

    public static function activeSelect($pdo)
    {
        return $pdo->query('SELECT id, cedula, CONCAT(nombres, " ", apellidos) AS nombre FROM empleados WHERE estado = "activo" ORDER BY nombres ASC')->fetchAll();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT e.*, c.nombre AS cargo_nombre FROM empleados e LEFT JOIN cargos c ON e.cargo_id = c.id WHERE e.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($pdo, array $data)
    {
        $stmt = $pdo->prepare('INSERT INTO empleados (cedula, nombres, apellidos, cargo_id, telefono) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['cedula'], $data['nombres'], $data['apellidos'], $data['cargo_id'] ?: null, $data['telefono'] ?: null]);
    }

    public static function update($pdo, $id, array $data)
    {
        $stmt = $pdo->prepare('UPDATE empleados SET cedula = ?, nombres = ?, apellidos = ?, cargo_id = ?, telefono = ? WHERE id = ?');
        $stmt->execute([$data['cedula'], $data['nombres'], $data['apellidos'], $data['cargo_id'] ?: null, $data['telefono'] ?: null, $id]);
    }

    public static function setEstado($pdo, $id, $estado)
    {
        $stmt = $pdo->prepare('UPDATE empleados SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
    }

    public static function delete($pdo, $id)
    {
        $stmt = $pdo->prepare('DELETE FROM empleados WHERE id = ?');
        $stmt->execute([$id]);
    }
}
