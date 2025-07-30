<?php

namespace App\Http\Controllers\Denuncias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Denuncias;
use App\Models\RIDMigrantes;
use App\Models\RIDFamilia;
use App\Models\User;


class DenunciasController extends Controller
{
    //


    public function index(){
    
        return Denuncias:: all();
    }

    public function store(Request $request){

        $request->validate([
            'establecimiento' => 'required',
            'provincia' => 'required',
            'distrito' => 'required',
            'corregimiento' => 'required',
            'categoria' => 'required',
            'subcategoria' => 'required',
            'lat' => 'required',
            'lon' => 'required',
            'user' => 'required',
        ]);

        return Denuncias::create($request->all());

    }

    public function show(Request $request){

        $id = $request->input('id');

        return Denuncias::find($id);
    }

    public function update(Request $request){
        
        // $denuncias = Denuncias::find($id);
        
        // $denuncias->update($request->all());

        // return $denuncias;
        $username = $request->input('username');
        $codigo = $request->input('codigo');

        $user = User::where('username', $username)->first();

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Update the user with the new data from the request
        $user->codigo = $codigo;
        $user->save();
    
        // Return the updated user
        return response()->json($user);

    }

    public function destroy($id){

        return Denuncias::destroy($id);

    }

    public function search($establecimiento){

        return Denuncias::where('establecimiento', 'like', '%'.$establecimiento.'%')->get();
        //return Denuncias::destroy($id);

    }

    public function consultaCodigo(Request $request) {

        $username = $request->input('username');

        // Buscar al usuario por username
        $user = User::where('username', $username)->first();

        // Verificar si el usuario existe
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Buscar la denuncia asociada al usuario
        $denuncia = Denuncias::where('user', $username)->first();

        // Verificar si se encontró la denuncia
        if (!$denuncia) {
            return response()->json(['message' => 'Denuncia not found'], 404);
        }

        // Obtener el campo 'estacionamiento' de la denuncia
        $estacionamiento = $denuncia->estacionamiento;

        // Retornar el valor del campo 'estacionamiento'
        return response()->json(['estacionamiento' => $estacionamiento]);

    }

   public function migrateData( Request $request) {    

        $request->validate([
            'primerNombre' => 'required',
            'primerApellido' => 'required',
            'fechaNacimiento' => 'required',
            'codigo' => 'required',
            'documento' => 'required',
            'regionId' => 'required',
            'paisId' => 'required',
            'nacionalidadId' => 'required',
            'genero' => 'required',
            'tipo' => 'required',
            'tipoubicacionId' => 'required',
            'ubicacionId' => 'required',
            'afinidadId' => 'required',
         
            //'estatus' => 'required',
            //'usuarioId' => 'required',
        ]);

        return RIDMigrantes::create($request->all());
        
   }

   public function migrateDataFamilia( Request $request) {    

    $request->validate([
        'id' => 'required',
        'codigo' => 'required', 
        //'estatus' => 'required',
        //'usuarioId' => 'required',
    ]);

    return RIDFamilia::create($request->all());
    
}

}
