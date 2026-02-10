<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Infractorincidenciaarchivo extends Model
{
    use HasFactory;

    protected $table = 'infractores_incidencias_archivos';

     protected $fillable = [
        'infractores_incidencias_id',
        'tipo',
        'archivo',
        'descripcion',
        'usuarioId',
        'estatus',
    ];
}
