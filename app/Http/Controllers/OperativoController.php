<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\RuexInfo;
use App\Models\CarnetInfo;
use App\Helpers\FotoHelper;

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
                'tipo_visa'            => $carnet->tipo_visa,
                'numero_tramite'       => $carnet->numero_tramite,
                'cotizacion'           => $carnet->cotizacion,
                'fecha_expedicion'     => $fmtFecha($carnet->fecha_expedicion),
                'fecha_expiracion'     => $fmtFecha($carnet->fecha_expiracion),
                'fecha_llegada_panama' => $fmtFecha($carnet->fecha_llegada_panama),
                'fecha_resolucion'     => $fmtFecha($carnet->fecha_resolucion),
                'fecha_notificacion'   => $fmtFecha($carnet->fecha_notificacion),
                'impreso'              => is_null($carnet->impreso) ? null : (bool)$carnet->impreso,
                'ruta_imagen'          => $carnet->ruta_Imagen,
            ];

        }

        $fotoPerfilUrl = null;
        if ($carnet) {
            $fotoPerfilUrl = FotoHelper::resolverFoto(
                $carnet->foto_url ?? null,
                $carnet->ruta_Imagen ?? null
            );
        }

        

        return response()->json([
            'ruex' => $ruexInfoGeneral,
            'carnet' => $carnetInfoGeneral,
            'foto' => $fotoPerfilUrl,
        ]);
    }
}
