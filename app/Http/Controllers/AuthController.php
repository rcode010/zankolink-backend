<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    use ApiResponses;

    // Login
    public function login(LoginRequest $request){
        $credentials = $request->validated();

        if(!Auth::attempt($credentials)){
            return $this->error("Invalid credentials", 401);
        }
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        return $this->ok(
            "User logged in successfully",
            [
                "name"=>$user->name,
                "email"=>$user->email,
                "token"=>$token,
            ]
        );
    }

    // Logout
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return $this->ok("Logged out successfully");
    }
}
