<?php

namespace App\Controllers;

use App\Core\Controller;

class ProveedorController extends Controller
{
    public function index()
    {
        $this->redirect('index', 'proveedores-pane');
    }
}
