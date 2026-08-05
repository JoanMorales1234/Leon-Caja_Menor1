<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\Reintegro;
use App\Models\Empleado;
use App\Models\Proveedor;

class HistorialController extends Controller
{
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
            return;
        }

        $detalleId = isset($_GET['detalle']) ? intval($_GET['detalle']) : 0;
        $detalleCaja = null;
        $detalleGastos = [];
        $detalleReintegros = [];

        if ($detalleId) {
            $detalleCaja = Caja::findById($this->pdo, $detalleId);
            if ($detalleCaja) {
                $detalleGastos = $this->getDetalleGastos($detalleId);
                $detalleReintegros = $this->getDetalleReintegros($detalleId);
            }
        }

        $filtroEstado = isset($_GET['estado']) && in_array($_GET['estado'], ['abierta', 'cerrada', 'todas']) ? $_GET['estado'] : 'todas';
        $filtroMes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;
        $filtroAnio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;
        $filtroDesde = isset($_GET['desde']) ? $_GET['desde'] : '';
        $filtroHasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';
        $filtroUltimos = isset($_GET['ultimos']) ? intval($_GET['ultimos']) : 0;
        $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $porPagina = 15;

        $resultMenor = Caja::paginate($this->pdo, 'menor', $filtroEstado, $filtroMes, $filtroAnio, $filtroDesde, $filtroHasta, $filtroUltimos, $pagina, $porPagina);
        $resultMayor = Caja::paginate($this->pdo, 'mayor', $filtroEstado, $filtroMes, $filtroAnio, $filtroDesde, $filtroHasta, $filtroUltimos, $pagina, $porPagina);
        $cajasMenor = $resultMenor['cajas'];
        $cajasMayor = $resultMayor['cajas'];
        $totalMenor = $resultMenor['total'];
        $totalMayor = $resultMayor['total'];
        $totalPaginasMenor = $filtroUltimos > 0 ? 1 : max(1, ceil($totalMenor / $porPagina));
        $totalPaginasMayor = $filtroUltimos > 0 ? 1 : max(1, ceil($totalMayor / $porPagina));

        $aniosDisponibles = Caja::getAvailableYears($this->pdo);
        $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $diasMesActual = date('t');
        $empleados = Empleado::activeSelect($this->pdo);
        $proveedores = Proveedor::activeSelect($this->pdo);

        $this->view('layout/header', ['pageTitle' => 'Historial de cajas']);
        $this->view('historial', compact(
            'detalleCaja', 'detalleGastos', 'detalleReintegros',
            'filtroEstado', 'filtroMes', 'filtroAnio', 'filtroDesde', 'filtroHasta', 'filtroUltimos', 'pagina',
            'cajasMenor', 'cajasMayor', 'totalMenor', 'totalMayor',
            'totalPaginasMenor', 'totalPaginasMayor',
            'aniosDisponibles', 'meses', 'diasMesActual',
            'empleados', 'proveedores'
        ));
        $this->view('layout/footer');
    }

    private function handlePost()
    {
        $message = '';
        $type = 'success';
        $cajaId = 0;

        try {
            if (!isset($_POST['action'])) throw new \Exception('Acción no definida.');

            switch ($_POST['action']) {

                case 'agregar_gasto':
                    $cajaId = intval($_POST['caja_id']);
                    $caja = Caja::findById($this->pdo, $cajaId);
                    if (!$caja) throw new \Exception('Caja no encontrada.');
                    $nuevoValor = floatval($_POST['valor']);
                    if ($caja['tipo_caja'] === 'menor' && $nuevoValor > 50000) throw new \Exception('El valor excede $50.000. Usa Caja Mayor.');
                    if ($caja['tipo_caja'] === 'mayor' && $nuevoValor <= 50000) throw new \Exception('El valor debe ser mayor a $50.000. Usa Caja Menor.');
                    Gasto::create($this->pdo, [
                        'caja_id' => $cajaId,
                        'empleado_id' => $_POST['empleado_id'] ?? '',
                        'proveedor_id' => $_POST['proveedor_id'] ?? '',
                        'fecha_gasto' => $_POST['fecha_gasto'] ?: date('Y-m-d'),
                        'descripcion' => $_POST['descripcion'],
                        'valor' => $nuevoValor,
                    ]);
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Gasto agregado correctamente.';
                    break;

                case 'editar_gasto':
                    $gastoId = intval($_POST['id']);
                    $nuevoValor = floatval($_POST['valor']);
                    $gastoActual = Gasto::findById($this->pdo, $gastoId);
                    if (!$gastoActual) throw new \Exception('Gasto no encontrado.');
                    $tipoCaja = $gastoActual['tipo_caja'];
                    if ($tipoCaja === 'menor' && $nuevoValor > 50000) throw new \Exception('El valor excede $50.000. Este gasto pertenece a Caja Menor.');
                    if ($tipoCaja === 'mayor' && $nuevoValor <= 50000) throw new \Exception('El valor debe ser mayor a $50.000. Este gasto pertenece a Caja Mayor.');
                    $fecha = $_POST['fecha_gasto'] ?? '';
                    if ($fecha && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) throw new \Exception('Fecha inválida.');
                    Gasto::update($this->pdo, $gastoId, [
                        'empleado_id' => $_POST['empleado_id'] ?? '',
                        'proveedor_id' => $_POST['proveedor_id'] ?? '',
                        'fecha_gasto' => $fecha ?: $gastoActual['fecha_gasto'],
                        'descripcion' => $_POST['descripcion'],
                        'valor' => $nuevoValor,
                    ]);
                    $cajaId = (int)$gastoActual['caja_id'];
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Gasto actualizado correctamente.';
                    break;

                case 'eliminar_gasto':
                    $gastoId = intval($_POST['id']);
                    $cajaId = (int)Gasto::getCajaId($this->pdo, $gastoId);
                    if (!$cajaId) throw new \Exception('Gasto no encontrado.');
                    Gasto::delete($this->pdo, $gastoId);
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Gasto eliminado.';
                    break;

                case 'agregar_reintegro':
                    $cajaId = intval($_POST['caja_id']);
                    $caja = Caja::findById($this->pdo, $cajaId);
                    if (!$caja) throw new \Exception('Caja no encontrada.');
                    Reintegro::create($this->pdo, [
                        'caja_id' => $cajaId,
                        'valor' => floatval($_POST['valor_reintegro'] ?? 0),
                        'descripcion' => $_POST['descripcion_reintegro'] ?? '',
                        'soporte' => $_POST['soporte_reintegro'] ?? '',
                        'fecha' => $_POST['fecha_reintegro'] ?: date('Y-m-d'),
                    ]);
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Reintegro agregado correctamente.';
                    break;

                case 'editar_reintegro':
                    $reintegroId = intval($_POST['id']);
                    $reintegro = Reintegro::findById($this->pdo, $reintegroId);
                    if (!$reintegro) throw new \Exception('Reintegro no encontrado.');
                    $fecha = $_POST['fecha_reintegro'] ?? '';
                    if ($fecha && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) throw new \Exception('Fecha inválida.');
                    Reintegro::update($this->pdo, $reintegroId, [
                        'valor' => floatval($_POST['valor_reintegro'] ?? 0),
                        'descripcion' => $_POST['descripcion_reintegro'] ?? '',
                        'soporte' => $reintegro['soporte'] ?? '',
                        'fecha' => $fecha ?: $reintegro['fecha_reintegro'],
                    ]);
                    $cajaId = (int)$reintegro['caja_id'];
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Reintegro actualizado correctamente.';
                    break;

                case 'eliminar_reintegro':
                    $reintegroId = intval($_POST['id']);
                    $cajaId = (int)Reintegro::getCajaId($this->pdo, $reintegroId);
                    if (!$cajaId) throw new \Exception('Reintegro no encontrado.');
                    Reintegro::delete($this->pdo, $reintegroId);
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Reintegro eliminado.';
                    break;

                case 'editar_caja':
                    $cajaId = intval($_POST['id']);
                    $caja = Caja::findById($this->pdo, $cajaId);
                    if (!$caja) throw new \Exception('Caja no encontrada.');
                    $fecha = $_POST['fecha_caja'] ?? '';
                    $valorInicial = floatval($_POST['valor_inicial'] ?? $caja['valor_inicial']);
                    if ($fecha && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) throw new \Exception('Fecha inválida.');
                    if ($fecha && $fecha !== $caja['fecha_caja']
                        && Caja::existsByDate($this->pdo, $caja['tipo_caja'], $fecha)) {
                        throw new \Exception("Ya existe una caja de tipo {$caja['tipo_caja']} para esa fecha.");
                    }
                    $stmt = $this->pdo->prepare('UPDATE cajas SET fecha_caja = ?, valor_inicial = ? WHERE id = ?');
                    $stmt->execute([$fecha ?: $caja['fecha_caja'], $valorInicial, $cajaId]);
                    Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    $message = 'Caja actualizada correctamente.';
                    break;

                case 'eliminar_caja':
                    Caja::deleteCaja($this->pdo, intval($_POST['id']));
                    $message = 'Caja y sus movimientos eliminados.';
                    $cajaId = 0;
                    break;

                default:
                    throw new \Exception('Acción desconocida.');
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $type = 'danger';
        }

        $this->redirectHistorial($cajaId, $message, $type);
    }

    private function redirectHistorial($cajaId, $message, $type)
    {
        $qs = [];
        foreach (['estado', 'mes', 'anio', 'desde', 'hasta', 'ultimos', 'pagina'] as $k) {
            $v = $_GET[$k] ?? '';
            if ($v !== '' && $v !== '0') $qs[$k] = $v;
        }
        if ($cajaId > 0) $qs['detalle'] = $cajaId;
        $qs['msg'] = $message;
        $qs['tipo'] = $type;
        $this->redirect('historial', $qs);
    }

    private function getDetalleGastos($cajaId)
    {
        $stmt = $this->pdo->prepare('SELECT g.*, e.nombres, e.apellidos, e.cedula, p.nombre AS proveedor_nombre, p.nit AS proveedor_nit
            FROM gastos g
            LEFT JOIN empleados e ON g.empleado_id = e.id
            LEFT JOIN proveedores p ON g.proveedor_id = p.id
            WHERE g.caja_id = ? ORDER BY g.orden ASC, g.id ASC');
        $stmt->execute([$cajaId]);
        $gastos = $stmt->fetchAll();
        foreach ($gastos as &$g) {
            $sops = Gasto::getSoportes($this->pdo, $g['id']);
            $g['soportes_json'] = json_encode(array_map(function ($s) {
                return ['id' => (int)$s['id'], 'tipo' => $s['tipo'], 'descripcion' => $s['descripcion'], 'archivo' => $s['archivo']];
            }, $sops));
        }
        return $gastos;
    }

    private function getDetalleReintegros($cajaId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reintegros WHERE caja_id = ? ORDER BY creado_en DESC');
        $stmt->execute([$cajaId]);
        return $stmt->fetchAll();
    }
}