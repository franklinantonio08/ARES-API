<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudZLC extends Model
{
    use HasFactory;

    protected $table = 'visa_zl_solicitud';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'nombre_usual',
        'estado_civil',
        'nombre_conyuge',
        'nacionalidad_conyuge',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'nacionalidad',
        'numero_identidad_personal',
        'nombre_padre',
        'nacionalidad_padre',
        'nombre_madre',
        'nacionalidad_madre',
        'ruex',
        'numero_pasaporte',
        'pais_emisor_pasaporte',
        'fecha_expedicion_pasaporte',
        'fecha_vencimiento_pasaporte',
        'pais_residencia',
        'estado_provincia_departamento',
        'ciudad',
        'correo_electronico',
        'ocupacion_profesion_actual',
        'lugar_trabajo',
        'direccion_hospedaje_panama',
        'telefono_contacto_panama',
        'fecha_viaje_panama',
        'tiempo_estadia_panama',
        'motivo_viaje',
        'empresa_usuario_zlc_nombre',
        'empresa_usuario_zlc_clave'
    ];
}
