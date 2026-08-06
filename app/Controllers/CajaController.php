<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\Reintegro;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Cargo;
use App\Models\Festivo;

class CajaController extends Controller
{
    public function index()
    {
        $message = null;
        $type = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_POST['action'])) throw new \Exception('Acción no definida.');

                switch ($_POST['action']) {

                    case 'reordenar_gastos':
                        $cajaId = intval($_POST['caja_id'] ?? 0);
                        $orden = json_decode($_POST['orden'] ?? '[]', true);
                        if (!$cajaId || !is_array($orden)) throw new \Exception('Datos inválidos.');
                        Gasto::reorder($this->pdo, $cajaId, $orden);
                        echo json_encode(['success' => true]);
                        exit;

                    case 'eliminar_soporte':
                        $gastoId = intval($_POST['gasto_id'] ?? 0);
                        $sopId = intval($_POST['sop_id'] ?? 0);
                        if (!$gastoId || !$sopId) throw new \Exception('Datos inválidos.');
                        Gasto::deleteSoporte($this->pdo, $sopId, $gastoId);
                        echo json_encode(['success' => true]);
                        exit;

                    case 'nueva_caja':
                        $tipoCrear = $_POST['tipo'] ?? '';
                        if (!in_array($tipoCrear, ['menor', 'mayor'])) throw new \Exception('Tipo de caja inválido.');
                        $holidays = Festivo::loadHolidays($this->pdo);
                        $fechaCaja = calculateCajaDate(date('Y-m-d'), $holidays);
                        if (!$fechaCaja) throw new \Exception('Hoy es domingo. No se puede generar caja.');
                        if (Caja::getOpenCaja($this->pdo, $tipoCrear)) throw new \Exception('Ya hay una caja ' . $tipoCrear . ' abierta.');
                        if (Caja::existsByDate($this->pdo, $tipoCrear, $fechaCaja)) throw new \Exception('Ya existe una caja ' . $tipoCrear . ' cerrada para la fecha ' . $fechaCaja . '.');
                        $valorInicial = Caja::getLastClosedSaldo($this->pdo, $tipoCrear);
                        Caja::create($this->pdo, $tipoCrear, $fechaCaja, $valorInicial);
                        $message = 'Caja ' . ucfirst($tipoCrear) . ' abierta correctamente para la fecha ' . $fechaCaja . '.';
                        break;

                    case 'agregar_gasto':
                        $cajaId = intval($_POST['caja_id'] ?? 0);
                        $caja = Caja::findById($this->pdo, $cajaId);
                        if (!$caja || $caja['estado'] !== 'abierta') {
                            $tipoCaja = $_POST['tipo_caja'] ?? 'menor';
                            $caja = Caja::getOpenCaja($this->pdo, $tipoCaja);
                            if ($caja) $cajaId = (int)$caja['id'];
                        }
                        if (!$caja) throw new \Exception('Caja no encontrada o no está abierta.');
                        $valor = floatval($_POST['valor'] ?? 0);
                        $tipoCaja = $caja['tipo_caja'];
                        if ($tipoCaja === 'menor' && $valor > 50000) throw new \Exception('El valor excede $50.000. Usa Caja Mayor para este gasto.');
                        if ($tipoCaja === 'mayor' && $valor <= 50000) throw new \Exception('El valor debe ser mayor a $50.000. Usa Caja Menor para este gasto.');
                        Gasto::shiftOrder($this->pdo, $cajaId);
                        $gastoId = Gasto::create($this->pdo, [
                            'caja_id' => $cajaId,
                            'empleado_id' => $_POST['empleado_id'] ?? '',
                            'proveedor_id' => $_POST['proveedor_id'] ?? '',
                            'fecha_gasto' => $_POST['fecha_gasto'] ?? '',
                            'descripcion' => $_POST['descripcion'],
                            'valor' => $valor,
                        ]);
                        $ordenSop = 0;
                        if (isset($_FILES['soporte_foto']) && $_FILES['soporte_foto']['error'] === UPLOAD_ERR_OK) {
                            $archivo = handleFotoUpload($_FILES['soporte_foto']);
                            Gasto::addSoporte($this->pdo, $gastoId, $_POST['tipo_soporte'] ?: 'otro', $archivo, null, $ordenSop++);
                        }
                        if (isset($_FILES['soporte_extra'])) {
                            $descs = $_POST['soporte_extra_desc'] ?? [];
                            foreach ($_FILES['soporte_extra']['error'] as $i => $err) {
                                if ($err === UPLOAD_ERR_OK && !empty($_FILES['soporte_extra']['name'][$i])) {
                                    $file = [
                                        'name' => $_FILES['soporte_extra']['name'][$i],
                                        'type' => $_FILES['soporte_extra']['type'][$i],
                                        'tmp_name' => $_FILES['soporte_extra']['tmp_name'][$i],
                                        'error' => UPLOAD_ERR_OK,
                                        'size' => $_FILES['soporte_extra']['size'][$i],
                                    ];
                                    $archivo = handleFotoUpload($file);
                                    $desc = $descs[$i] ?? '';
                                    Gasto::addSoporte($this->pdo, $gastoId, 'otro', $archivo, $desc, $ordenSop++);
                                }
                            }
                        }
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                        $message = 'Gasto agregado a caja ' . strtoupper($tipoCaja) . '.';
                        break;

                    case 'editar_gasto':
                        $gastoId = intval($_POST['id']);
                        $nuevoValor = floatval($_POST['valor']);
                        $gastoActual = Gasto::findById($this->pdo, $gastoId);
                        if (!$gastoActual) throw new \Exception('Gasto no encontrado.');
                        $tipoCaja = $gastoActual['tipo_caja'];
                        if ($tipoCaja === 'menor' && $nuevoValor > 50000) throw new \Exception('El valor excede $50.000. Este gasto pertenece a Caja Menor.');
                        if ($tipoCaja === 'mayor' && $nuevoValor <= 50000) throw new \Exception('El valor debe ser mayor a $50.000. Este gasto pertenece a Caja Mayor.');
                        Gasto::update($this->pdo, $gastoId, [
                            'empleado_id' => $_POST['empleado_id'] ?? '',
                            'proveedor_id' => $_POST['proveedor_id'] ?? '',
                            'fecha_gasto' => $_POST['fecha_gasto'],
                            'descripcion' => $_POST['descripcion'],
                            'valor' => $nuevoValor,
                        ]);
                        if (!empty($_POST['eliminar_soporte'])) {
                            Gasto::deleteSoportes($this->pdo, $gastoId, $_POST['eliminar_soporte']);
                        }
                        $ordenSop = Gasto::nextSoporteOrden($this->pdo, $gastoId);
                        if (isset($_FILES['soporte_foto']) && $_FILES['soporte_foto']['error'] === UPLOAD_ERR_OK) {
                            $archivo = handleFotoUpload($_FILES['soporte_foto']);
                            Gasto::addSoporte($this->pdo, $gastoId, $_POST['tipo_soporte'] ?: 'otro', $archivo, null, $ordenSop++);
                        }
                        if (isset($_FILES['soporte_extra'])) {
                            $descs = $_POST['soporte_extra_desc'] ?? [];
                            foreach ($_FILES['soporte_extra']['error'] as $i => $err) {
                                if ($err === UPLOAD_ERR_OK && !empty($_FILES['soporte_extra']['name'][$i])) {
                                    $file = [
                                        'name' => $_FILES['soporte_extra']['name'][$i],
                                        'type' => $_FILES['soporte_extra']['type'][$i],
                                        'tmp_name' => $_FILES['soporte_extra']['tmp_name'][$i],
                                        'error' => UPLOAD_ERR_OK,
                                        'size' => $_FILES['soporte_extra']['size'][$i],
                                    ];
                                    $archivo = handleFotoUpload($file);
                                    $desc = $descs[$i] ?? '';
                                    Gasto::addSoporte($this->pdo, $gastoId, 'otro', $archivo, $desc, $ordenSop++);
                                }
                            }
                        }
                        Caja::recalculateCajaFinal($this->pdo, $gastoActual['caja_id']);
                        $message = 'Gasto actualizado en caja ' . strtoupper($tipoCaja) . '.';
                        break;

                    case 'eliminar_gasto':
                        $gastoId = intval($_POST['id']);
                        $cajaId = Gasto::getCajaId($this->pdo, $gastoId);
                        if (!$cajaId) throw new \Exception('Gasto no encontrado.');
                        Gasto::delete($this->pdo, $gastoId);
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                        $message = 'Gasto eliminado.';
                        break;

                    case 'agregar_reintegro':
                        $cajaId = intval($_POST['caja_id'] ?? 0);
                        $valor = floatval($_POST['valor_reintegro'] ?? 0);
                        if ($cajaId <= 0 || $valor <= 0) throw new \Exception('Caja o valor inválidos.');
                        $caja = Caja::findById($this->pdo, $cajaId);
                        if (!$caja || $caja['estado'] !== 'abierta') throw new \Exception('Caja no encontrada o no está abierta.');
                        Reintegro::create($this->pdo, [
                            'caja_id' => $cajaId,
                            'valor' => $valor,
                            'descripcion' => $_POST['descripcion_reintegro'] ?? '',
                            'soporte' => $_POST['soporte_reintegro'] ?? '',
                            'fecha' => $_POST['fecha_reintegro'] ?? '',
                        ]);
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                        $message = 'Reintegro agregado.';
                        break;

                    case 'editar_reintegro':
                        $reintegroId = intval($_POST['id']);
                        $reintegro = Reintegro::findById($this->pdo, $reintegroId);
                        if (!$reintegro) throw new \Exception('Reintegro no encontrado.');
                        Reintegro::update($this->pdo, $reintegroId, [
                            'valor' => floatval($_POST['valor_reintegro']),
                            'descripcion' => $_POST['descripcion_reintegro'] ?? '',
                            'soporte' => $_POST['soporte_reintegro'] ?? '',
                            'fecha' => $_POST['fecha_reintegro'] ?? '',
                        ]);
                        Caja::recalculateCajaFinal($this->pdo, $reintegro['caja_id']);
                        $message = 'Reintegro actualizado.';
                        break;

                    case 'eliminar_reintegro':
                        $reintegroId = intval($_POST['id']);
                        $cajaId = Reintegro::getCajaId($this->pdo, $reintegroId);
                        if (!$cajaId) throw new \Exception('Reintegro no encontrado.');
                        Reintegro::delete($this->pdo, $reintegroId);
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                        $message = 'Reintegro eliminado.';
                        break;

                    case 'cerrar_caja':
                        Caja::closeCaja($this->pdo, intval($_POST['caja_id'] ?? 0));
                        $message = 'Caja cerrada correctamente.';
                        break;

                    case 'nuevo_empleado':
                        Empleado::create($this->pdo, [
                            'cedula' => $_POST['cedula'],
                            'nombres' => $_POST['nombres'],
                            'apellidos' => $_POST['apellidos'],
                            'cargo_id' => $_POST['cargo_id'] ?? '',
                            'telefono' => $_POST['telefono'] ?? '',
                        ]);
                        $message = 'Empleado creado correctamente.';
                        break;

                    case 'editar_empleado':
                        Empleado::update($this->pdo, intval($_POST['id']), [
                            'cedula' => $_POST['cedula'],
                            'nombres' => $_POST['nombres'],
                            'apellidos' => $_POST['apellidos'],
                            'cargo_id' => $_POST['cargo_id'] ?? '',
                            'telefono' => $_POST['telefono'] ?? '',
                        ]);
                        $message = 'Empleado actualizado correctamente.';
                        break;

                    case 'inactivar_empleado':
                        Empleado::setEstado($this->pdo, intval($_POST['id']), 'inactivo');
                        $message = 'Empleado inactivado.';
                        break;

                    case 'activar_empleado':
                        Empleado::setEstado($this->pdo, intval($_POST['id']), 'activo');
                        $message = 'Empleado activado.';
                        break;

                    case 'eliminar_empleado':
                        Empleado::delete($this->pdo, intval($_POST['id']));
                        $message = 'Empleado eliminado permanentemente.';
                        break;

                    case 'nuevo_proveedor':
                        Proveedor::create($this->pdo, [
                            'nit' => $_POST['nit'],
                            'nombre' => $_POST['nombre'],
                            'telefono' => $_POST['telefono'] ?? '',
                            'direccion' => $_POST['direccion'] ?? '',
                        ]);
                        $message = 'Proveedor creado correctamente.';
                        break;

                    case 'editar_proveedor':
                        Proveedor::update($this->pdo, intval($_POST['id']), [
                            'nit' => $_POST['nit'],
                            'nombre' => $_POST['nombre'],
                            'telefono' => $_POST['telefono'] ?? '',
                            'direccion' => $_POST['direccion'] ?? '',
                        ]);
                        $message = 'Proveedor actualizado correctamente.';
                        break;

                    case 'inactivar_proveedor':
                        Proveedor::setEstado($this->pdo, intval($_POST['id']), 'inactivo');
                        $message = 'Proveedor inactivado.';
                        break;

                    case 'activar_proveedor':
                        Proveedor::setEstado($this->pdo, intval($_POST['id']), 'activo');
                        $message = 'Proveedor activado.';
                        break;

                    case 'eliminar_proveedor':
                        Proveedor::delete($this->pdo, intval($_POST['id']));
                        $message = 'Proveedor eliminado permanentemente.';
                        break;

                    case 'nuevo_cargo':
                        Cargo::create($this->pdo, $_POST['nombre']);
                        $message = 'Cargo creado correctamente.';
                        break;

                    case 'editar_cargo':
                        Cargo::update($this->pdo, intval($_POST['id']), $_POST['nombre']);
                        $message = 'Cargo actualizado correctamente.';
                        break;

                    case 'inactivar_cargo':
                        Cargo::setEstado($this->pdo, intval($_POST['id']), 'inactivo');
                        $message = 'Cargo inactivado.';
                        break;

                    case 'activar_cargo':
                        Cargo::setEstado($this->pdo, intval($_POST['id']), 'activo');
                        $message = 'Cargo activado.';
                        break;

                    case 'eliminar_cargo':
                        Cargo::delete($this->pdo, intval($_POST['id']));
                        $message = 'Cargo eliminado permanentemente.';
                        break;

                    case 'eliminar_caja':
                        Caja::deleteCaja($this->pdo, intval($_POST['id']));
                        $message = 'Caja y todos sus movimientos eliminados.';
                        break;

                    case 'agregar_festivo':
                        Festivo::create($this->pdo, $_POST['nombre_festivo'], $_POST['fecha_festivo']);
                        $message = 'Festivo registrado correctamente.';
                        break;

                    default:
                        throw new \Exception('Acción desconocida.');
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $type = 'danger';
            }

            // Patrón Post/Redirect/Get: guarda el mensaje en sesión y redirige.
            // Evita que al recargar (F5) el navegador reenvíe el POST y duplique el gasto.
            if ($message !== null) {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $type;
            }
            $this->redirect('index');
        }

        // Lee el mensaje flash guardado tras el redirect del POST.
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        }

        $cajaMenor = Caja::getCajaDetails($this->pdo, 'menor');
        $cajaMayor = Caja::getCajaDetails($this->pdo, 'mayor');
        $movimientosMenor = $cajaMenor ? Caja::getCajaMovements($this->pdo, $cajaMenor['id']) : ['gastos' => [], 'reintegros' => []];
        $movimientosMayor = $cajaMayor ? Caja::getCajaMovements($this->pdo, $cajaMayor['id']) : ['gastos' => [], 'reintegros' => []];

        $empleados = Empleado::activeSelect($this->pdo);
        $proveedores = Proveedor::activeSelect($this->pdo);
        $cargos = Cargo::activeSelect($this->pdo);

        $todosEmpleados = Empleado::all($this->pdo);
        $todosProveedores = Proveedor::all($this->pdo);
        $todosCargos = Cargo::all($this->pdo);
        $festivosActivos = Festivo::allActive($this->pdo);

        $this->view('layout/header', compact('message', 'type') + ['pageTitle' => 'Sistema de Caja Menor / Mayor']);
        $this->view('index', compact(
            'message', 'type',
            'cajaMenor', 'cajaMayor',
            'movimientosMenor', 'movimientosMayor',
            'empleados', 'proveedores', 'cargos',
            'todosEmpleados', 'todosProveedores', 'todosCargos',
            'festivosActivos'
        ));
        $this->view('layout/footer');
    }
}
