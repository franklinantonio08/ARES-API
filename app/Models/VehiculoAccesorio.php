<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiculoAccesorio extends Model
{
    use HasFactory;

    protected $table = 'vehiculo_accesorio';

    protected $fillable = [
        'id',
        'vehiculo_id',
        'accesorio_id',
    ];
}
