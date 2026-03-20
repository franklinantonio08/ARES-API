<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Vehiculos;
use App\Models\Accesorio;
use App\Models\VehiculoAccesorio;
use App\Models\Inspeccionvehiculo;
use App\Models\Inspeccionaccesorio;
use App\Models\Inspeccionarchivo;

use App\Helpers\CommonHelper;
use App\Traits\Loggable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DB;
use Excel;
use Carbon\Carbon;


class VehiculoController extends Controller {

    private CommonHelper $common;

    public function __construct(CommonHelper $common){

        $this->common = $common;

    }

    public function ListaVehiculos(Request $request){

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json(['success' => false,'message' => 'No autenticado.','code' => 'UNAUTHENTICATED'], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json(['success' => false,'message' => $this->common->message ?? 'Acceso no autorizado.','code' => 'PERMISO_DENEGADO'], 403);
        }

        $vehiculos = Vehiculos::orderBy('placa', 'asc')
            ->get();

        return response()->json($vehiculos);
    
    }

    public function BuscarVehiculos(Request $request){

        $userId = $request->user()->id ?? Auth::id();

        if (!$userId) {
            return response()->json(['success' => false,'message' => 'No autenticado.','code' => 'UNAUTHENTICATED'], 401);
        }

        if (!$this->common->usuariopermiso('050', $userId)) {
            return response()->json(['success' => false,'message' => $this->common->message ?? 'Acceso no autorizado.','code' => 'PERMISO_DENEGADO'], 403);
        }

        $placa = $request->input('placa');

        $vehiculo = Vehiculos::where('placa', $placa)
        ->select()
            ->first();

        if (!$vehiculo) {
            return response()->json(['mensaje' => 'Vehículo no encontrado'], 404);
        }

        $ultimaInspeccion = Inspeccionvehiculo::where('id_vehiculo', $vehiculo->id)
            ->orderBy('created_at', 'desc')
            ->first();


        $accesorios = [];

        if ($ultimaInspeccion) {
            $accesorios = Inspeccionaccesorio::where('inspeccion_id', $ultimaInspeccion->id)
                ->join('accesorios', 'inspeccion_accesorios.accesorio_id', '=', 'accesorios.id')
                ->select(
                    'accesorios.id',
                    'accesorios.nombre_accesorio',
                    'inspeccion_accesorios.estatus',
                )
                ->get();
        }

        $todosLosAccesorios = Accesorio::where('estatus', 'Activo')
            ->select('id', 'nombre_accesorio')
            ->get();

        return response()->json([
            'vehiculo'          => $vehiculo,
            'ultima_inspeccion' => $ultimaInspeccion,
            'accesorios_inspeccion' => $accesorios,       
            'accesorios_catalogo'   => $todosLosAccesorios
        ]);

    }

    /*public function ListaAccesorios() {

        return Accesorio::select('id', 'nombre_accesorio')
        ->where('estatus', 'Activo')
        ->get();

    }*/

    
    public function GuardaInspeccion(Request $request){

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
        // VALIDACIÓN
        // ==========================================================
        $request->validate([
            'id_vehiculo'               => 'required|integer|exists:vehiculos,id',
            'kilometraje'               => 'required|numeric|min:0',
            'combustible'               => 'required|in:Vacío,1/4,1/2,3/4,Lleno',
            'observaciones'             => 'nullable|string|max:1000',
            'conductor_cedula'          => 'required|string|max:20',
            'conductor_id'              => 'nullable|integer',
            'accesorios'                => 'nullable|array',
            'accesorios.*.accesorio_id' => 'required_with:accesorios|integer|exists:accesorios,id',
            'accesorios.*.estatus'      => 'required_with:accesorios|in:Activo,Inactivo',
            // multipart
            'imagen_20puntos'           => 'nullable|file|mimes:jpg,jpeg,png|max:8192',
            'evidencias'                => 'nullable|array|max:10',
            'evidencias.*'              => 'file|mimes:jpg,jpeg,png|max:8192',
            // base64
            'imagen_20puntos_b64'       => 'nullable|string',
            'evidencias_b64'            => 'nullable|array|max:10',
            'evidencias_b64.*'          => 'string',
        ]);

        try {

            $result = DB::transaction(function () use ($request, $userId) {

                // ==========================================================
                // 1. CREAR INSPECCIÓN
                // ==========================================================
                $inspeccion = Inspeccionvehiculo::create([
                    'id_vehiculo'      => $request->input('id_vehiculo'),
                    'kilometraje'      => $request->input('kilometraje'),
                    'combustible'      => $request->input('combustible'),
                    'observaciones'    => $request->input('observaciones'),
                    'conductor_cedula' => $request->input('conductor_cedula'),
                    'conductor_id'     => $request->input('conductor_id'),
                    'funcionario_id'   => $userId,
                    'sucursal_id'      => $this->common->sucursalId ?? null,
                ]);

                // ==========================================================
                // 2. ACCESORIOS (insert masivo)
                // ==========================================================
                $accesorios = $request->input('accesorios');

                if (!empty($accesorios) && is_array($accesorios)) {
                    $accesoriosData = collect($accesorios)->map(fn($acc) => [
                        'inspeccion_id' => $inspeccion->id,
                        'accesorio_id'  => $acc['accesorio_id'],
                        'estatus'       => $acc['estatus'],
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ])->toArray();

                    Inspeccionaccesorio::insert($accesoriosData);
                }

                // ==========================================================
                // 3. ARCHIVOS
                // ==========================================================

                // ── Helper: decodifica y valida un string base64 ──────────
                $decodeBase64 = function (string $base64): array {
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

                    return ['data' => $data, 'ext' => $ext];
                };

                // ── Helper: genera nombre único ───────────────────────────
                $nombreArchivo = fn(string $ext) =>
                    'insp_' . $inspeccion->id . '_' . \Str::random(20) . '.' . $ext;

                // ── Helper: inserta registro en inspecciones_archivos ─────
                $registrarArchivo = function (string $path, string $tipo) use ($inspeccion, $userId) {
                    DB::table('inspecciones_archivos')->insert([
                        'inspeccion_id' => $inspeccion->id,
                        'tipo'          => $tipo,
                        'archivo'       => $path,
                        'descripcion'   => 'Imagen Adjunta',
                        'estatus'       => 'Activo',
                        'usuarioId'     => $userId,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                };

                // ── 3a. Imagen 20 puntos — BASE64 ─────────────────────────
                $img20b64 = $request->input('imagen_20puntos_b64');

                if (!empty($img20b64)) {
                    ['data' => $data, 'ext' => $ext] = $decodeBase64($img20b64);
                    $path = 'inspecciones/' . $nombreArchivo($ext);
                    Storage::disk('public')->put($path, $data);
                    $registrarArchivo($path, '20puntos');
                }

                // ── 3b. Imagen 20 puntos — MULTIPART ─────────────────────
                if ($request->hasFile('imagen_20puntos')) {
                    $file = $request->file('imagen_20puntos');
                    $ext  = strtolower($file->getClientOriginalExtension());
                    if ($ext === 'jpeg') $ext = 'jpg';
                    $path = $file->storeAs('inspecciones', $nombreArchivo($ext), 'public');
                    $registrarArchivo($path, '20puntos');
                }

                // ── 3c. Evidencias — BASE64 ───────────────────────────────
                $evidenciasB64 = $request->input('evidencias_b64');

                if (!empty($evidenciasB64) && is_array($evidenciasB64)) {
                    foreach ($evidenciasB64 as $b64) {
                        ['data' => $data, 'ext' => $ext] = $decodeBase64($b64);
                        $path = 'inspecciones/' . $nombreArchivo($ext);
                        Storage::disk('public')->put($path, $data);
                        $registrarArchivo($path, 'evidencia');
                    }
                }

                // ── 3d. Evidencias — MULTIPART ────────────────────────────
                if ($request->hasFile('evidencias')) {
                    foreach ($request->file('evidencias') as $file) {

                        if (!$file->isValid()) {
                            throw new \RuntimeException('Archivo adjunto inválido.');
                        }

                        $ext   = strtolower($file->getClientOriginalExtension());
                        $valid = ['png', 'jpg', 'jpeg'];

                        if (!in_array($ext, $valid)) {
                            throw new \RuntimeException('Formato de imagen no permitido (solo png, jpg, jpeg).');
                        }
                        if ($file->getSize() > 8 * 1024 * 1024) {
                            throw new \RuntimeException('Imagen supera el tamaño permitido (8MB).');
                        }

                        if ($ext === 'jpeg') $ext = 'jpg';

                        $path = $file->storeAs('inspecciones', $nombreArchivo($ext), 'public');
                        $registrarArchivo($path, 'evidencia');
                    }
                }

                return $inspeccion->id;
            });

            return response()->json([
                'success'       => true,
                'message'       => 'Inspección guardada correctamente.',
                'inspeccion_id' => $result,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            \Log::error('Error GuardaInspeccion', [
                'user_id' => $userId,
                'ex'      => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error guardando la inspección.',
                'detail'  => $e->getMessage(),
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
        // 3. Sucursal
        // =========================
        $this->common->ensureSucursalOrFail();

        // =========================
        // 4. Fecha HOY
        // =========================
        $hoy = Carbon::today();

        // =========================
        // 5. QUERY
        // =========================
        $inspecciones = Inspeccionvehiculo::query()
            ->where('funcionario_id', $userId)
            ->whereDate('created_at', $hoy)
            ->with([
                'vehiculo:id,placa,marca,modelo,anio,color',
            ])
            ->orderByDesc('created_at')
            ->get();

        // =========================
        // 6. MAPEO
        // =========================
        $listado = $inspecciones->map(function ($insp) {
            return [
                'id'            => $insp->id,
                'placa'         => $insp->vehiculo->placa  ?? '',
                'marca'         => $insp->vehiculo->marca  ?? '',
                'modelo'        => $insp->vehiculo->modelo ?? '',
                'anio'          => $insp->vehiculo->anio   ?? '',
                'color'         => $insp->vehiculo->color  ?? '',
                'kilometraje'   => $insp->kilometraje,
                'combustible'   => $insp->combustible,
                'conductor_cedula' => $insp->conductor_cedula,
                'observaciones' => $insp->observaciones,
                'ts'            => optional($insp->created_at)->toDateTimeString(),
            ];
        });

        // =========================
        // 7. RESPUESTA
        // =========================
        return response()->json([
            'total'        => $inspecciones->count(),
            'inspecciones' => $listado,
        ]);  
    
    }



}
