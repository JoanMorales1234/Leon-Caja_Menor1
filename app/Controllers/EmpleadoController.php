<?php

namespace App\Controllers;

use App\Core\Controller;

class EmpleadoController extends Controller
{
    public function index()
    {
        $this->redirect('index', 'empleados-pane');
    }
}
