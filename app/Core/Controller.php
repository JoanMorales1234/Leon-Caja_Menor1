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

    protected function redirect($url, $fragment = '')
    {
        header('Location: ' . url($url) . ($fragment ? '#' . ltrim($fragment, '#') : ''));
        exit;
    }
}
