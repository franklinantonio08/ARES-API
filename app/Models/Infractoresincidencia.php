<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasSucursal;

class Infractoresincidencia extends Model
{
    use HasFactory, HasSucursal;

     protected $table = 'infractores_incidencias';

    protected $fillable = [
        'infractorId',
        // 'operativoId',
        'sucursalId',
        // 'unidadSolicitanteId',
        'incidencia_motivo_Id',
        'incidencia_accion_Id',
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

    // public function sucursal() { 
    //     return $this->belongsTo(Sucursal::class, 'sucursalId'); 
    // }

    // public function infractor() { 
    //     return $this->belongsTo(Infractor::class, 'infractorId');
    // }

    
    public function infractor()
    {
        return $this->belongsTo(Infractor::class, 'infractorId');
    }

    // public function operativo()
    // {
    //     return $this->belongsTo(Operativo::class, 'operativoId');
    // }

    public function motivo()
    {
        return $this->belongsTo(MotivosInc::class, 'incidencia_motivo_Id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provinciaId');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    public function verificador()
    {
        return $this->belongsTo(User::class, 'verificadorId');
    }
}
