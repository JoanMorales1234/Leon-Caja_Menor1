<?php

namespace App\Models;

class Gasto
{
    public static function create($pdo, array $data)
    {
        $stmt = $pdo->prepare('INSERT INTO gastos (caja_id, empleado_id, proveedor_id, fecha_gasto, descripcion, valor, orden) VALUES (?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([
            $data['caja_id'],
            $data['empleado_id'] ?: null,
            $data['proveedor_id'] ?: null,
            $data['fecha_gasto'] ?: date('Y-m-d'),
            $data['descripcion'],
            $data['valor'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT g.*, c.tipo_caja FROM gastos g JOIN cajas c ON g.caja_id = c.id WHERE g.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getCajaId($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT caja_id FROM gastos WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['caja_id'] : null;
    }

    public static function update($pdo, $id, array $data)
    {
        $stmt = $pdo->prepare('UPDATE gastos SET empleado_id = ?, proveedor_id = ?, fecha_gasto = ?, descripcion = ?, valor = ? WHERE id = ?');
        $stmt->execute([
            $data['empleado_id'] ?: null,
            $data['proveedor_id'] ?: null,
            $data['fecha_gasto'],
            $data['descripcion'],
            $data['valor'],
            $id,
        ]);
    }

    public static function delete($pdo, $id)
    {
        $stmt = $pdo->prepare('DELETE FROM gastos WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function shiftOrder($pdo, $cajaId)
    {
        $pdo->prepare('UPDATE gastos SET orden = orden + 1 WHERE caja_id = ?')->execute([$cajaId]);
    }

    public static function reorder($pdo, $cajaId, array $orden)
    {
        $stmt = $pdo->prepare('UPDATE gastos SET orden = ? WHERE id = ? AND caja_id = ?');
        foreach ($orden as $index => $gastoId) {
            $stmt->execute([$index + 1, intval($gastoId), $cajaId]);
        }
    }

    public static function getSoportes($pdo, $gastoId)
    {
        $stmt = $pdo->prepare('SELECT * FROM soportes WHERE gasto_id = ? ORDER BY orden, id');
        $stmt->execute([$gastoId]);
        return $stmt->fetchAll();
    }

    public static function addSoporte($pdo, $gastoId, $tipo, $archivo, $descripcion = null, $orden = 0)
    {
        $stmt = $pdo->prepare('INSERT INTO soportes (gasto_id, tipo, archivo, descripcion, orden) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$gastoId, $tipo ?: 'otro', $archivo, $descripcion, $orden]);
    }

    public static function deleteSoportes($pdo, $gastoId, array $ids)
    {
        $stmt = $pdo->prepare('DELETE FROM soportes WHERE id = ? AND gasto_id = ?');
        foreach ($ids as $sid) {
            $stmt->execute([intval($sid), $gastoId]);
        }
    }

    public static function nextSoporteOrden($pdo, $gastoId)
    {
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(orden), -1) + 1 FROM soportes WHERE gasto_id = ?');
        $stmt->execute([$gastoId]);
        return (int)$stmt->fetchColumn();
    }
}
