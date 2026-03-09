<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudZLCArchivos extends Model
{
    use HasFactory;

    protected $table = 'visa_zl_solicitud_archivos';

    protected $fillable = [
        'visa_zl_solicitud_id',
        'tipo',
        'archivo',
        'descripcion',
        'usuarioId',
        'estatus'
    ];
}
