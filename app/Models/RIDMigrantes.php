<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RIDMigrantes extends Model
{
    use HasFactory;

    protected $table = 'RID_migrante';


    protected $fillable = [
        'nombre',
        'apellido',
        'fechaNacimiento',
        'codigo',
        'documento',
        'regionId',
        'paisId',
        'nacionalidadId',
        'genero',
        'tipo',
        'puestoId',
        'afinidadId',
        'infoextra',
        'estatus',
        'usuarioId',

    ];
    

}
