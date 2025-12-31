<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccionesInc extends Model
{
    use HasFactory;

    protected $table = 'incidencias_acciones';

    protected $fillable = [
        'id',
        'descripcion',
        'estatus',
        'usuarioId',
    ];
}
