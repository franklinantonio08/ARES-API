<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Corregimiento extends Model
{
    use HasFactory;

    protected $table = 'corregimiento';

    protected $fillable = [
        'id',
        'nombre',
        'codigo',
        'estatus',
        'distritoId',
    ];
}
