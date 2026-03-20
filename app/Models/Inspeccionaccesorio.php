<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspeccionaccesorio extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_accesorios';

    protected $fillable = [
        'id',
        'inspeccion_id',
        'accesorio_id',
        'estatus'
    ];
}
