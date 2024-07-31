<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
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

    public function login(Request $request){

        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'            
        ]);

        $user = User::where('username', $fields['username'])->first();

        if(!$user || !Hash::check($fields['password'], $user->password)){
            return responde([
                'message' => 'Bad creds'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);

    }



    public function logout (Request $request){

        auth()->user()->tokens()->delete();

        return ['message' => 'Logged out'];

    }
}
