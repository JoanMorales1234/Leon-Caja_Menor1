<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caja;
use App\Models\Festivo;

class SetupController extends Controller
{
    public function seed()
    {
        try {
            $cargos = ['Gerente', 'Sistemas', 'Logística', 'Contador'];
            foreach ($cargos as $cargo) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO cargos (nombre) VALUES (?)');
                $stmt->execute([$cargo]);
            }

            $empleados = [
                ['cedula' => '12345678', 'nombres' => 'Juan', 'apellidos' => 'Pérez', 'cargo' => 'Gerente', 'telefono' => '3001234567'],
                ['cedula' => '87654321', 'nombres' => 'María', 'apellidos' => 'González', 'cargo' => 'Contador', 'telefono' => '3007654321'],
                ['cedula' => '10293847', 'nombres' => 'Carlos', 'apellidos' => 'Ramírez', 'cargo' => 'Sistemas', 'telefono' => '3009876543'],
            ];
            foreach ($empleados as $empleado) {
                $stmt = $this->pdo->prepare('SELECT id FROM cargos WHERE nombre = ? LIMIT 1');
                $stmt->execute([$empleado['cargo']]);
                $cargoId = $stmt->fetchColumn();
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO empleados (cedula, nombres, apellidos, cargo_id, telefono) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$empleado['cedula'], $empleado['nombres'], $empleado['apellidos'], $cargoId, $empleado['telefono']]);
            }

            $proveedores = [
                ['nit' => '900123456', 'nombre' => 'Distribuciones ABC', 'telefono' => '3012345678', 'direccion' => 'Calle 1 # 2-3'],
                ['nit' => '800987654', 'nombre' => 'Servicios XYZ', 'telefono' => '3019876543', 'direccion' => 'Carrera 4 # 5-6'],
            ];
            foreach ($proveedores as $proveedor) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO proveedores (nit, nombre, telefono, direccion) VALUES (?, ?, ?, ?)');
                $stmt->execute([$proveedor['nit'], $proveedor['nombre'], $proveedor['telefono'], $proveedor['direccion']]);
            }

            $festivos = [
                ['nombre' => 'Día del Trabajo', 'fecha' => '2026-05-01'],
                ['nombre' => 'Independencia', 'fecha' => '2026-07-20'],
            ];
            foreach ($festivos as $festivo) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO festivos (nombre, fecha) VALUES (?, ?)');
                $stmt->execute([$festivo['nombre'], $festivo['fecha']]);
            }

            $holidays = Festivo::loadHolidays($this->pdo);
            $fechaCaja = calculateCajaDate(date('Y-m-d'), $holidays);
            if ($fechaCaja) {
                foreach (['menor', 'mayor'] as $tipo) {
                    $stmt = $this->pdo->prepare('SELECT id FROM cajas WHERE tipo_caja = ? AND estado = "abierta" LIMIT 1');
                    $stmt->execute([$tipo]);
                    $cajaId = $stmt->fetchColumn();
                    if (!$cajaId) {
                        $valorInicial = Caja::getLastClosedSaldo($this->pdo, $tipo);
                        $stmt = $this->pdo->prepare('INSERT INTO cajas (tipo_caja, fecha_caja, valor_inicial) VALUES (?, ?, ?)');
                        $stmt->execute([$tipo, $fechaCaja, $valorInicial]);
                        $cajaId = $this->pdo->lastInsertId();
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    }
                    if ($tipo === 'mayor') {
                        $stmt = $this->pdo->prepare('INSERT INTO reintegros (caja_id, valor, descripcion, soporte, fecha_reintegro) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$cajaId, 232976.00, 'Reintegro de ejemplo para caja mayor', 'Ticket 232976', date('Y-m-d')]);
                        Caja::recalculateCajaFinal($this->pdo, $cajaId);
                    }
                }
            }

            $this->view('layout/header', ['pageTitle' => 'Resultado']);
            $this->view('setup_resultado', [
                'tipo' => 'success',
                'titulo' => 'Datos de ejemplo cargados',
                'mensaje' => 'Se cargaron cargos, empleados, proveedores, festivos y un reintegro de 232.976,00 en la caja mayor abierta.',
            ]);
            $this->view('layout/footer');
        } catch (\Exception $e) {
            $this->view('layout/header', ['pageTitle' => 'Resultado']);
            $this->view('setup_resultado', [
                'tipo' => 'danger',
                'titulo' => 'Error',
                'mensaje' => $e->getMessage(),
            ]);
            $this->view('layout/footer');
        }
    }

    public function reset()
    {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
            try {
                $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

                $tables = ['gastos', 'reintegros', 'cajas', 'festivos', 'role_permissions', 'users', 'empleados', 'proveedores', 'cargos', 'permissions', 'roles'];
                foreach ($tables as $table) {
                    $this->pdo->exec("TRUNCATE TABLE `$table`");
                }

                $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

                $this->pdo->exec("INSERT INTO roles (nombre, descripcion) VALUES
                    ('admin', 'Administrador del sistema'),
                    ('aprendiz', 'Usuario aprendiz'),
                    ('facturador', 'Usuario facturador'),
                    ('contador', 'Usuario contador')");

                $this->pdo->exec("INSERT INTO permissions (nombre, descripcion) VALUES
                    ('crear_gasto', 'Crear gastos'),
                    ('editar_gasto', 'Editar gastos'),
                    ('exportar_excel', 'Exportar a Excel'),
                    ('cerrar_caja', 'Cerrar caja')");

                $message = 'Base de datos restablecida correctamente. Todos los datos fueron eliminados y los contadores reiniciados.';
            } catch (\Exception $e) {
                $error = 'Error al restablecer: ' . $e->getMessage();
            }
        }

        $this->view('layout/header', ['pageTitle' => 'Restablecer base de datos']);
        $this->view('reset', compact('message', 'error'));
        $this->view('layout/footer');
    }

    public function install()
    {
        try {
            $sql = file_get_contents(ROOT_PATH . '/schema.sql');
            if ($sql === false) {
                throw new \Exception('No se pudo leer schema.sql');
            }
            $this->pdo->exec($sql);
            $this->view('layout/header', ['pageTitle' => 'Resultado']);
            $this->view('setup_resultado', [
                'tipo' => 'success',
                'titulo' => 'Instalación completada',
                'mensaje' => 'La base de datos y las tablas se han creado correctamente.',
            ]);
            $this->view('layout/footer');
        } catch (\Exception $e) {
            $this->view('layout/header', ['pageTitle' => 'Resultado']);
            $this->view('setup_resultado', [
                'tipo' => 'danger',
                'titulo' => 'Error de instalación',
                'mensaje' => $e->getMessage(),
            ]);
            $this->view('layout/footer');
        }
    }
}
