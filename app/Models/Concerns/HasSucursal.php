<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait HasSucursal {

    protected static function bootHasSucursal() {

        static::creating(function ($model) {
            if (empty($model->sucursalId) && Auth::check()) {
                $u = Auth::user();
                $model->sucursalId = $u->current_sucursal_id ?? $u->sucursalId;
            }
        });
        
    }

}
