<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    // Login
    public function login(LoginRequest $request){
        $credentials = $request->validated();

        if(!Auth::attempt($credentials)){
            return response([
                "success" => false,
                "message" => "Invalid credentials"
            ],400);
        }
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        return response([
            "success" => true,
            "message" => "Logged in successfully",
            "data" => [
                "name"=>$user->name,
                "email"=>$user->email,
                "token"=>$token,
            ]
        ]);
    }
}
