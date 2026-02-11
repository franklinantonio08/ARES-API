<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\RuexInfo;
use App\Models\CarnetInfo;
use App\Models\Provincia;
use App\Models\Distrito;
use App\Models\Corregimiento;
use App\Models\Pais;
use App\Models\Operativo;
use App\Models\Acciones;
use App\Models\Motivos;
use App\Models\Impedimentos;

use App\Models\Infractoresincidencia;
use App\Models\AccionesInc;
use App\Models\MotivosInc;

use App\Models\Infractor;
use App\Models\Infractoresoperativo;

use App\Models\Infractorarchivo;
use App\Models\Infractorincidenciaarchivo;

use App\Helpers\FotoHelper;
use App\Helpers\CommonHelper;
use App\Traits\Loggable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DB;
use Excel;
use Carbon\Carbon;




class OperativoController extends Controller{

    private CommonHelper $common;

    public function __construct(CommonHelper $common){

        $this->common = $common;

    }


    private function fichaCompletaPorFiliacion(string $num_filiacion): ?array {

        $upper = fn($v) => $v ? mb_strtoupper(trim($v), 'UTF-8') : '';

        $title = fn($v) => $v
            ? mb_convert_case(mb_strtolower(trim($v), 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            : '';

        $fmtFecha = function ($f) {
            if (empty($f)) return null;
            try { return \Carbon\Carbon::parse($f)->format('Y-m-d'); }
            catch (\Throwable $e) { return null; }
        };

        $mapGenero = function ($g) {
            $g = mb_strtoupper(trim((string)$g), 'UTF-8');
            if ($g === 'M' || $g === 'MASCULINO') return 'M';
            if ($g === 'F' || $g === 'FEMENINO') return 'F';
            return '';
        };

        $ruex = RuexInfo::where('num_filiacion', $num_filiacion)->first();
        if (!$ruex) return null;


        $nacionalidad = Pais::orderBy('pais', 'asc')
            ->where('cod_pais', '=', $title($ruex->cod_pais_nacionalidad))
            ->first();

        $pais_nacimiento = Pais::orderBy('pais', 'asc')
            ->where('cod_pais', '=', $title($ruex->cod_pais_nacimiento))
            ->first();

        $ruexInfoGeneral = [
            'num_filiacion'     => (string) $ruex->num_filiacion,
            'primerNombre'      => $title($ruex->primerNombre),
            'segundoNombre'     => $title($ruex->segundoNombre),
            'primerApellido'    => $title($ruex->primerApellido),
            'segundoApellido'   => $title($ruex->segundoApellido),
            'casadaApellidos'   => $title($ruex->casadaApellidos),
            'genero'            => $mapGenero($ruex->genero),
            'pasaporte'         => $upper($ruex->pasaporte),
            'fecha_nacimiento'  => $fmtFecha($ruex->fecha_nacimiento),
            'cod_pais_nacionalidad'   => $title($ruex->cod_pais_nacionalidad),
            'pais_nacionalidad' => $nacionalidad->nacionalidad,
            'cod_pais_nacimiento' => $title($ruex->cod_pais_nacimiento),
            'pais_nacimiento'   => $nacionalidad->pais,
            'direccion'         => $title($ruex->direccion_panama),
        ];

        $carnet = CarnetInfo::where('num_filiacion', $num_filiacion)
            ->orderByRaw('CASE WHEN fecha_expedicion IS NULL THEN 1 ELSE 0 END, fecha_expedicion DESC')
            ->orderByRaw('CASE WHEN fecha_expiracion IS NULL THEN 1 ELSE 0 END, fecha_expiracion DESC')
            ->orderByRaw('CASE WHEN fecha_carga IS NULL THEN 1 ELSE 0 END, fecha_carga DESC')
            ->first();

        $carnetInfoGeneral = null;
        $fotoPerfilUrl = null;

        if ($carnet) {
            $carnetInfoGeneral = [
                'num_carnet'           => $carnet->num_carnet,
                'tipo_carnet'          => $carnet->tipo_carnet,
                'tipo_visa'            => $carnet->tipo_visa ? trim($carnet->tipo_visa) : null,
                'numero_tramite'       => $carnet->numero_tramite,
                'cotizacion'           => $carnet->cotizacion,
                'fecha_expedicion'     => $fmtFecha($carnet->fecha_expedicion),
                'fecha_expiracion'     => $fmtFecha($carnet->fecha_expiracion),
                'fecha_llegada_panama' => $fmtFecha($carnet->fecha_llegada_panama),
                'fecha_resolucion'     => $fmtFecha($carnet->fecha_resolucion),
                'fecha_notificacion'   => $fmtFecha($carnet->fecha_notificacion),
                'ruta_imagen'          => $carnet->ruta_Imagen,
                'tipo_solicitud'       => $title($carnet->tipo_reporte),
                'estatus_carnet'       => ($carnet->tipo_reporte === 'RESIDENTE PERMANENTE')
                    ? 'Vigente'
                    : (
                        $carnet->fecha_expiracion
                            ? (\Carbon\Carbon::parse($carnet->fecha_expiracion)->isPast() ? 'Vencido' : 'Vigente')
                            : 'Sin fecha de expiración'
                    ),
            ];

            $fotoPerfilUrl = FotoHelper::resolverFoto(
                $carnet->foto_url ?? null,
                $carnet->ruta_Imagen ?? null
            );
        }

        $impedimientos = Impedimentos::where('primerNombre', 'LIKE', '%' . $ruex->primerNombre . '%')
            ->where('primerApellido', 'LIKE', '%' . $ruex->primerApellido . '%')
            ->get();

        return [
            'ruex_model'    => $ruex, // solo para logging
            'ruex'          => $ruexInfoGeneral,
            'impedimientos' => $impedimientos,
            'carnet'        => $carnetInfoGeneral,
            'foto'          => $fotoPerfilUrl,
        ];
    }


    public function BuscarRuex(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json(['success' => false,'message' => 'No autenticado.','code' => 'UNAUTHENTICATED'], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json(['success' => false,'message' => $this->common->message ?? 'Acceso no autorizado.','code' => 'PERMISO_DENEGADO'], 403);
        }

        $num_filiacion = $request->input('ruex');

        $ficha = $this->fichaCompletaPorFiliacion((string)$num_filiacion);

        if (!$ficha) {
            return response()->json(['mensaje' => 'Registro no encontrado'], 404);
        }

        $ruexModel = $ficha['ruex_model'];

        // 🔥 AUDITORÍA EXACTA COMO LA TENÍAS
        $this->common->registrarConsultaValidacion(
            $request,
            'RUEX',
            [
                'num_filiacion'    => $num_filiacion,
                'primerNombre'     => $ruexModel->primerNombre ?? null,
                'primerApellido'   => $ruexModel->primerApellido ?? null,
                'fecha_nacimiento' => $ruexModel->fecha_nacimiento ?? null,
                'nacionalidad'     => $ruexModel->pais_nacionalidad ?? null,
            ],
            [
                'num_filiacion' => $ruexModel->num_filiacion,
                'pasaporte'     => $ruexModel->pasaporte,
            ],
            1
        );

        unset($ficha['ruex_model']);

        return response()->json($ficha);
    }


    public function BuscarPais(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

         $pais = Pais::orderBy('pais', 'asc')
                        ->get();

        return response()->json($pais);

    }
    
    public function BuscarOperativo(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $operativo = Operativo::where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($operativo);
    }

    public function BuscarAcciones(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $acciones = Acciones::where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($acciones);
    }

    public function BuscarMotivo(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $accionId = $request->input('accionId');

        $motivo = Motivos::where('accionId', $accionId)
                            ->where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($motivo);
    }


    public function BuscarProvincia(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $provincia = Provincia:: all();

        return response()->json($provincia);

    }

    public function BuscarDistrito(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $provinciaId = $request->input('provinciaId');

        $distritos = Distrito::where('provinciaId', $provinciaId)
                            ->orderBy('nombre', 'asc')
                            ->get();

        return response()->json($distritos);
    }

    public function BuscarCorregimiento(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

         $distritoId = $request->input('distritoId');

        $corregimiento = Corregimiento::where('distritoId', $distritoId)
                            ->orderBy('nombre', 'asc')
                            ->get();

        return response()->json($corregimiento);

    }

    public function BuscarPasaporte(Request $request){

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $pasaporte      = $request->input('pasaporte');
        $primerNombre   = $request->input('primerNombre');
        $primerApellido = $request->input('primerApellido');
        $nacionalidad   = $request->input('nacionalidad');
        $diaN           = $request->input('diaN');
        $MesN           = $request->input('MesN');
        $anioN          = $request->input('anioN');

        $fndia  = (int) ($diaN  ?? 0);
        $fnmes  = (int) ($MesN  ?? 0);
        $fnanio = (int) ($anioN ?? 0);

        $fecha_fn = null;
        if ($fndia || $fnmes || $fnanio) {
            if (!($fndia && $fnmes && $fnanio && checkdate($fnmes, $fndia, $fnanio))) {
                return response()->json(['mensaje' => 'Fecha de nacimiento inválida'], 422);
            }
            $fecha_fn = sprintf('%04d-%02d-%02d', $fnanio, $fnmes, $fndia);
        }

        // 🔎 Buscar coincidencias
        $coincidencias = RuexInfo::where('pasaporte', $pasaporte)
            ->where('primerNombre', $primerNombre)
            ->where('primerApellido', $primerApellido)
            ->where('fecha_nacimiento', $fecha_fn)
            ->where('pais_nacionalidad', $nacionalidad)
            ->get();

        $resultados = [];
        $salidaLog  = [];

        foreach ($coincidencias as $persona) {

            $ficha = $this->fichaCompletaPorFiliacion((string)$persona->num_filiacion);

            if ($ficha) {
                $ruexModel = $ficha['ruex_model'];

                $salidaLog[] = [
                    'num_filiacion' => $ruexModel->num_filiacion,
                    'pasaporte'     => $ruexModel->pasaporte,
                ];

                unset($ficha['ruex_model']);
                $resultados[] = $ficha;
            }
        }

        // 🧾 Auditoría EXACTA
        $this->common->registrarConsultaValidacion(
            $request,
            'PASAPORTE',
            [
                'pasaporte'        => $pasaporte,
                'primerNombre'     => $primerNombre,
                'primerApellido'   => $primerApellido,
                'fecha_nacimiento' => $fecha_fn,
                'nacionalidad'     => $nacionalidad,
            ],
            $salidaLog,
            count($salidaLog)
        );

        return response()->json([
            'resultados' => $resultados
        ]);
    }



    public function BuscarCedula(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $cedula        = $request->input('cedula');
        $primerNombre  = $request->input('primerNombre');
        $primerApellido= $request->input('primerApellido');
        $nacionalidad  = $request->input('nacionalidad');
        $diaN          = $request->input('diaN');
        $MesN          = $request->input('MesN');
        $anioN         = $request->input('anioN');

        $fndia  = (int) ($diaN  ?? 0);
        $fnmes  = (int) ($MesN  ?? 0);
        $fnanio = (int) ($anioN ?? 0);

        $fecha_fn = null;
        if ($fndia || $fnmes || $fnanio) {
            if (!($fndia && $fnmes && $fnanio && checkdate($fnmes, $fndia, $fnanio))) {
                return response()->json(['mensaje' => 'Fecha de nacimiento inválida'], 422);
            }
            $fecha_fn = sprintf('%04d-%02d-%02d', $fnanio, $fnmes, $fndia);
        }

        // 🔎 Coincidencias (igual que antes)
        $coincidencias = RuexInfo::where('primerNombre', $primerNombre)
            ->where('primerApellido', $primerApellido)
            ->where('fecha_nacimiento', $fecha_fn)
            ->where('pais_nacionalidad', $nacionalidad)
            ->get();

        $resultados = [];
        $salidaLog  = [];

        foreach ($coincidencias as $persona) {

            $ficha = $this->fichaCompletaPorFiliacion((string)$persona->num_filiacion);

            if ($ficha) {
                $ruexModel = $ficha['ruex_model'];

                $salidaLog[] = [
                    'num_filiacion' => $ruexModel->num_filiacion,
                    'pasaporte'     => $ruexModel->pasaporte,
                ];

                unset($ficha['ruex_model']);
                $resultados[] = $ficha;
            }
        }

        // 🧾 Auditoría SNM
        $this->common->registrarConsultaValidacion(
            $request,
            'CEDULA',
            [
                'cedula'          => $cedula,
                'primerNombre'    => $primerNombre,
                'primerApellido'  => $primerApellido,
                'fecha_nacimiento'=> $fecha_fn,
                'nacionalidad'    => $nacionalidad,
            ],
            $salidaLog,
            count($salidaLog)
        );

        return response()->json([
            'resultados' => $resultados
        ]);
    }


    // public function GuardaOperacion(Request $request) {

    
    //     $userId = $request->user()->id ?? Auth::id();

    //     if (!$userId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No autenticado.',
    //             'code'    => 'UNAUTHENTICATED'
    //         ], 401);
    //     }

    //     if (!$this->common->usuariopermiso('050', $userId)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $this->common->message ?? 'Acceso no autorizado.',
    //             'code'    => 'PERMISO_DENEGADO'
    //         ], 403);
    //     }

    //     $this->common->ensureSucursalOrFail();

    //     /* ==========================================================
    //     SOPORTE BASE64 (MOVIL)
    //     ========================================================== */

    //     $adjuntosBase64 = $request->input('adjuntos');

    //     if (!empty($adjuntosBase64) && is_array($adjuntosBase64)) {

    //         foreach ($adjuntosBase64 as $img) {

    //             $base64 = $img['base64'] ?? null;

    //             if (!$base64) continue;

    //             // Si viene con prefijo data:image/...
    //             if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
    //                 $base64 = substr($base64, strpos($base64, ',') + 1);
    //                 $ext = strtolower($type[1]);
    //                 if ($ext === 'jpeg') $ext = 'jpg';
    //             } else {
    //                 $ext = 'jpg'; // fallback
    //             }

    //             $imageData = base64_decode($base64);

    //             if ($imageData === false) {
    //                 throw new \RuntimeException('Error decodificando imagen Base64.');
    //             }

    //             // Validar tamaño (8MB)
    //             if (strlen($imageData) > (8 * 1024 * 1024)) {
    //                 throw new \RuntimeException('Imagen supera el tamaño permitido (8MB).');
    //             }

    //             // Generar nombre seguro SIEMPRE
    //             $filename = 'infractor_' . uniqid('', true) . '.' . $ext;

    //             Storage::put("public/infractores/$filename", $imageData);
    //         }
    //     }


    //     /* ==========================================================
    //     SOPORTE MULTIPART (WEB / POSTMAN)
    //     ========================================================== */

    //     if ($request->hasFile('adjuntos')) {

    //         foreach ($request->file('adjuntos') as $file) {

    //             if (!$file->isValid()) {
    //                 throw new \RuntimeException('Archivo adjunto inválido.');
    //             }

    //             $ext = strtolower($file->getClientOriginalExtension());
    //             $valid = ['png', 'jpg', 'jpeg'];

    //             if (!in_array($ext, $valid)) {
    //                 throw new \RuntimeException('Formato de imagen no permitido (solo png, jpg, jpeg).');
    //             }

    //             if ($file->getSize() > (8 * 1024 * 1024)) {
    //                 throw new \RuntimeException('Imagen supera el tamaño permitido (8MB).');
    //             }

    //             $filename = 'infractor_' . uniqid('', true) . '.' . $ext;
    //             $file->storeAs('public/infractores', $filename);
    //         }
    //     }

    //     /* ==========================================================
    //     RESTO DE TU LÓGICA (SIN CAMBIOS)
    //     ========================================================== */

    //     $inspectorId        = $request->input('inspectorId');
    //     $latitud            = $request->input('latitud');
    //     $longitud           = $request->input('longitud');
    //     $primerNombre       = $request->input('primerNombre');
    //     $segundoNombre      = $request->input('segundoNombre');
    //     $primerApellido     = $request->input('primerApellido');
    //     $segundoApellido    = $request->input('segundoApellido');

    //     $documento          = $request->input('documento');
    //     $pasaporte          = $request->input('pasaporte');
    //     $fechaNacimiento    = $request->input('fechaNacimiento');
    //     $genero             = $request->input('genero');

    //     $paisNacimiento     = $request->input('paisNacimiento');
    //     $nacionalidad       = $request->input('nacionalidad');
    //     $operativoId        = $request->input('operativo');
    //     $accionId           = $request->input('accionId');
    //     $motivoId           = $request->input('motivo');

    //     $provinciaId        = $request->input('provinciaId');
    //     $distritoId         = $request->input('distritoId');
    //     $corregimientoId    = $request->input('corregimientoId');
    //     $lugarCaptacion     = $request->input('lugarCaptacion');
    //     $fechaCitacion      = $request->input('fechaCitacion');
    //     $comentario         = $request->input('comentario');

    //     $pais = DB::table('rid_paises')->where('id', $paisNacimiento)->first();

    //     if (!$pais) {
    //         throw new \RuntimeException('País no encontrado.');
    //     }

    //     $region = $pais->region_id;

    //     if ($genero === 'M') {
    //         $genero = 'Masculino';
    //     } else {
    //         $genero = 'Femenino';
    //     }

    //     $res_id = null;
    //     $num_filiacion = null;

    //     $ruex = RuexInfo::where('pasaporte', $pasaporte)
    //         ->where('primerNombre', $primerNombre)
    //         ->where('primerApellido', $primerApellido)
    //         ->where('fecha_nacimiento', $fechaNacimiento)
    //         ->first();

    //     if (!empty($ruex)) {
    //         $num_filiacion = $ruex->num_filiacion;
    //     }

    //     $infractor = new Infractor();
    //     $infractor->primerNombre    = trim($primerNombre);
    //     $infractor->segundoNombre   = trim($segundoNombre ?? '');
    //     $infractor->primerApellido  = trim($primerApellido);
    //     $infractor->segundoApellido = trim($segundoApellido ?? '');
    //     $infractor->codigo          = trim($num_filiacion ?? '');
    //     $infractor->documento       = strtoupper(trim($pasaporte ?? ''));
    //     $infractor->regionId        = $region;
    //     $infractor->paisId          = (int) $paisNacimiento;
    //     $infractor->nacionalidadId  = (int) $nacionalidad;
    //     $infractor->fechaNacimiento = $fechaNacimiento;
    //     $infractor->genero          = $genero;

    //     $infractor->estatus = ($accionId === '4') ? 'Aprobado' : 'Pendiente';

    //     $infractor->usuarioId = $inspectorId;
    //     $infractor->save();

    //     if (empty($operativoId)) {

    //         $incidencia = new Infractoresincidencia();
    //         $incidencia->infractorId          = $infractor->id;
    //         $incidencia->incidencia_motivo_Id = (int) $motivoId;
    //         $incidencia->incidencia_accion_Id = (int) $accionId;
    //         $incidencia->provinciaId          = (int) $provinciaId;
    //         $incidencia->distritoId           = (int) $distritoId;
    //         $incidencia->corregimientoId      = (int) $corregimientoId;
    //         $incidencia->direccion            = trim($lugarCaptacion);
    //         $incidencia->fechacitacion        = $fechaCitacion;
    //         $incidencia->estatus              = ($accionId === '1') ? 'Aprobado' : 'Pendiente';
    //         $incidencia->infoextra            = trim($comentario ?? '');
    //         $incidencia->usuarioId            = $inspectorId;
    //         $incidencia->save();

    //         $res_id = $incidencia->id;

    //     } else {

    //         $operativo = DB::table('operativo')->where('id', $operativoId)->first();

    //         if (!$operativo) {
    //             throw new \RuntimeException('Operativo no encontrado.');
    //         }

    //         $unidadSolicitante = $operativo->unidaSolicitanteId ?? null;

    //         $infractorop = new Infractoresoperativo();
    //         $infractorop->infractorId         = $infractor->id;
    //         $infractorop->operativoId         = (int) $operativoId;
    //         $infractorop->unidadSolicitanteId = $unidadSolicitante;
    //         $infractorop->motivoId            = (int) $motivoId;
    //         $infractorop->estatusId           = (int) $accionId;
    //         $infractorop->provinciaId         = (int) $provinciaId;
    //         $infractorop->distritoId          = (int) $distritoId;
    //         $infractorop->corregimientoId     = (int) $corregimientoId;
    //         $infractorop->direccion           = trim($lugarCaptacion);
    //         $infractorop->fechacitacion       = $fechaCitacion;
    //         $infractorop->estatus             = ($accionId === '4') ? 'Aprobado' : 'Pendiente';
    //         $infractorop->infoextra           = trim($comentario ?? '');
    //         $infractorop->usuarioId           = $inspectorId;
    //         $infractorop->save();

    //         $res_id = $infractorop->id;
    //     }

    //     return response()->json($res_id);
    // }


    public function GuardaOperacion(Request $request){

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $this->common->ensureSucursalOrFail();

        // ==========================================================
        // DATA
        // ==========================================================
        $inspectorId        = $request->input('inspectorId');
        $primerNombre       = $request->input('primerNombre');
        $segundoNombre      = $request->input('segundoNombre');
        $primerApellido     = $request->input('primerApellido');
        $segundoApellido    = $request->input('segundoApellido');

        $pasaporte          = $request->input('pasaporte');
        $fechaNacimiento    = $request->input('fechaNacimiento');
        $genero             = $request->input('genero'); // 'M' / 'F'

        $paisNacimiento     = $request->input('paisNacimiento'); // id
        $nacionalidad       = $request->input('nacionalidad');   // id
        $operativoId        = $request->input('operativo');      // puede venir null
        $accionId           = $request->input('accionId');       // estatusId o incidencia_accion
        $motivoId           = $request->input('motivo');

        $provinciaId        = $request->input('provinciaId');
        $distritoId         = $request->input('distritoId');
        $corregimientoId    = $request->input('corregimientoId');
        $lugarCaptacion     = $request->input('lugarCaptacion');
        $fechaCitacion      = $request->input('fechaCitacion');
        $comentario         = $request->input('comentario');

        // ==========================================================
        // VALIDACIONES BÁSICAS (mínimas para no romper tu lógica)
        // ==========================================================
        $pais = DB::table('rid_paises')->where('id', $paisNacimiento)->first();
        if (!$pais) {
            return response()->json(['success' => false, 'message' => 'País no encontrado.'], 422);
        }

        $region = $pais->region_id;

        if ($genero === 'M') $genero = 'Masculino';
        else if ($genero === 'F') $genero = 'Femenino';

        // ==========================================================
        // TRANSACCIÓN: primero crea registros, luego guarda archivos
        // ==========================================================
        try {

            $result = DB::transaction(function () use (
                $request,
                $region,
                $paisNacimiento,
                $nacionalidad,
                $pasaporte,
                $primerNombre,
                $segundoNombre,
                $primerApellido,
                $segundoApellido,
                $fechaNacimiento,
                $genero,
                $inspectorId,
                $operativoId,
                $accionId,
                $motivoId,
                $provinciaId,
                $distritoId,
                $corregimientoId,
                $lugarCaptacion,
                $fechaCitacion,
                $comentario
            ) {

                // ---------- Buscar RUEX para num_filiacion ----------
                $num_filiacion = null;

                $ruex = RuexInfo::where('pasaporte', $pasaporte)
                    ->where('primerNombre', $primerNombre)
                    ->where('primerApellido', $primerApellido)
                    ->where('fecha_nacimiento', $fechaNacimiento)
                    ->first();

                if (!empty($ruex)) {
                    $num_filiacion = $ruex->num_filiacion;
                }

                // ---------- Infractor ----------
                $infractor = new Infractor();
                $infractor->primerNombre    = trim($primerNombre);
                $infractor->segundoNombre   = trim($segundoNombre ?? '');
                $infractor->primerApellido  = trim($primerApellido);
                $infractor->segundoApellido = trim($segundoApellido ?? '');
                $infractor->codigo          = trim($num_filiacion ?? '');
                $infractor->documento       = strtoupper(trim($pasaporte ?? ''));
                $infractor->regionId        = $region;
                $infractor->paisId          = (int) $paisNacimiento;
                $infractor->nacionalidadId  = (int) $nacionalidad;
                $infractor->fechaNacimiento = $fechaNacimiento;
                $infractor->genero          = $genero;
                $infractor->estatus         = ($accionId === '4' || $accionId === 4) ? 'Aprobado' : 'Pendiente';
                $infractor->usuarioId       = $inspectorId;
                $infractor->save();

                // ---------- Operativo o Incidencia ----------
                $tipoEvento = null;
                $res_id = null;

                if (empty($operativoId)) {

                    $tipoEvento = 'INCIDENCIA';

                    $incidencia = new Infractoresincidencia();
                    $incidencia->infractorId          = $infractor->id;
                    $incidencia->incidencia_motivo_Id = (int) $motivoId;
                    $incidencia->incidencia_accion_Id = (int) $accionId;
                    $incidencia->provinciaId          = (int) $provinciaId;
                    $incidencia->distritoId           = (int) $distritoId;
                    $incidencia->corregimientoId      = (int) $corregimientoId;
                    $incidencia->direccion            = trim($lugarCaptacion);
                    $incidencia->fechacitacion        = $fechaCitacion;
                    $incidencia->estatus              = ((string)$accionId === '1') ? 'Aprobado' : 'Pendiente';
                    $incidencia->infoextra            = trim($comentario ?? '');
                    $incidencia->usuarioId            = $inspectorId;
                    $incidencia->save();

                    $res_id = $incidencia->id;

                } else {

                    $tipoEvento = 'OPERATIVO';

                    $operativo = DB::table('operativo')->where('id', $operativoId)->first();
                    if (!$operativo) {
                        throw new \RuntimeException('Operativo no encontrado.');
                    }

                    $unidadSolicitante = $operativo->unidaSolicitanteId ?? null;

                    $infractorop = new Infractoresoperativo();
                    $infractorop->infractorId         = $infractor->id;
                    $infractorop->operativoId         = (int) $operativoId;
                    $infractorop->unidadSolicitanteId = $unidadSolicitante;
                    $infractorop->motivoId            = (int) $motivoId;
                    $infractorop->estatusId           = (int) $accionId;
                    $infractorop->provinciaId         = (int) $provinciaId;
                    $infractorop->distritoId          = (int) $distritoId;
                    $infractorop->corregimientoId     = (int) $corregimientoId;
                    $infractorop->direccion           = trim($lugarCaptacion);
                    $infractorop->fechacitacion       = $fechaCitacion;
                    $infractorop->estatus             = ((string)$accionId === '4') ? 'Aprobado' : 'Pendiente';
                    $infractorop->infoextra           = trim($comentario ?? '');
                    $infractorop->usuarioId           = $inspectorId;
                    $infractorop->save();

                    $res_id = $infractorop->id;
                }

                // ==========================================================
                // GUARDAR ARCHIVOS (BASE64 + MULTIPART) Y REGISTRAR EN TABLA
                // ==========================================================

                // 1) BASE64
                $adjuntosBase64 = $request->input('adjuntos');

                if (!empty($adjuntosBase64) && is_array($adjuntosBase64)) {

                    foreach ($adjuntosBase64 as $img) {

                        $base64 = $img['base64'] ?? null;
                        if (!$base64) continue;

                        $ext = 'jpg';

                        if (strpos($base64, 'data:image') === 0) {
                            preg_match('/^data:image\/(\w+);base64,/', $base64, $type);
                            $base64 = substr($base64, strpos($base64, ',') + 1);
                            $ext = strtolower($type[1] ?? 'jpg');
                            if ($ext === 'jpeg') $ext = 'jpg';
                        }

                        $data = base64_decode($base64, true);
                        if ($data === false) {
                            throw new \RuntimeException('Error decodificando imagen Base64.');
                        }
                        if (strlen($data) > 8 * 1024 * 1024) {
                            throw new \RuntimeException('Imagen supera el tamaño permitido (8MB).');
                        }

                        $fileName = ($tipoEvento === 'INCIDENCIA' ? 'incidencia_' : 'infractor_') . uniqid('', true) . '.' . $ext;

                        if ($tipoEvento === 'INCIDENCIA') {
                            Storage::disk('public')->put("incidencias/$fileName", $data);

                            Infractorincidenciaarchivo::create([
                                'infractores_incidencias_id' => $res_id,
                                'tipo'        => $ext,
                                'archivo'     => "incidencias/$fileName",
                                'descripcion' => 'Imagen Adjunta',
                                'usuarioId'   => $inspectorId,
                                'estatus'     => 'Activo',
                            ]);

                        } else {
                            Storage::disk('public')->put("infractores/$fileName", $data);

                            Infractorarchivo::create([
                                'infractores_operativos_id' => $res_id,
                                'tipo'        => $ext,
                                'archivo'     => "infractores/$fileName",
                                'descripcion' => 'Imagen Adjunta',
                                'usuarioId'   => $inspectorId,
                                'estatus'     => 'Activo',
                            ]);
                        }
                    }
                }

                // 2) MULTIPART
                if ($request->hasFile('adjuntos')) {

                    foreach ($request->file('adjuntos') as $file) {

                        if (!$file->isValid()) {
                            throw new \RuntimeException('Archivo adjunto inválido.');
                        }

                        $ext = strtolower($file->getClientOriginalExtension());
                        $valid = ['png', 'jpg', 'jpeg'];

                        if (!in_array($ext, $valid)) {
                            throw new \RuntimeException('Formato de imagen no permitido (solo png, jpg, jpeg).');
                        }

                        if ($file->getSize() > (8 * 1024 * 1024)) {
                            throw new \RuntimeException('Imagen supera el tamaño permitido (8MB).');
                        }

                        if ($ext === 'jpeg') $ext = 'jpg';

                        $fileName = ($tipoEvento === 'INCIDENCIA' ? 'incidencia_' : 'infractor_') . uniqid('', true) . '.' . $ext;

                        if ($tipoEvento === 'INCIDENCIA') {
                            $file->storeAs('public/incidencias', $fileName);

                            Infractorincidenciaarchivo::create([
                                'infractores_incidencias_id' => $res_id,
                                'tipo'        => $ext,
                                'archivo'     => "incidencias/$fileName",
                                'descripcion' => 'Imagen Adjunta',
                                'usuarioId'   => $inspectorId,
                                'estatus'     => 'Activo',
                            ]);

                        } else {
                            $file->storeAs('public/infractores', $fileName);

                            Infractorarchivo::create([
                                'infractores_operativos_id' => $res_id,
                                'tipo'        => $ext,
                                'archivo'     => "infractores/$fileName",
                                'descripcion' => 'Imagen Adjunta',
                                'usuarioId'   => $inspectorId,
                                'estatus'     => 'Activo',
                            ]);
                        }
                    }
                }

                return [
                    'tipo' => $tipoEvento,
                    'id'   => $res_id,
                ];
            });

            return response()->json($result['id']);

            
        } catch (\Throwable $e) {

            \Log::error('Error GuardaOperacion', ['ex' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Error guardando operación.',
                'detail'  => $e->getMessage(),
            ], 500);
        }
    }



    public function ActualizaOperacion(Request $request, $id)
    {
        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $this->common->ensureSucursalOrFail();

        // ============================
        // 1. Buscar operación existente
        // ============================
        $infractorop = Infractoresoperativo::find($id);

        if (!$infractorop) {
            return response()->json([
                'success' => false,
                'message' => 'Operación no encontrada.',
                'code'    => 'NOT_FOUND'
            ], 404);
        }

        $infractor = Infractor::find($infractorop->infractorId);

        if (!$infractor) {
            return response()->json([
                'success' => false,
                'message' => 'Infractor asociado no encontrado.',
                'code'    => 'NOT_FOUND_INFRACTOR'
            ], 404);
        }

        // ============================
        // 2. Tomar datos del request
        // ============================

        $inspectorId        = $request->input('inspectorId');

        $latitud            = $request->input('latitud');
        $longitud           = $request->input('longitud');
        $primerNombre       = $request->input('primerNombre');
        $segundoNombre      = $request->input('segundoNombre');
        $primerApellido     = $request->input('primerApellido');
        $segundoApellido    = $request->input('segundoApellido');

        $documento          = $request->input('documento');
        $pasaporte          = $request->input('pasaporte');
        $fechaNacimiento    = $request->input('fechaNacimiento');
        $genero             = $request->input('genero');

        $paisNacimiento     = $request->input('paisNacimiento');
        $nacionalidad       = $request->input('nacionalidad');
        $operativoId        = $request->input('operativo');
        $accionId           = $request->input('accionId');
        $motivoId           = $request->input('motivo');

        $provinciaId        = $request->input('provinciaId');
        $distritoId         = $request->input('distritoId');
        $corregimientoId    = $request->input('corregimientoId');
        $lugarCaptacion     = $request->input('lugarCaptacion');
        $fechaCitacion      = $request->input('fechaCitacion');
        $comentario         = $request->input('comentario');

        // ============================
        // 3. Validar país y operativo
        // ============================

        $pais = DB::table('rid_paises')->where('id', $paisNacimiento)->first();
        if (!$pais) {
            return response()->json([
                'success' => false,
                'message' => 'País no encontrado.',
                'code'    => 'PAIS_NO_ENCONTRADO'
            ], 422);
        }
        $region = $pais->region_id;

        $operativo = DB::table('operativo')->where('id', $operativoId)->first();
        if (!$operativo) {
            return response()->json([
                'success' => false,
                'message' => 'Operativo no encontrado.',
                'code'    => 'OPERATIVO_NO_ENCONTRADO'
            ], 422);
        }
        $unidadSolicitante = $operativo->unidaSolicitanteId ?? null;

        if ($genero === 'M') {
            $genero = 'Masculino';
        } else {
            $genero = 'Femenino';
        }

        try {
            DB::beginTransaction();

            // ============================
            // 4. Actualizar INFRCTOR
            // ============================

            $infractor->primerNombre    = trim($primerNombre);
            $infractor->segundoNombre   = trim($segundoNombre ?? '');
            $infractor->primerApellido  = trim($primerApellido);
            $infractor->segundoApellido = trim($segundoApellido ?? '');
            $infractor->documento       = strtoupper(trim($documento));
            $infractor->regionId        = $region;
            $infractor->paisId          = (int) $paisNacimiento;
            $infractor->nacionalidadId  = (int) $nacionalidad;
            $infractor->fechaNacimiento = $fechaNacimiento;
            $infractor->genero          = $genero;

            if ($accionId === '4') {
                $infractor->estatus = 'Aprobado';
            } else {
                $infractor->estatus = 'Pendiente';
            }

            $infractor->usuarioId = $inspectorId;
            $infractor->save();

            // ============================
            // 5. Actualizar INFRACTOR_OPERATIVO
            // ============================

            $infractorop->infractorId         = $infractor->id;
            $infractorop->operativoId         = (int) $operativoId;
            $infractorop->unidadSolicitanteId = $unidadSolicitante;
            $infractorop->motivoId            = (int) $motivoId;
            $infractorop->estatusId           = (int) $accionId;
            $infractorop->provinciaId         = (int) $provinciaId;
            $infractorop->distritoId          = (int) $distritoId;
            $infractorop->corregimientoId     = (int) $corregimientoId;
            $infractorop->direccion           = trim($lugarCaptacion);
            $infractorop->fechacitacion       = $fechaCitacion;

            // OJO: aquí uso $accionId en vez de $request->estatus
            if ($accionId === '4') {
                $infractorop->estatus = 'Aprobado';
            } else {
                $infractorop->estatus = 'Pendiente';
            }

            $infractorop->infoextra = trim($comentario ?? '');
            $infractorop->usuarioId = $inspectorId;
            $infractorop->save();

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Operación actualizada correctamente.',
                'operacionId'   => $infractorop->id,
                'infractorId'   => $infractor->id
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la operación.',
                'error'   => $e->getMessage(),
                'code'    => 'ERROR_UPDATE'
            ], 500);
        }
    }

    
    public function Estadistica(Request $request){
        // =========================
        // 1. Usuario autenticado
        // =========================
        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        // =========================
        // 2. Permisos
        // =========================
        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        // =========================
        // 3. Validar sucursal
        // =========================
        $this->common->ensureSucursalOrFail();

        // =========================
        // 4. Fecha HOY
        // =========================
        $hoy = Carbon::today();

        // ======================================================
        // 5. OPERATIVOS
        // ======================================================
        $operativos = Infractoresoperativo::query()
            ->where('usuarioId', $userId)
            ->whereDate('created_at', $hoy)
            ->with([
                'infractor:id,primerNombre,primerApellido,documento,nacionalidadId',
                'infractor.nacionalidad:id,pais',
                'motivo:id,descripcion',
                'operativo:id,descripcion',
                'provincia:id,nombre',
                'usuario:id,name,lastName',
                'verificador:id,name,lastName'
            ])
            ->get()
            ->map(function ($io) {
                return [
                    'id'           => $io->id,
                    'tipo'         => 'OPERATIVO',

                    'nombre'       => trim(($io->infractor->primerNombre ?? '') . ' ' . ($io->infractor->primerApellido ?? '')),
                    'documento'    => $io->infractor->documento ?? '',
                    'nacionalidad' => $io->infractor->nacionalidad->pais ?? '',

                    'motivo'       => $io->motivo->descripcion ?? '',
                    'operativo'    => $io->operativo->descripcion ?? '',
                    'provincia'    => $io->provincia->nombre ?? '',

                    'funcionario'  => trim(($io->usuario->name ?? '') . ' ' . ($io->usuario->lastName ?? '')),
                    'aprobadoPor'  => trim(($io->verificador->name ?? '') . ' ' . ($io->verificador->lastName ?? '')),

                    'estado'       => $io->estatus,
                    'ts'           => optional($io->created_at)->toDateTimeString(),
                ];
            });

        // ======================================================
        // 6. INCIDENCIAS
        // ======================================================
        $incidencias = Infractoresincidencia::query()
            ->where('usuarioId', $userId)
            ->whereDate('created_at', $hoy)
            ->with([
                'infractor:id,primerNombre,primerApellido,documento,nacionalidadId',
                'infractor.nacionalidad:id,pais',
                'motivo:id,descripcion',
                'provincia:id,nombre',
                'usuario:id,name,lastName',
                'verificador:id,name,lastName'
            ])
            ->get()
            ->map(function ($ii) {
                return [
                    'id'           => $ii->id,
                    'tipo'         => 'INCIDENCIA',

                    'nombre'       => trim(($ii->infractor->primerNombre ?? '') . ' ' . ($ii->infractor->primerApellido ?? '')),
                    'documento'    => $ii->infractor->documento ?? '',
                    'nacionalidad' => $ii->infractor->nacionalidad->pais ?? '',

                    'motivo'       => $ii->motivo->descripcion ?? '',
                    'operativo'    => '—', // no aplica
                    'provincia'    => $ii->provincia->nombre ?? '',

                    'funcionario'  => trim(($ii->usuario->name ?? '') . ' ' . ($ii->usuario->lastName ?? '')),
                    'aprobadoPor'  => trim(($ii->verificador->name ?? '') . ' ' . ($ii->verificador->lastName ?? '')),

                    'estado'       => $ii->estatus,
                    'ts'           => optional($ii->created_at)->toDateTimeString(),
                ];
            });

        // ======================================================
        // 7. UNIFICAR + ORDENAR
        // ======================================================

        $resultado = collect($operativos)
            ->merge(collect($incidencias))
            ->sortByDesc('ts')
            ->values();

        // =========================
        // 8. Respuesta
        // =========================
        return response()->json($resultado);
    }

  
    public function BuscarAccionesInc(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $acciones = AccionesInc::where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($acciones);
    }

    public function BuscarMotivoInc(Request $request) {

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code'    => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $accionId = $request->input('accionId');

        $motivo = MotivosInc::where('accionId', $accionId)
                            ->where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($motivo);
    }

     
}
