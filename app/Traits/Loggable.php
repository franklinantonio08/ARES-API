<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

trait Loggable
{
    public static function bootLoggable()
    {
        static::created(function ($model) {
            self::guardarLog('INSERT', $model);
        });

        static::updated(function ($model) {
            self::guardarLog('UPDATE', $model);
        });

        static::deleted(function ($model) {
            self::guardarLog('DELETE', $model);
        });
    }

    protected static function guardarLog($accion, $modelo)
    {
        DB::table('logs')->insert([
            'usuarioId'   => Auth::check() ? Auth::id() : null,
            'action'      => $accion,
            'descripcion' => "Acción: $accion en {$modelo->getTable()} con ID {$modelo->id}",
            'nombreTabla' => $modelo->getTable(),
            'recordId'    => $modelo->id ?? null,
            'ipAddress'   => Request::ip(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public static function registrarManual($action, $descripcion, $nombreTabla, $recordId = null)
    {
        DB::table('logs')->insert([
            'usuarioId'   => Auth::check() ? Auth::id() : null,
            'action'      => $action,
            'descripcion' => $descripcion,
            'nombreTabla' => $nombreTabla,
            'recordId'    => $recordId,
            'ipAddress'   => Request::ip(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
