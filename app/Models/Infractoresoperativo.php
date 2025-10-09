<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasSucursal;

class Infractoresoperativo extends Model
{
    use HasFactory, HasSucursal;

    protected $connection = 'atlas'; 

    protected $table = 'infractores_operativos';

    protected $fillable = [
        'infractorId',
        'operativoId',
        'sucursalId',
        'unidadSolicitanteId',
        'motivoId',
        'estatusId',
        'provinciaId',
        'distritoId',
        'corregimientoId',
        'direccion',
        'fechacitacion',
        'estatus',
        'infoextra',
        'usuarioId',
        'usuarioactualizaId',
        'verificadorId'
    ];

    public function sucursal() { 
        return $this->belongsTo(Sucursal::class, 'sucursalId'); 
    }

    public function infractor() { 
        return $this->belongsTo(Infractor::class, 'infractorId');
    }
}
