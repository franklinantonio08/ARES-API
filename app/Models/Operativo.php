<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operativo extends Model
{
    use HasFactory;
    
    protected $table = 'operativo';

    protected $fillable = [
        'id',
        'descripcion',
        'unidaSolicitanteId',
        'estatus',
        'usuarioId',
    ];
}
