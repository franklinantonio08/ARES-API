<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Infractorarchivo extends Model
{
    use HasFactory;

    protected $connection = 'atlas'; 
    
    protected $table = 'infractores_operativos_archivos';

     protected $fillable = [
        'infractores_operativos_id',
        'tipo',
        'archivo',
        'descripcion',
        'usuarioId',
        'estatus',
    ];

}
