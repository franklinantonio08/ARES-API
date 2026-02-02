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

    public function login(Request $request) {
        // 1) Validación: SOLO username + password
        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $fields['username'];
        $password = $fields['password'];

        // 2) Bind contra AD usando UPN
        $upn  = $username . '@migracion.gob.pa';
        $conn = Container::getConnection('default');

        // if (!$conn->auth()->attempt($upn, $password)) {
        //     return response(['message' => 'Credenciales inválidas (LDAP)'], 401);
        // }

        if (!$conn->auth()->attempt($upn, $password)) {

            $this->saveLog(
                null,
                'LOGIN_FAIL_LDAP',
                "Intento fallido de login LDAP para el usuario {$username}",
                $request
            );

            return response(['message' => 'Credenciales inválidas (LDAP)'], 401);
        }

        // 3) Buscar el usuario en AD por sAMAccountName
        $ldapUser = LdapUser::where('samaccountname', $username)->first();

        // if (!$ldapUser) {
        //     return response(['message' => 'Usuario de Active Directory no encontrado.'], 404);
        // }

        if (!$ldapUser) {

            $this->saveLog(
                null,
                'LOGIN_FAIL_AD_NOT_FOUND',
                "Usuario {$username} autenticó LDAP pero no existe en consulta AD",
                $request
            );

            return response(['message' => 'Usuario de Active Directory no encontrado.'], 404);
        }

        // 4) Atributos de AD
        $guid = $ldapUser->getConvertedGuid();
        $sam  = $ldapUser->getFirstAttribute('samaccountname');
        $cn   = $ldapUser->getFirstAttribute('cn');
        $sn   = $ldapUser->getFirstAttribute('sn');
        $dn   = strtolower($ldapUser->getDn() ?? '');
        preg_match_all('/dc=([^,]+)/', $dn, $m);
        $domain = $m && isset($m[1]) ? implode('.', $m[1]) : 'migracion.gob.pa';

        // 5) Resolver / sincronizar en `users` (preferencia: guid -> username)
        $user = null;
        if ($guid)  $user = User::where('guid', $guid)->first();
        if (!$user) $user = User::where('username', $sam ?? $username)->first();
        if (!$user) $user = new User();

        // Si tu tabla exige email NOT NULL/UNIQUE, generamos uno sintético estable
        $syntheticEmail = ($sam ?? $username) . '@' . $domain;

        $user->name         = $cn ?: ($sam ?? $username);
        $user->lastName     = $sn ?: ($user->lastName ?? null);
        $user->username     = $sam ?: $username;
        $user->email        = $user->email ?: $syntheticEmail; // o quítalo si tu esquema lo permite nulo
        $user->guid         = $guid ?: ($user->guid ?? null);
        $user->domain       = $domain;
        $user->estatus      = $user->estatus ?: 'Activo';
        $user->tipo_usuario = $user->tipo_usuario ?: '1';
        if (empty($user->rolId)) $user->rolId = 11;

        $user->save();

        // 6) Verificar permiso 031 ANTES de crear token (sin depender de Auth::user)
        // if (!$this->common->usuariopermiso('050', $user->id)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => $this->common->message ?? 'Acceso no autorizado.',
        //         'code'    => 'PERMISO_DENEGADO'
        //     ], 403);
        // }

        if (!$this->common->usuariopermiso('050', $user->id)) {

            $this->saveLog(
                $user->id,
                'LOGIN_DENIED_PERMISO',
                "Usuario {$user->username} autenticó pero no tiene permiso 050",
                $request
            );

            return response()->json([
                'success' => false,
                'message' => $this->common->message ?? 'Acceso no autorizado.',
                'code'    => 'PERMISO_DENEGADO'
            ], 403);
        }

        // 7) Crear token Sanctum
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
