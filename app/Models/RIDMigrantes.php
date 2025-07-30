<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RIDMigrantes extends Model
{
    use HasFactory;

    protected $table = 'rid_migrante';


    protected $fillable = [
        'primerNombre',
        'segundoNombre',
        'primerApellido',
        'segundoApellido',
        'fechaNacimiento',
        'codigo',
        'documento',
        'regionId',
        'paisId',
        'nacionalidadId',
        'genero',
        'tipo',
        'tipoubicacionId',
        'ubicacionId',
        'afinidadId',
        'familiaId',
        'infoextra',
        'estatus',
        'usuarioId',

    ];
    

}
