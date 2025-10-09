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

use App\Models\Infractor;
use App\Models\Infractoresoperativo;

use App\Helpers\FotoHelper;
use App\Helpers\CommonHelper;

use DB;
use Excel;
use Carbon\Carbon;

class OperativoController extends Controller
{
    

    public function BuscarRuex(Request $request){

        $num_filiacion = $request->input('ruex');

        $ruex = RuexInfo::select([
            'num_filiacion',
            'primerNombre',
            'segundoNombre',
            'primerApellido',
            'segundoApellido',
            'casadaApellidos',
            'genero',
            'pasaporte',
            'fecha_nacimiento',
            'pais_nacionalidad',
            'pais_nacimiento',
        ])
        ->where('num_filiacion', $num_filiacion)
        ->first();

        if (!$ruex) {
            return response()->json(['mensaje' => 'Registro no encontrado'], 404);
        }

        $upper    = fn($v) => $v ? mb_strtoupper(trim($v), 'UTF-8') : '';
        $title    = fn($v) => $v ? mb_convert_case(mb_strtolower(trim($v), 'UTF-8'), MB_CASE_TITLE, 'UTF-8') : '';
        $fmtFecha = function ($f) {
            if (empty($f)) return null;
            try { return Carbon::parse($f)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
        };
        $mapGenero = function ($g) {
            $g = mb_strtoupper(trim((string)$g), 'UTF-8');
            if ($g === 'M' || $g === 'MASCULINO') return 'M';
            if ($g === 'F' || $g === 'FEMENINO') return 'F';
            return '';
        };

        $ruexInfoGeneral = [
            'num_filiacion'     => (string) $ruex->num_filiacion,
            'primerNombre'      => $title($ruex->primerNombre),
            'segundoNombre'     => $title($ruex->segundoNombre),
            'primerApellido'    => $title($ruex->primerApellido),
            'segundoApellido'   => $title($ruex->segundoApellido),
            'casadaApellidos'   => $title($ruex->casadaApellidos),
            'genero'            => $mapGenero($ruex->genero),      // 'M' / 'F'
            'pasaporte'         => $upper($ruex->pasaporte),       // como pediste en MAYÚSCULAS
            'fecha_nacimiento'  => $fmtFecha($ruex->fecha_nacimiento),
            'pais_nacionalidad' => $title($ruex->pais_nacionalidad),
            'pais_nacimiento'   => $title($ruex->pais_nacimiento),
        ];

        $carnet = CarnetInfo::where('num_filiacion', $num_filiacion)
            ->orderByRaw('
                CASE WHEN fecha_expedicion IS NULL THEN 1 ELSE 0 END, fecha_expedicion DESC
            ')
            ->orderByRaw('
                CASE WHEN fecha_expiracion IS NULL THEN 1 ELSE 0 END, fecha_expiracion DESC
            ')
            ->orderByRaw('
                CASE WHEN fecha_carga IS NULL THEN 1 ELSE 0 END, fecha_carga DESC
            ')
            ->first();

        $carnetInfoGeneral = null;      

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
                // 'impreso'              => is_null($carnet->impreso) ? null : (bool)$carnet->impreso,
                'ruta_imagen'          => $carnet->ruta_Imagen,
                'tipo_solicitud'       => $title($carnet->tipo_reporte),
                'estatus_carnet'       => ($carnet->tipo_reporte === 'RESIDENTE PERMANENTE')
                                            ? 'Vigente'
                                            : (
                                                $carnet->fecha_expiracion
                                                    ? (\Carbon\Carbon::parse($carnet->fecha_expiracion)->isPast()
                                                        ? 'Vencido'
                                                        : 'Vigente')
                                                    : 'Sin fecha de expiración'
                                            ),
            ];
        }

        $fotoPerfilUrl = null;
        if ($carnet) {
            $fotoPerfilUrl = FotoHelper::resolverFoto(
                $carnet->foto_url ?? null,
                $carnet->ruta_Imagen ?? null
            );
        }


        $impedimientos = Impedimentos::select([
            'cod_impedimento',
            'cod_impedido',
            'primerNombre',
            'segundoNombre',
            'primerApellido',
            'segundoApellido',
            'genero',
            'fecha_nacimiento',
            'nacionalidad',
            'cod_pais_nacional',
            'num_oficio',
            'observacion',
            'nombre_autoridad',
            'nom_accion',
            'nom_alerta',
        ])
        ->where('primerNombre', 'LIKE', '%' . $ruex->primerNombre . '%')
        ->where('primerApellido', 'LIKE', '%' . $ruex->primerApellido . '%')
        ->get();      

        return response()->json([
            'ruex' => $ruexInfoGeneral,
            'impedimientos' => $impedimientos,
            'carnet' => $carnetInfoGeneral,
            'foto' => $fotoPerfilUrl,
        ]);
    }

    public function BuscarPais(Request $request) {

         $pais = Pais::orderBy('pais', 'asc')
                        ->get();

        return response()->json($pais);

    }
    
    public function BuscarOperativo(Request $request) {

        // $provinciaId = $request->input('provinciaId');

        $operativo = Operativo::where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($operativo);
    }

    public function BuscarAcciones(Request $request) {

        // $provinciaId = $request->input('provinciaId');

        $acciones = Acciones::where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($acciones);
    }

    public function BuscarMotivo(Request $request) {

        $accionId = $request->input('accionId');

        $motivo = Motivos::where('accionId', $accionId)
                            ->where('estatus', '=', 'Activo')
                            ->orderBy('descripcion', 'asc')
                            ->get();

        return response()->json($motivo);
    }


    public function BuscarProvincia(Request $request) {

        return Provincia:: all();

    }

    public function BuscarDistrito(Request $request) {

        $provinciaId = $request->input('provinciaId');

        $distritos = Distrito::where('provinciaId', $provinciaId)
                            ->orderBy('nombre', 'asc')
                            ->get();

        return response()->json($distritos);
    }

    public function BuscarCorregimiento(Request $request) {

        //  return Provincia:: all();

         $distritoId = $request->input('distritoId');

        $corregimiento = Corregimiento::where('distritoId', $distritoId)
                            ->orderBy('nombre', 'asc')
                            ->get();

        return response()->json($corregimiento);

    }

    public function BuscarPasaporte(Request $request) {

        //  return Provincia:: all();

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
                return back()->withErrors('Fecha de nacimiento inválida')->withInput();
            }
            $fecha_fn = sprintf('%04d-%02d-%02d', $fnanio, $fnmes, $fndia);
        }

        $ruex = RuexInfo::where('pasaporte', '=',$pasaporte)
        ->where('primerNombre', '=',$primerNombre)
        ->where('primerApellido', '=',$primerApellido)
        ->where('fecha_nacimiento', '=',$fecha_fn)
        ->where('pais_nacionalidad', '=',$nacionalidad)
        ->orderBy('primerNombre', 'asc')
        // ->select([
        //     'num_filiacion',
        //     'primerNombre',
        //     'segundoNombre',
        //     'primerApellido',
        //     'segundoApellido',
        //     'casadaApellidos',
        //     'genero',
        //     'pasaporte',
        //     'fecha_nacimiento',
        //     'pais_nacionalidad',
        //     'pais_nacimiento',
        // ])        
        ->get();

         $impedimientos = Impedimentos::select([
            'cod_impedimento',
            'cod_impedido',
            'primerNombre',
            'segundoNombre',
            'primerApellido',
            'segundoApellido',
            'genero',
            'fecha_nacimiento',
            'nacionalidad',
            'cod_pais_nacional',
            'num_oficio',
            'observacion',
            'nombre_autoridad',
            'nom_accion',
            'nom_alerta',
        ])
        ->where('primerNombre', 'LIKE', '%' . $primerNombre . '%')
        ->where('primerApellido', 'LIKE', '%' . $primerApellido . '%')
        ->get();

        return response()->json([
            'ruex' => $ruex,
            'impedimientos' => $impedimientos,          
        ]);

    }

    public function GuardaOperacion(Request $request) {

        // $this->common->ensureSucursalOrFail();

        //$datos = $request->all();


        // $pasaporte      = $request->input('fecha');
        $inspectorId    = $request->input('inspectorId');

        $latitud        = $request->input('latitud');
        $longitud       = $request->input('longitud');
        $primerNombre   = $request->input('primerNombre');
        $segundoNombre  = $request->input('segundoNombre');
        $primerApellido = $request->input('primerApellido');
        $segundoApellido   = $request->input('segundoApellido');

        $documento      = $request->input('documento');
        $pasaporte      = $request->input('pasaporte');
        $fechaNacimiento   = $request->input('fechaNacimiento');
        $genero         = $request->input('genero');

        $paisNacimiento = $request->input('paisNacimiento');
        $nacionalidad   = $request->input('nacionalidad');
        $operativoId    = $request->input('operativo');
        $accionId       = $request->input('accionId');
        $motivoId       = $request->input('motivo');

        $provinciaId    = $request->input('provinciaId');
        $distritoId     = $request->input('distritoId');
        $corregimientoId = $request->input('corregimientoId');
        $lugarCaptacion = $request->input('lugarCaptacion');
        $fechaCitacion  = $request->input('fechaCitacion');
        $comentario     = $request->input('comentario');

        $pais = DB::table('pais')->where('id', $this->request->pais)->first();

        if (!$pais) {
            throw new \RuntimeException('País no encontrado.');
        }
        $region = $pais->region_id;

        if($genero === 'M'){
            $genero = 'Masculino';
        }else{
            $genero = 'Femenino';
        }


        $infractor = new Infractor();
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

        if($accionId === '4'){
            $infractor->estatus         = 'Aprobado';
        }else{
            $infractor->estatus         = 'Pendiente';
        }

        $infractor->usuarioId       = $inspectorId;
        $infractor->save();


        // $infractorop = new Infractoresoperativo();
        // $infractorop->infractorId         = $infractor->id;
        // $infractorop->operativoId         = (int) $operativo;
        // $infractorop->unidadSolicitanteId = $unidadSolicitante;
        // $infractorop->motivoId            = (int) $motivoId;
        // $infractorop->estatusId           = (int) $estatus;
        // $infractorop->provinciaId         = (int) $this->request->provincia;
        // $infractorop->distritoId          = (int) $this->request->distrito;
        // $infractorop->corregimientoId     = (int) $this->request->corregimiento;
        // $infractorop->direccion           = trim($this->request->direccion);
        // $infractorop->fechacitacion       = $fecha_fc;

        // if($this->request->estatus === '4'){
        //     $infractorop->estatus         = 'Aprobado';
        // }else{
        //     $infractorop->estatus         = 'Pendiente';
        // }
        // $infractorop->infoextra           = trim($this->request->comentario ?? '');
        // $infractorop->usuarioId           = Auth::id();
        // $infractorop->save();


        return response()->json($inspectorId);



    }


     
}
