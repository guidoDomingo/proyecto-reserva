<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as RulesPassword;

class LoginController extends Controller
{
    public function login(Request $request){

        $credentials = $request->only('email','password');

        if(! Auth::attempt($credentials)){
            return response()->json(['message' => 'Credenciales incorrectas','code' => 407, 'error' => true]);
        }

        $accesToken = Auth::user()->createToken('guido123')->accessToken;

        return response()->json([
            'error' => false,
            'code' => 200,
            'user' => Auth::user(),
            'access_token' => $accesToken
        ],200);

    }

    public function register(Request $request){

            $data = $request->all();

           $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed', RulesPassword::defaults()],
            ]);


            $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
            ]);


            $accesToken = $user->createToken('guido123')->accessToken;

            return response()->json([
                'error' => false,
                'code' => 200,
                'user' => $user,
                'access_token' => $accesToken
            ],200);

    }

    public function all(Request $request){
        $users = User::all();

        return response()->json(['data' => $users, 'code' => 200]);
    }

    public function activar_admin(Request $request)
    {
        $user = User::find($request->id);

        if(!$user){
            return response()->json(['message' => 'Usuario no encontrado','code' => 409]);
        }

        $user->tipo_usuario = User::ADMIN;

        $user->save();

        return response()->json(['error' => false, 'code' => 200, 'data' =>  $user]);

    }
}
