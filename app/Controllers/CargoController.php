<?php

namespace App\Controllers;

use App\Core\Controller;

class CargoController extends Controller
{
    public function index()
    {
        $this->redirect('index', 'cargos-pane');
    }
}
