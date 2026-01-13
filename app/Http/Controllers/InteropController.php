<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\RuexInfo;
use App\Models\CarnetInfo;


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

    public function BuscarRuex(Request $request){

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

        return response()->json([
            'ruex' => $ruexInfoGeneral,
            'impedimientos' => $impedimientos,
            'carnet' => $carnetInfoGeneral,
            'foto' => $fotoPerfilUrl,
        ]);

    }
}
