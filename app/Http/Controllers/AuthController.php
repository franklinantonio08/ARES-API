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

class AuthController extends Controller
{
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

        if (!$conn->auth()->attempt($upn, $password)) {
            return response(['message' => 'Credenciales inválidas (LDAP)'], 401);
        }

        // 3) Buscar el usuario en AD por sAMAccountName
        $ldapUser = LdapUser::where('samaccountname', $username)->first();
        if (!$ldapUser) {
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
        $user->tipo_usuario = $user->tipo_usuario ?: 'UsuarioSNM';
        if (empty($user->rolId)) $user->rolId = 11;

        $user->save();

        // 6) Verificar permiso 031 ANTES de crear token (sin depender de Auth::user)
        if (!$this->common->usuariopermiso('050', $user->id)) {
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

        auth()->user()->tokens()->delete();

        return ['message' => 'Logged out'];

    }
}
