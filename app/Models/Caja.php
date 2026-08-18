<?php

namespace App\Models;

class Caja
{
    public static function getLastClosedSaldo($pdo, $tipo)
    {
        $stmt = $pdo->prepare('SELECT valor_final FROM cajas WHERE tipo_caja = ? AND estado = "cerrada" ORDER BY fecha_caja DESC LIMIT 1');
        $stmt->execute([$tipo]);
        $row = $stmt->fetch();
        return $row ? (float)$row['valor_final'] : 0.00;
    }

    public static function getOpenCaja($pdo, $tipo)
    {
        $stmt = $pdo->prepare('SELECT * FROM cajas WHERE tipo_caja = ? AND estado = "abierta" ORDER BY fecha_caja DESC LIMIT 1');
        $stmt->execute([$tipo]);
        return $stmt->fetch();
    }

    public static function getOpenCajas($pdo)
    {
        return $pdo->query('SELECT * FROM cajas WHERE estado = "abierta" ORDER BY fecha_caja DESC')->fetchAll();
    }

    public static function getOpenCajaDetails($pdo)
    {
        $cajas = self::getOpenCajas($pdo);
        foreach ($cajas as $i => $caja) {
            $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM gastos WHERE caja_id = ?');
            $stmt->execute([$caja['id']]);
            $cajas[$i]['total_gastos'] = (float)$stmt->fetchColumn();
            $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM reintegros WHERE caja_id = ?');
            $stmt->execute([$caja['id']]);
            $cajas[$i]['total_reintegros'] = (float)$stmt->fetchColumn();
            $cajas[$i]['saldo_actual'] = (float)$caja['valor_inicial'] + $cajas[$i]['total_reintegros'] - $cajas[$i]['total_gastos'];
        }
        return $cajas;
    }

    public static function recalculateCajaFinal($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('SELECT valor_inicial FROM cajas WHERE id = ?');
        $stmt->execute([$cajaId]);
        $caja = $stmt->fetch();
        if (!$caja) return;
        $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM reintegros WHERE caja_id = ?');
        $stmt->execute([$cajaId]);
        $reintegros = (float)$stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT IFNULL(SUM(valor), 0) FROM gastos WHERE caja_id = ?');
        $stmt->execute([$cajaId]);
        $gastos = (float)$stmt->fetchColumn();
        $valorFinal = (float)$caja['valor_inicial'] + $reintegros - $gastos;
        $stmt = $pdo->prepare('UPDATE cajas SET valor_final = ? WHERE id = ?');
        $stmt->execute([$valorFinal, $cajaId]);
    }

    public static function reconcileFrom($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('SELECT tipo_caja, fecha_caja FROM cajas WHERE id = ?');
        $stmt->execute([$cajaId]);
        $origen = $stmt->fetch();
        if (!$origen) return;

        $rows = $pdo->prepare('SELECT * FROM cajas WHERE tipo_caja = ? AND fecha_caja >= ? ORDER BY fecha_caja ASC, id ASC');
        $rows->execute([$origen['tipo_caja'], $origen['fecha_caja']]);
        $prevFinal = null;
        $primero = true;
        foreach ($rows as $caja) {
            if (!$primero) {
                if ((float)$caja['valor_inicial'] !== (float)$prevFinal) {
                    $u = $pdo->prepare('UPDATE cajas SET valor_inicial = ? WHERE id = ?');
                    $u->execute([$prevFinal, $caja['id']]);
                    $caja['valor_inicial'] = $prevFinal;
                }
            }
            self::recalculateCajaFinal($pdo, $caja['id']);
            $stmt = $pdo->prepare('SELECT valor_final FROM cajas WHERE id = ?');
            $stmt->execute([$caja['id']]);
            $prevFinal = (float)$stmt->fetchColumn();
            $primero = false;
        }
    }

    public static function closeCaja($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('SELECT id FROM cajas WHERE id = ? AND estado = "abierta"');
        $stmt->execute([$cajaId]);
        if (!$stmt->fetch()) throw new \Exception('Caja no encontrada o ya está cerrada.');
        self::recalculateCajaFinal($pdo, $cajaId);
        $stmt = $pdo->prepare('UPDATE cajas SET estado = "cerrada", fecha_cierre = NOW() WHERE id = ?');
        $stmt->execute([$cajaId]);
    }

    public static function getCajaDetails($pdo, $tipo)
    {
        $stmt = $pdo->prepare('SELECT c.*,
            IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
            IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
            FROM cajas c WHERE c.tipo_caja = ? AND c.estado = "abierta" ORDER BY c.fecha_caja DESC LIMIT 1');
        $stmt->execute([$tipo]);
        $caja = $stmt->fetch();
        if ($caja) $caja['saldo_actual'] = (float)$caja['valor_inicial'] + (float)$caja['total_reintegros'] - (float)$caja['total_gastos'];
        return $caja;
    }

    public static function getCajaMovements($pdo, $cajaId)
    {
        $gastos = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, p.nombre AS proveedor_nombre
            FROM gastos g
            LEFT JOIN empleados e ON g.empleado_id = e.id
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.orden ASC, g.id DESC');
        $gastos->execute([$cajaId]);
        $gastos = $gastos->fetchAll();
        $soportesStmt = $pdo->prepare('SELECT * FROM soportes WHERE gasto_id = ? ORDER BY orden, id');
        foreach ($gastos as &$gasto) {
            $soportesStmt->execute([$gasto['id']]);
            $gasto['soportes'] = $soportesStmt->fetchAll();
        }
        unset($gasto);
        $reintegros = $pdo->prepare('SELECT * FROM reintegros WHERE caja_id = ? ORDER BY creado_en DESC');
        $reintegros->execute([$cajaId]);
        return ['gastos' => $gastos, 'reintegros' => $reintegros->fetchAll()];
    }

    public static function getMovements($pdo)
    {
        $gastos = $pdo->query('SELECT g.*, c.tipo_caja, c.fecha_caja FROM gastos g JOIN cajas c ON g.caja_id = c.id ORDER BY g.creado_en DESC LIMIT 10')->fetchAll();
        $reintegros = $pdo->query('SELECT r.*, c.tipo_caja, c.fecha_caja FROM reintegros r JOIN cajas c ON r.caja_id = c.id ORDER BY r.creado_en DESC LIMIT 10')->fetchAll();
        return ['gastos' => $gastos, 'reintegros' => $reintegros];
    }

    public static function getBoxesSummary($pdo, $tipo)
    {
        $stmt = $pdo->prepare('SELECT c.*, IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros, IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos FROM cajas c WHERE c.tipo_caja = ? ORDER BY c.fecha_caja DESC LIMIT 1');
        $stmt->execute([$tipo]);
        return $stmt->fetch();
    }

    public static function findById($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT c.*,
            IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
            IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
            FROM cajas c WHERE c.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function existsByDate($pdo, $tipo, $fecha)
    {
        $stmt = $pdo->prepare('SELECT id FROM cajas WHERE tipo_caja = ? AND fecha_caja = ?');
        $stmt->execute([$tipo, $fecha]);
        return $stmt->fetch() ? true : false;
    }

    public static function create($pdo, $tipo, $fechaCaja, $valorInicial)
    {
        $stmt = $pdo->prepare('INSERT INTO cajas (tipo_caja, fecha_caja, valor_inicial) VALUES (?, ?, ?)');
        $stmt->execute([$tipo, $fechaCaja, $valorInicial]);
        $id = (int)$pdo->lastInsertId();
        self::recalculateCajaFinal($pdo, $id);
        return $id;
    }

    public static function getGastosWithDetails($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, ca.nombre AS cargo, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
            FROM gastos g
            LEFT JOIN empleados e ON g.empleado_id = e.id
            LEFT JOIN cargos ca ON e.cargo_id = ca.id
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.orden ASC, g.id ASC');
        $stmt->execute([$cajaId]);
        return $stmt->fetchAll();
    }

    public static function getNota($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('SELECT nota FROM cajas WHERE id = ?');
        $stmt->execute([$cajaId]);
        $row = $stmt->fetch();
        return $row ? (string)$row['nota'] : '';
    }

    public static function saveNota($pdo, $cajaId, $nota)
    {
        $stmt = $pdo->prepare('UPDATE cajas SET nota = ? WHERE id = ?');
        $stmt->execute([$nota, $cajaId]);
    }

    public static function clearNota($pdo, $cajaId)
    {
        $stmt = $pdo->prepare('UPDATE cajas SET nota = NULL WHERE id = ?');
        $stmt->execute([$cajaId]);
    }

    public static function deleteCaja($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT id FROM cajas WHERE id = ? AND estado = "cerrada"');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) throw new \Exception('Solo se pueden eliminar cajas cerradas.');
        $pdo->prepare('DELETE FROM gastos WHERE caja_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM reintegros WHERE caja_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM cajas WHERE id = ?')->execute([$id]);
    }

    public static function getAvailableYears($pdo)
    {
        return $pdo->query('SELECT DISTINCT YEAR(fecha_caja) AS anio FROM cajas ORDER BY anio DESC')->fetchAll();
    }

    public static function paginate($pdo, $tipo, $estado, $mes, $anio, $desde, $hasta, $ultimos, $pagina, $porPagina)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS c.*,
            IFNULL((SELECT SUM(valor) FROM reintegros WHERE caja_id = c.id), 0) AS total_reintegros,
            IFNULL((SELECT SUM(valor) FROM gastos WHERE caja_id = c.id), 0) AS total_gastos
            FROM cajas c WHERE c.tipo_caja = ?';
        $params = [$tipo];

        if ($estado !== 'todas') {
            $sql .= ' AND c.estado = ?';
            $params[] = $estado;
        }
        if ($mes > 0) {
            $sql .= ' AND MONTH(c.fecha_caja) = ?';
            $params[] = $mes;
        }
        if ($anio > 0) {
            $sql .= ' AND YEAR(c.fecha_caja) = ?';
            $params[] = $anio;
        }
        if ($desde) {
            $sql .= ' AND c.fecha_caja >= ?';
            $params[] = $desde;
        }
        if ($hasta) {
            $sql .= ' AND c.fecha_caja <= ?';
            $params[] = $hasta;
        }

        $sql .= ' ORDER BY c.fecha_caja DESC';

        if ($ultimos > 0) {
            $sql .= ' LIMIT ' . (int)$ultimos;
        } elseif ($ultimos === -1) {
            // "Todos": sin paginación ni límite
        } else {
            $offset = ($pagina - 1) * $porPagina;
            $sql .= ' LIMIT ' . (int)$porPagina . ' OFFSET ' . (int)$offset;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cajas = $stmt->fetchAll();

        $totalRegistros = 0;
        if ($ultimos <= 0 && $ultimos !== -1) {
            $totalRegistros = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        }

        foreach ($cajas as $i => $caja) {
            $cajas[$i]['valor_final'] = (float)$caja['valor_inicial'] + (float)$caja['total_reintegros'] - (float)$caja['total_gastos'];
        }

        return ['cajas' => $cajas, 'total' => $totalRegistros];
    }
}
