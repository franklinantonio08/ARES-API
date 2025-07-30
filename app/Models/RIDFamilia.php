<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RIDFamilia extends Model
{
    use HasFactory;

    protected $table = 'rid_familia';

    protected $fillable = [
        'id',
        'codigo',
        'infoextra',
        'estatus',
        'usuarioId',

    ];
}
