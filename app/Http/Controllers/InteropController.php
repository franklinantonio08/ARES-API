<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Migrantesinfo;
use App\Models\Ocupacion;
use App\Models\Paises;


use App\Helpers\FotoHelper;
use App\Helpers\CommonHelper;
use App\Traits\Loggable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DB;
use Excel;
use Carbon\Carbon;


class InteropController extends Controller
{
    private $request;
    private $common;

    public function __construct(Request $request){
        
        $this->request = $request;
        $this->common = New CommonHelper();
    }

    public function BuscarRegistro(){

        $request = $this->request;

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('099', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        // =========================
        // Inputs comunes
        // =========================
        $ruex               = $request->input('Ruex');
        $pasaporte          = $request->input('Num_pasaporte');
        $fechaNacimiento    = $request->input('Fecha_nacimiento');
        $paisNacimiento     = $request->input('Pais_nac');

        $primerNombre       = $request->input('Primer_nombre');
        $segundoNombre      = $request->input('Segundo_nombre');
        $primerApellido     = $request->input('Primer_apellido');
        $segundoApellido    = $request->input('Segundo_apellido');
        $apellidoCasada     = $request->input('Apellido_casada');

        // return $ruex ;

        // =========================
        // Query base
        // =========================
        $query = Migrantesinfo::select([
            'num_reg_filiacion', 
            'primerNombre', 
            'segundoNombre', 
            'primerApellido', 
            'segundoApellido', 
            'apellidoCasada', 
            'num_pasaporte', 
            'cod_nac', 
            'nacionalidad', 
            'fecha_nacimiento', 
            'cod_genero', 
            'genero', 
            'cod_pais_nacimiento', 
            'pais_nacimiento', 
            'direccion', 
            'numero_telefono', 
            'numero_carnet', 
            'cod_ocupacion', 
            'ocupacion', 
            'cod_estadoCivil', 
            'estadoCivil', 
            'nombre_conyugue', 
            'cod_nacionalidad_conyugue', 
            'nacionalidad_conyugue', 
            'nombre_padre', 
            'cod_nacionalidad_padre', 
            'nacionalidad_padre', 
            'nombre_madre', 
            'cod_nacionalidad_madre', 
            'nacionalidad_madre'
        ]);

        // =========================
        // Opción 1: RUEX
        // =========================
        if (!empty($ruex)) {

            $query->where('num_reg_filiacion', $ruex);

        // =========================
        // Opción 2: Pasaporte
        // =========================
        } elseif (!empty($pasaporte)) {

            $query->where('num_pasaporte', $pasaporte);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debe enviar RUEX o número de pasaporte',
                'code'    => 'VALIDATION_ERROR'
            ], 422);
        }

        // =========================
        // Filtros comunes
        // =========================
        $query->where('primerNombre', $primerNombre)
            ->where('primerApellido', $primerApellido);

        if (!empty($segundoNombre)) {
            $query->where('segundoNombre', $segundoNombre);
        }

        if (!empty($segundoApellido)) {
            $query->where('segundoApellido', $segundoApellido);
        }

        // if (!empty($apellidoCasada)) {
        //     $query->where('apellidoCasada', $apellidoCasada);
        // }

        if (!empty($fechaNacimiento)) {
            $query->whereDate('fecha_nacimiento', $fechaNacimiento);
        }

        if (!empty($paisNacimiento)) {
            $query->where('cod_pais_nacimiento', $paisNacimiento);
        }

        // =========================
        // Resultado
        // =========================
        $registro = $query->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $registro
        ]);
    }

    public function BuscarPaises(){

        $request = $this->request;

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('099', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $pais = Paises::all();

        return response()->json($pais);


    }

    public function BuscarOcupaciones(){

        $request = $this->request;

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
            
        }

        if (!$this->common->usuariopermiso('099', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $ocupacion = Ocupacion::all();

        return response()->json($ocupacion);


    }

}
