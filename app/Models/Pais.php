<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;

    protected $table = 'rid_paises';

    protected $fillable = [
        'id',
        'pais',
        'cod_pais',
        'estatus',
        'nacionalidad',
    ];
}
