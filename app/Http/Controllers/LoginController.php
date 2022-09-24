<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request){

        $credentials = $request->only('email','password');

        if(! Auth::attempt($credentials)){
            return response()->json(['message' => 'Credenciales incorrectas','status' => 401]);
        }

        $accesToken = Auth::user()->createToken('guido123')->accessToken;

        return response()->json([
            'user' => Auth::user(),
            'access_token' => $accesToken
        ],200);

    }

    public function all(Request $request){
        $users = User::all();

        return response()->json(['data' => $users, 'status' => 200]);
    }

    public function activar_admin(Request $request)
    {
        $user = User::find($request->id);

        if(!$user){
            return response()->json(['message' => 'Usuario no encontrado','status' => 401]);
        }

        $user->tipo_usuario = User::ADMIN;

        $user->save();

        return $user;
        // $usuario = $user->activar_admin();

        // return $usuario;
    }
}
