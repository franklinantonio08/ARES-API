<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motivos extends Model
{
    use HasFactory;

    protected $table = 'motivo_operativo';

    protected $fillable = [
        'id',
        'descripcion',
        'accionId',
        'estatus',
        'usuarioId',
    ];
}
