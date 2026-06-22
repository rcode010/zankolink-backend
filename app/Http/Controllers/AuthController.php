<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponses;

    // Register
    public function register(RegisterRequest $request)
    {
        $credentials = $request->validated();

        $user = User::create($credentials);

        return $this->ok(
            'User registered successfully',
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'position' => $user->position,
                'role_scope_id' => $user->role_scope_id,
                'role_scope_type' => $user->role_scope_type,
            ]
        );
    }

    // Login
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->ok(
            'User logged in successfully',
            [
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
            ]
        );
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok('Logged out successfully');
    }

    // Change Password
    public function changePassword(ChangePasswordRequest $request)
    {
        $credentials = $request->validated();
        $user = Auth::user();

        if (! Hash::check($credentials['current_password'], $user->password)) {
            return $this->error('Current password is incorrect.', 401);
        }
        if ($credentials['password'] === $credentials['current_password']) {
            return $this->error("New password can't be the same as current one.", 422);

        }

        $user->update(['password' => $credentials['password']]);

        return $this->ok('Password changed successfully');
    }

    // Forget Password
    public function forgetPassword(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        $status = Password::sendResetLink(['email' => $credentials['email']]);

        return $status === Password::RESET_LINK_SENT
        ? $this->ok('Password reset link sent to your email.')
        : $this->error('Unable to snd reset link.', 400);

    }

    // Reset Password
    public function resetPassword(ResetPasswordRequest $request)
    {
        $credentials = $request->validated();

        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->ok('Password reset successfully.')
            : $this->error('Invalid token or email, Please request a new reset link.', 422);
    }
}
