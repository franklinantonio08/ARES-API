<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Migrantesinfo;
use App\Models\Ocupacion;
use App\Models\Paises;

use App\Models\SolicitudZLC;
use App\Models\SolicitudZLCArchivos;
use Illuminate\Support\Facades\Storage;

use App\Helpers\FotoHelper;
use App\Helpers\CommonHelper;
use App\Traits\Loggable;

use Illuminate\Support\Facades\Auth;
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

    public function GuardarSolicitud(Request $request){

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

        try {

            $result = DB::transaction(function () use ($request, $userId) {

                $solicitud = new SolicitudZLC();

                // ================================
                // DATOS PERSONALES
                // ================================

                $solicitud->primer_nombre = $request->PrimerNombre;
                $solicitud->segundo_nombre = $request->SegundoNombre;
                $solicitud->primer_apellido = $request->PrimerApellido;
                $solicitud->segundo_apellido = $request->SegundoApellido;
                $solicitud->nombre_usual = $request->NombreUsual;

                $solicitud->estado_civil = $request->EstadoCivil;
                $solicitud->nombre_conyuge = $request->NombreConyuge;
                $solicitud->nacionalidad_conyuge = $request->NacionalidadConyuge;

                $solicitud->fecha_nacimiento = $this->convertDate($request->FechaNacimiento);
                $solicitud->lugar_nacimiento = $request->LugarNacimiento;
                $solicitud->nacionalidad = $request->Nacionalidad;

                $solicitud->numero_identidad_personal = $request->NumeroIdentidadPersonal;

                $solicitud->nombre_padre = $request->NombrePadre;
                $solicitud->nacionalidad_padre = $request->NacionalidadPadre;

                $solicitud->nombre_madre = $request->NombreMadre;
                $solicitud->nacionalidad_madre = $request->NacionalidadMadre;

                $solicitud->ruex = $request->Ruex;

                // ================================
                // PASAPORTE
                // ================================

                $solicitud->numero_pasaporte = strtoupper($request->NumeroPasaporte);
                $solicitud->pais_emisor_pasaporte = $request->PaisEmisorPasaporte;
                $solicitud->fecha_expedicion_pasaporte = $this->convertDate($request->FechaExpedicionPasaporte);
                $solicitud->fecha_vencimiento_pasaporte = $this->convertDate($request->FechaVencimientoPasaporte);

                // ================================
                // RESIDENCIA
                // ================================

                $solicitud->pais_residencia = $request->PaisResidencia;
                $solicitud->estado_provincia_departamento = $request->EstadoProvinciaDepartamento;
                $solicitud->ciudad = $request->Ciudad;

                $solicitud->correo_electronico = $request->CorreoElectronico;

                // ================================
                // PROFESION
                // ================================

                $solicitud->ocupacion_profesion_actual = $request->OcupacionProfesionActual;
                $solicitud->lugar_trabajo = $request->LugarTrabajo;

                // ================================
                // CONTACTO EN PANAMA
                // ================================

                $solicitud->direccion_hospedaje_panama = $request->DireccionHospedajePanama;
                $solicitud->telefono_contacto_panama = $request->TelefonoContactoPanama;

                $solicitud->fecha_viaje_panama = $this->convertDate($request->FechaViajePanama);
                $solicitud->tiempo_estadia_panama = $request->TiempoEstadiaPanama;

                $solicitud->motivo_viaje = $request->MotivoViaje;

                // ================================
                // EMPRESA ZLC
                // ================================

                $solicitud->empresa_usuario_zlc_nombre = $request->Empresazlcnombre;
                $solicitud->empresa_usuario_zlc_clave = $request->Empresazlcclave;

                // ================================
                // OTROS
                // ================================

                $solicitud->usuario = $request->usuario ?? 'api';
                $solicitud->estado = 'Pendiente';
                $solicitud->usuario_id = $userId;

                $solicitud->save();

                // ================================
                // ARCHIVOS
                // ================================

                if (!empty($request->archivos)) {

                    foreach ($request->archivos as $archivo) {

                        $tempPath = storage_path(
                            "app/temp/visaszonalibre/" . $archivo['temp_file']
                        );

                        if (!file_exists($tempPath)) {
                            continue;
                        }

                        $ext = pathinfo($archivo['temp_file'], PATHINFO_EXTENSION);

                        $fileName = 'visaszonalibre_' . uniqid() . '.' . $ext;

                        Storage::disk('public')->put(
                            "visaszonalibre/$fileName",
                            fopen($tempPath, 'r')
                        );

                        unlink($tempPath);

                        SolicitudZLCArchivos::create([
                            'visa_zl_solicitud_id' => $solicitud->id,
                            'tipo' => $archivo['tipo'],
                            'archivo' => "visaszonalibre/$fileName",
                            'descripcion' => 'Documento adjunto',
                            'usuarioId' => $userId,
                            'estatus' => 'Activo'
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'id' => $solicitud->id
                ];
            });

            return response()->json($result);

        } catch (\Throwable $e) {

            \Log::error('Error GuardarSolicitud', ['ex' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error guardando solicitud',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    private function convertDate($date){
        if (!$date) return null;

        try {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function GuardarSolicitudArchivos(Request $request){
        
        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success'=>false,
                'message'=>'No autenticado'
            ],401);
        }

        if (!$request->hasFile('archivo')) {
            return response()->json([
                'success'=>false,
                'message'=>'Archivo requerido'
            ],422);
        }

        $file = $request->file('archivo');

        if (!$file->isValid()) {
            return response()->json([
                'success'=>false,
                'message'=>'Archivo inválido'
            ],422);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        $permitidos = ['jpg','jpeg','png','pdf'];

        if (!in_array($ext,$permitidos)) {
            return response()->json([
                'success'=>false,
                'message'=>'Formato no permitido'
            ],422);
        }

        // límite extra por seguridad
        if ($file->getSize() > (2 * 1024 * 1024)) {
            return response()->json([
                'success'=>false,
                'message'=>'Archivo supera 2MB'
            ],422);
        }

        $fileName = 'tmp_visa_'.uniqid().'.'.$ext;

        Storage::disk('local')->putFileAs(
            'temp/visaszonalibre',
            $file,
            $fileName
        );

        return response()->json([
            'success'=>true,
            'temp_file'=>$fileName,
            'tipo'=>$request->tipo
        ]);
    }

}
