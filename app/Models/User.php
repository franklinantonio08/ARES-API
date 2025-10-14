<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'lastName',
        'departamentoId',
        'sucursalId',
        'rolId',
        'codigo',
        'estatus',
        'infoExtra',
        'usuarioId',
        'tipo_usuario',
        'email',
        'password',   // seguirá NULL para usuarios LDAP
        'guid',
        'domain',
        'remember_token',
        'cedula',
        'posicion',
        'cargo',
        'fecha_nacimiento',
        'actualizacion_perfil',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'fecha_nacimiento'     => 'date',
        'actualizacion_perfil' => 'boolean',
    ];
}
