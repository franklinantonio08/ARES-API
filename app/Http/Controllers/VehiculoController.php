<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Vehiculos;


class VehiculoController extends Controller
{
    public function ListaVehiculos(){

        //return response()->json(['mensaje' => 'Sí llegaste']);
    
        return Vehiculos:: all();
    }
}
