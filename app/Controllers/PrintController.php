<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use App\Models\Gasto;

class PrintController extends Controller
{
    public function imprimir()
    {
        $cajaId = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
        $tipoPrint = isset($_GET['tipo']) && in_array($_GET['tipo'], ['menor', 'mayor']) ? $_GET['tipo'] : '';

        if ($cajaId) {
            $caja = Caja::findById($this->pdo, $cajaId);
        } elseif ($tipoPrint) {
            $caja = Caja::getCajaDetails($this->pdo, $tipoPrint);
        } else {
            die('Debe especificar ?caja_id=N o ?tipo=menor|mayor');
        }

        if (!$caja) die('Caja no encontrada');

        $gastosData = $this->getGastosPrint($caja['id']);

        $maxChars = 0;
        foreach ($gastosData as $g) {
            $len = mb_strlen($g['descripcion'] ?? '');
            if ($len > $maxChars) $maxChars = $len;
            $len = mb_strlen(trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')));
            if ($len > $maxChars) $maxChars = $len;
            $len = mb_strlen($g['cargo'] ?? '');
            if ($len > $maxChars) $maxChars = $len;
            $len = mb_strlen($g['proveedor_nombre'] ?? '');
            if ($len > $maxChars) $maxChars = $len;
        }
        if ($maxChars < 20) $fontSize = 8;
        elseif ($maxChars < 30) $fontSize = 7.5;
        elseif ($maxChars < 40) $fontSize = 7;
        elseif ($maxChars < 55) $fontSize = 6.5;
        elseif ($maxChars < 75) $fontSize = 6;
        elseif ($maxChars < 100) $fontSize = 5.5;
        else $fontSize = 5;

        $totalGastos = (float)$caja['total_gastos'];
        $totalReintegros = (float)$caja['total_reintegros'];
        $valorInicial = (float)$caja['valor_inicial'];

        $maxPerPage = 24;
        if (empty($gastosData)) {
            $gastosChunks = [$gastosData];
        } else {
            $gastosChunks = array_chunk($gastosData, $maxPerPage);
        }
        $totalPaginas = count($gastosChunks);

        $saldoAnterior = $valorInicial;
        $saldoActual = $saldoAnterior + $totalReintegros;
        $nuevoSaldo = $saldoActual - $totalGastos;

        $tipoMay = strtoupper($caja['tipo_caja']);
        $fechaObj = \DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
        $mesEsp = [
            'January' => 'ENERO', 'February' => 'FEBRERO', 'March' => 'MARZO',
            'April' => 'ABRIL', 'May' => 'MAYO', 'June' => 'JUNIO',
            'July' => 'JULIO', 'August' => 'AGOSTO', 'September' => 'SEPTIEMBRE',
            'October' => 'OCTUBRE', 'November' => 'NOVIEMBRE', 'December' => 'DICIEMBRE'
        ];
        $nomMes = $mesEsp[$fechaObj->format('F')] ?? strtoupper($fechaObj->format('F'));
        $dia = $fechaObj->format('d');
        $anio = $fechaObj->format('Y');

        $notaGuardada = Caja::getNota($this->pdo, $caja['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cajaId) {
            if (isset($_POST['guardar_nota'])) {
                $notaGuardada = $_POST['nota_caja'] ?? '';
                Caja::saveNota($this->pdo, $cajaId, $notaGuardada);
            } elseif (isset($_POST['eliminar_nota'])) {
                $notaGuardada = '';
                Caja::clearNota($this->pdo, $cajaId);
            }
        }

        $tieneLogo = file_exists(ROOT_PATH . '/uploads/logo.png');

        $this->view('imprimir_caja', compact(
            'caja', 'gastosData', 'gastosChunks', 'totalPaginas',
            'fontSize', 'totalGastos', 'totalReintegros', 'valorInicial',
            'saldoAnterior', 'saldoActual', 'nuevoSaldo',
            'tipoMay', 'nomMes', 'dia', 'anio',
            'notaGuardada', 'tieneLogo'
        ));
    }

    public function soportes()
    {
        $cajaId = isset($_GET['caja_id']) ? intval($_GET['caja_id']) : 0;
        if (!$cajaId) die('Debe especificar ?caja_id=N');

        $stmt = $this->pdo->prepare('SELECT * FROM cajas WHERE id = ?');
        $stmt->execute([$cajaId]);
        $caja = $stmt->fetch();
        if (!$caja) die('Caja no encontrada');

        $todosGastos = Caja::getGastosWithDetails($this->pdo, $cajaId);
        $sopStmt = $this->pdo->prepare('SELECT * FROM soportes WHERE gasto_id = ? ORDER BY orden, id');
        foreach ($todosGastos as &$g) {
            $sopStmt->execute([$g['id']]);
            $g['soportes'] = $sopStmt->fetchAll();
        }
        unset($g);

        $todosSoportes = [];
        foreach ($todosGastos as $g) {
            $nombreEmp = trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? ''));
            if (!empty($g['soportes'])) {
                foreach ($g['soportes'] as $sop) {
                    if ($sop['archivo']) {
                        $todosSoportes[] = [
                            'id' => $g['id'],
                            'nombre' => $nombreEmp,
                            'archivo' => $sop['archivo'],
                            'orden' => $sop['orden'],
                        ];
                    }
                }
            } elseif ($g['soporte']) {
                $todosSoportes[] = [
                    'id' => $g['id'],
                    'nombre' => $nombreEmp,
                    'archivo' => $g['soporte'],
                    'orden' => 0,
                ];
            }
        }
        $totalGastos = count($todosSoportes);
        $tipoMay = strtoupper($caja['tipo_caja']);
        $fechaObj = \DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
        $mesEsp = [
            'January' => 'ENERO', 'February' => 'FEBRERO', 'March' => 'MARZO',
            'April' => 'ABRIL', 'May' => 'MAYO', 'June' => 'JUNIO',
            'July' => 'JULIO', 'August' => 'AGOSTO', 'September' => 'SEPTIEMBRE',
            'October' => 'OCTUBRE', 'November' => 'NOVIEMBRE', 'December' => 'DICIEMBRE'
        ];
        $nomMes = $mesEsp[$fechaObj->format('F')] ?? strtoupper($fechaObj->format('F'));
        $dia = $fechaObj->format('d');
        $anio = $fechaObj->format('Y');
        $fechaCajaStr = "$dia DE $nomMes $anio";

        $orientacion = isset($_GET['orientacion']) && $_GET['orientacion'] === 'vertical' ? 'vertical' : 'horizontal';
        $pageW = $orientacion === 'vertical' ? '21.6cm' : '27.9cm';
        $pageH = $orientacion === 'vertical' ? '27.9cm' : '21.6cm';
        $pageSize = $orientacion === 'vertical' ? 'letter portrait' : 'letter landscape';
        $chunks = array_chunk($todosSoportes, 4);
        $totalPaginas = count($chunks);
        $tieneLogo = file_exists(ROOT_PATH . '/uploads/logo.png');

        $this->view('imprimir_soportes', compact(
            'caja', 'cajaId', 'todosGastos', 'todosSoportes', 'totalGastos',
            'tipoMay', 'fechaCajaStr', 'orientacion', 'pageW', 'pageH', 'pageSize',
            'chunks', 'totalPaginas', 'tieneLogo'
        ));
    }

    private function getGastosPrint($cajaId)
    {
        $gastos = $this->pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, ca.nombre AS cargo, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
            FROM gastos g
            LEFT JOIN empleados e ON g.empleado_id = e.id
            LEFT JOIN cargos ca ON e.cargo_id = ca.id
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.orden DESC, g.id ASC');
        $gastos->execute([$cajaId]);
        return $gastos->fetchAll();
    }
}
