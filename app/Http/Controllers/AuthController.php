<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponses;

    // Register
    public function register(RegisterRequest $request){
        $credentials = $request->validated();

        $user = User::create($credentials);


        return $this->ok(
            "User registered successfully",
            [
                "name"=>$user->name,
                "email"=>$user->email,
                "phone"=>$user->phone,
                "position"=>$user->position,
                "role_scope_id"=>$user->role_scope_id,
                "role_scope_type"=>$user->role_scope_type,
            ]
        );
    }

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

    // Change Password
    public function changePassword(ChangePasswordRequest $request){
        $credentials = $request->validated();
        $user = Auth::user();

        if (! Hash::check($credentials['current_password'], $user->password)) {
            return $this->error("Current password is incorrect.", 401);
        }
        if ($credentials['password'] === $credentials['current_password']) {
            return $this->error("New password can't be the same as current one.", 422);

        }

            $user->update(['password'=>$credentials['password']]);

        return $this->ok("Password changed successfully");
    }
}
