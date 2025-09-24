<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acciones extends Model
{
    use HasFactory;

    protected $table = 'acciones_operativo';

    protected $fillable = [
        'id',
        'descripcion',
        'estatus',
        'usuarioId',
    ];
}
