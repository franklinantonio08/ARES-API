<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB; 

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

}
