<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Infractor extends Model
{
    use HasFactory, Loggable;

    //protected $connection = 'atlas'; 

    protected $table = 'infractor';

    protected $fillable = [
            'primerNombre',
            'segundoNombre',
            'primerApellido',
            'segundoApellido',
            'fechaNacimiento',
            'documento',
            'regionId',
            'paisId',
            'nacionalidadId',
            'genero',
            'estatus',
            'usuarioId',
            'verificadorId',
        ];

        /** =========================
         *  RELACIONES REALES (DDL)
         *  ========================= */

        // País de nacimiento
        public function pais()
        {
            return $this->belongsTo(Pais::class, 'paisId');
        }

        // Nacionalidad
        public function nacionalidad()
        {
            return $this->belongsTo(Pais::class, 'nacionalidadId');
        }
}
