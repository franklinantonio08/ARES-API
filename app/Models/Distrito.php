<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    use HasFactory;

    protected $table = 'distrito';

    protected $fillable = [
        'id',
        'nombre',
        'codigo',
        'estatus',
        'provinciaId',
    ];
}
