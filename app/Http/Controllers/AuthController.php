<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Models\ActiveDirectory\User as LdapRecordUser;
use LdapRecord\Container;
use App\Models\LdapUser;
use App\Helpers\CommonHelper;
use App\Models\Logs;

class AuthController extends Controller {

    private $common;

    public function __construct(CommonHelper $common)
    {
        $this->common = $common;
    }

    public function register(Request $request){

        $fields = $request->validate([
            'name' => 'required|string',
            'codigo' => 'required|integer',
            'username' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'            
        ]);

        $user = User::create([

            'name' => $fields['name'],
            'codigo' => $fields['codigo'],
            'username' => $fields['username'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
            
        ]);

        $this->saveLog(
            $user->id,
            'LOGIN_SUCCESS',
            "Usuario {$user->username} inició sesión correctamente vía LDAP",
            $request
        );

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            //'codigo' => $codigo,
            'token' => $token
        ];

        return response($response, 201);

    }

    public function login(Request $request){

        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $fields['username'];
        $password = $fields['password'];

        // 🔹 1) Buscar si el usuario ya existe en tu tabla
        $localUser = User::where('username', $username)->first();

        /**
         * =========================================================
         * 🔐 CASO 1 — USUARIO LOCAL (tipo_usuario = 2)
         * =========================================================
         */
        if ($localUser && $localUser->tipo_usuario == 2) {

            if (!\Hash::check($password, $localUser->password)) {

                $this->saveLog(
                    $localUser->id,
                    'LOGIN_FAIL_LOCAL',
                    "Intento fallido de login LOCAL para {$username}",
                    $request
                );

                return response(['message' => 'Credenciales inválidas (LOCAL)'], 401);
            }

            // Verificar permiso antes del token
            if (!$this->common->usuariopermiso('099', $localUser->id)) {

                $this->saveLog(
                    $localUser->id,
                    'LOGIN_DENIED_PERMISO',
                    "Usuario LOCAL {$username} no tiene permiso 099",
                    $request
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Acceso no autorizado.',
                    'code'    => 'PERMISO_DENEGADO'
                ], 403);
            }

            $token = $localUser->createToken('myapptoken')->plainTextToken;

            return response([
                'user'  => $localUser,
                'token' => $token,
            ], 200);
        }

        /**
         * =========================================================
         * 🔐 CASO 2 — USUARIO AD (tipo_usuario = 1 o no existe aún)
         * =========================================================
         */

        $upn  = $username . '@migracion.gob.pa';
        $conn = Container::getConnection('default');

        if (!$conn->auth()->attempt($upn, $password)) {

            $this->saveLog(
                null,
                'LOGIN_FAIL_LDAP',
                "Intento fallido de login LDAP para {$username}",
                $request
            );

            return response(['message' => 'Credenciales inválidas (LDAP)'], 401);
        }

        // Buscar en AD
        $ldapUser = LdapUser::where('samaccountname', $username)->first();

        if (!$ldapUser) {

            $this->saveLog(
                null,
                'LOGIN_FAIL_AD_NOT_FOUND',
                "Usuario {$username} autenticó LDAP pero no existe en AD",
                $request
            );

            return response(['message' => 'Usuario de Active Directory no encontrado.'], 404);
        }

        // Atributos AD
        $guid = $ldapUser->getConvertedGuid();
        $sam  = $ldapUser->getFirstAttribute('samaccountname');
        $cn   = $ldapUser->getFirstAttribute('cn');
        $sn   = $ldapUser->getFirstAttribute('sn');
        $dn   = strtolower($ldapUser->getDn() ?? '');
        preg_match_all('/dc=([^,]+)/', $dn, $m);
        $domain = $m && isset($m[1]) ? implode('.', $m[1]) : 'migracion.gob.pa';

        // Sincronizar usuario
        $user = null;
        if ($guid)  $user = User::where('guid', $guid)->first();
        if (!$user) $user = User::where('username', $sam ?? $username)->first();
        if (!$user) $user = new User();

        $syntheticEmail = ($sam ?? $username) . '@' . $domain;

        $user->name         = $cn ?: ($sam ?? $username);
        $user->lastName     = $sn ?: ($user->lastName ?? null);
        $user->username     = $sam ?: $username;
        $user->email        = $user->email ?: $syntheticEmail;
        $user->guid         = $guid ?: ($user->guid ?? null);
        $user->domain       = $domain;
        $user->estatus      = $user->estatus ?: 'Activo';
        $user->tipo_usuario = 1; // 🔥 SIEMPRE AD
        if (empty($user->rolId)) $user->rolId = 11;

        $user->save();

        if (!$this->common->usuariopermiso('050', $user->id)) {

            $this->saveLog(
                $user->id,
                'LOGIN_DENIED_PERMISO',
                "Usuario AD {$username} no tiene permiso 050",
                $request
            );

            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        return response([
            'user'  => $user,
            'token' => $token,
        ], 200);

    }



    public function logout (Request $request){

        //auth()->user()->tokens()->delete();

        //return ['message' => 'Logged out'];

        $user = auth()->user();

        $this->saveLog(
            $user->id,
            'LOGOUT',
            "Usuario {$user->username} cerró sesión",
            $request
        );

        $user->tokens()->delete();

        return ['message' => 'Logged out'];

    }

    private function saveLog($usuarioId, $action, $descripcion, $request) {

         Logs::create([
        'usuarioId'   => $usuarioId,
        'action'      => $action,
        'descripcion' => $descripcion,
        'nombreTabla' => 'users',
        'recordId'    => $usuarioId,
        'ipAddress'   => $request->ip(),
        'created_at'  => now()
        ]);
    }
}
