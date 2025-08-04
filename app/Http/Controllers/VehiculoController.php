<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Vehiculos;
use App\Models\Accesorio;
use App\Models\VehiculoAccesorio;




class VehiculoController extends Controller
{
    public function ListaVehiculos(){

        //return response()->json(['mensaje' => 'Sí llegaste']);
    
        return Vehiculos:: all();
    }

    public function BuscarVehiculos(Request $request){

        $placa = $request->input('placa');

        $vehiculo = Vehiculos::where('placa', $placa)->first();

        if (!$vehiculo) {
            return response()->json(['mensaje' => 'Vehículo no encontrado'], 404);
        }

        // $accesorios = Accesorio::select('id', 'nombre_accesorio')
        //     ->where('activo', 1)
        //     ->get();

        return response()->json([
            'vehiculo' => $vehiculo,
            // 'accesorios' => $accesorios
        ]);
        }

    public function ListaAccesorios() {

        return Accesorio::select('id', 'nombre_accesorio')->where('activo', 1)->get();

    }



}
