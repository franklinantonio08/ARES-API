<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultaValidacion extends Model
{
    use HasFactory;


     protected $table = 'consultas_validacion';

    protected $fillable = [
        'usuario_id',
        'tipo_consulta',

        'num_filiacion',
        'pasaporte',
        'cedula',

        'primerNombre',
        'primerApellido',
        'fecha_nacimiento',
        'nacionalidad',

        'encontrado',
        'total_resultados',
        'datos_validados',

        'ip',
        'user_agent'
    ];

    protected $casts = [
        'datos_validados' => 'array',
        'encontrado'      => 'boolean',
        'fecha_nacimiento'=> 'date',
    ];

    public $timestamps = true;
}
