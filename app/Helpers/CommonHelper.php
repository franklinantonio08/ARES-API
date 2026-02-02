<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB; 
use App\Models\ConsultaValidacion;
use Illuminate\Http\Request;

class CommonHelper
{
    public $message = 'No tiene permiso para acceder a esta sección.';

public function usuariopermiso(string $codigoPermiso, ?int $userId = null): bool
    {
        $uid = $userId ?? Auth::id();
        if (!$uid) return false;

        $perm = DB::table('usuario_permiso')
            ->where('usuarioId', $uid)
            ->where('codigo', $codigoPermiso)
            ->value('valor');

        return $perm === 'TRUE';
    }

    public function asignarPermisosUsuario($usuarioId, $rolId) {

        DB::beginTransaction();
        try {
            // Eliminar permisos existentes
            DB::table('usuario_permiso')->where('usuarioId', $usuarioId)->delete();

            // Obtener los permisos del rol
            $rolePermissions = DB::table('rol_permiso')
                ->where('rolId', $rolId)
                ->get();

            if ($rolePermissions->isEmpty()) {
                DB::rollBack();
                return false;
            }

            // Insertar nuevos permisos
            foreach ($rolePermissions as $permiso) {
                DB::table('usuario_permiso')->insert([
                    'codigo' => str_pad($permiso->codigopermisoId, 3, "0", STR_PAD_LEFT),
                    'valor' => $permiso->valor,
                    'rolId' => $rolId,
                    'codigopermisoId' => $permiso->codigopermisoId,
                    'usuarioId' => $usuarioId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                if ((int)$permiso->codigopermisoId === 48) {
                    $yaExiste = DB::table('agentes')
                        ->where('funcionarioId', $usuarioId)
                        ->exists();

                    if (!$yaExiste) {
                        DB::table('agentes')->insert([
                            'funcionarioId'     => $usuarioId, // ajusta si corresponde
                            'descripcion'       => 'Agente asignado automáticamente',
                            'cantatenciones'    => 0,
                            'cantatencionesext' => 0,
                            'infoextra'         => null,
                            'estatus'           => 'Activo',
                            'usuarioId'         => Auth::user()->id,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function FechaCorreccion($anio, $mes, $dia){
        
        $anio = (int) trim((string) $anio);
        $mes = (int) trim((string) $mes);
        $dia = (int) trim((string) $dia);

        // incompleta o vacía -> null
        if ($anio === 0 || $mes === 0 || $dia === 0) {
            return null;
        }

        // inválida -> null
        if (!checkdate($mes, $dia, $anio)) {
            return null;
        }

        // válida -> YYYY-MM-DD
        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }

    public function getSucursalIdUsuario(): ?int {

        if (!Auth::check()) return null;

        if ($sid = Session::get('current_sucursal_id')) {
            return (int) $sid;
        }

        $u = Auth::user();
        if (!empty($u->current_sucursal_id)) {
            return (int) $u->current_sucursal_id;
        }
        if (!empty($u->sucursalId)) {
            return (int) $u->sucursalId;
        }
        return null;

    }

    public function ensureSucursalOrFail(): void {

        if ($this->getSucursalIdUsuario() === null) {
            abort(422, 'El usuario no tiene una sucursal activa/asignada.');
        }

    }

    public function withSucursal(array $data, string $field = 'sucursalId'): array {
        $sid = $this->getSucursalIdUsuario();
        if ($sid !== null) {
            $data[$field] = $sid;
        }
        return $data;
    }

        /**
     * Registrar consultas de validación (RUEX / PASAPORTE / CEDULA)
     */
    public function registrarConsultaValidacion(
        Request $request,
        string $tipoConsulta,
        array $busqueda,
        array $resultado = [],
        int $totalResultados = 0
    ): void {

        $userId = $request->user()->id ?? Auth::id();
        if (!$userId) {
            return; // seguridad: no registrar si no hay usuario
        }

        $normalize = fn($v) => is_string($v) ? trim(mb_strtoupper($v)) : $v;

        ConsultaValidacion::create([
            'usuario_id'       => $userId,
            'tipo_consulta'    => strtoupper($tipoConsulta),

            // Datos buscados (repetibles)
            'num_filiacion' => $normalize($busqueda['num_filiacion'] ?? null),
            'pasaporte'     => $normalize($busqueda['pasaporte'] ?? null),
            'cedula'        => $normalize($busqueda['cedula'] ?? null),


            'primerNombre'     => $busqueda['primerNombre'] ?? null,
            'primerApellido'   => $busqueda['primerApellido'] ?? null,
            'fecha_nacimiento' => $busqueda['fecha_nacimiento'] ?? null,
            'nacionalidad'     => $busqueda['nacionalidad'] ?? null,

            // Resultado
            'encontrado'       => $totalResultados > 0 ? 1 : 0,
            'total_resultados' => $totalResultados,
            'datos_validados'  => !empty($resultado) ? $resultado : null,

            // Contexto
            'ip'               => $request->ip(),
            'user_agent'       => substr((string) $request->userAgent(), 0, 255),
        ]);
    }


}
