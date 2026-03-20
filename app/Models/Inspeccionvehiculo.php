<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inspeccionvehiculo extends Model
{
    use HasFactory;
    
    protected $table = 'inspecciones_vehiculos';

    protected $fillable = [
        'id',
        'id_vehiculo',
        'kilometraje',
        'combustible',
        'conductor_cedula',
        'funcionario_id',
        'sucursal_id'
    ];


    public function vehiculo()
    {
        return $this->belongsTo(Vehiculos::class, 'id_vehiculo');
    }

}
