<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspeccionarchivo extends Model
{
    use HasFactory;
    
    protected $table = 'inspecciones_archivos';

    protected $fillable = [
        'id',
        'inspeccion_id',
        'tipo',
        'archivo',
        'descripcion',
        'estatus',
        'usuarioId'
    ];
}
