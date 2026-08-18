<?php

namespace App\Core;

use App\Core\Database;

abstract class Controller
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = Database::getPdo();
    }

    protected function view($view, array $data = [])
    {
        $file = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \Exception("Vista no encontrada: $view");
        }
        extract($data, EXTR_SKIP);
        require $file;
    }

    protected function redirect($url, $params = '')
    {
        if (is_array($params)) {
            header('Location: ' . url($url, $params));
        } else {
            header('Location: ' . url($url) . ($params ? '#' . ltrim($params, '#') : ''));
        }
        exit;
    }
}
