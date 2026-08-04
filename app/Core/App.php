<?php

namespace App\Core;

use App\Core\Database;

class App
{
    public static function run()
    {
        $url = trim($_GET['url'] ?? '', '/');
        $segments = $url === '' ? [] : explode('/', $url);

        $routes = [
            '' => ['CajaController', 'index'],
            'index' => ['CajaController', 'index'],
            'historial' => ['HistorialController', 'index'],
            'empleados' => ['EmpleadoController', 'index'],
            'proveedores' => ['ProveedorController', 'index'],
            'cargos' => ['CargoController', 'index'],
            'importar' => ['ImportController', 'index'],
            'logo' => ['LogoController', 'index'],
            'imprimir' => ['PrintController', 'imprimir'],
            'soportes' => ['PrintController', 'soportes'],
            'exportar/excel' => ['ExportController', 'excel'],
            'exportar/syscafe' => ['ExportController', 'syscafe'],
            'datos/ejemplo' => ['SetupController', 'seed'],
            'datos/reset' => ['SetupController', 'reset'],
            'instalar' => ['SetupController', 'install'],
        ];

        $route = $url;
        if (!isset($routes[$route])) {
            http_response_code(404);
            echo 'Página no encontrada';
            exit;
        }

        [$controllerName, $method] = $routes[$route];
        $controllerClass = 'App\\Controllers\\' . $controllerName;
        $controller = new $controllerClass();
        $controller->$method();
    }
}
