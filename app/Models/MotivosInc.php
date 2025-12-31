<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivosInc extends Model
{
    use HasFactory;

    protected $table = 'incidencias_motivo';

    protected $fillable = [
        'id',
        'descripcion',
        'accionId',
        'estatus',
        'usuarioId',
    ];
}
