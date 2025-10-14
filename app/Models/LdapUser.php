<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LdapRecord\Models\ActiveDirectory\User as BaseLdapUser;
use App\Models\User as EloquentUser;

class LdapUser extends BaseLdapUser{    

    public function createEloquentModel() {
        return new EloquentUser(); 
    }

    public function syncWithEloquent($user){
    
        // Mapeo correcto de campos según tu estructura de tabla
        $user->name = $this->getFirstAttribute('givenname');
        $user->username = $this->getFirstAttribute('samaccountname');
        $user->lastName = $this->getFirstAttribute('sn'); 
        $user->email = $this->getFirstAttribute('mail');
        $user->guid = $this->getConvertedGuid();
        
        // Campos adicionales específicos de tu aplicación
        $user->departamentoId = $this->getDepartmentId();
        $user->tipo_usuario = 'UsuarioSNM';
        $user->estatus = 'Activo';
        
        $user->save();
    }

     public static function findByUsername($username){
        return static::where('samaccountname', '=', $username)->first();
    }

    public static function findByEmail($email){
        return static::where('mail', '=', $email)->first();
    }

}
