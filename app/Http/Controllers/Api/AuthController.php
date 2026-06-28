<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Models\UserScope;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use ApiResponses;

    // Register
    public function register(RegisterRequest $request)
    {
        $credentials = $request->validated();
        [$user, $role] = DB::transaction(function () use ($credentials) {
            $user = User::create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'phone' => $credentials['phone'],
            ]);

            $role = Role::where('name', $credentials['role'])->firstOrFail();
            $user->assignRole($role);

            UserScope::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'scope_type' => $credentials['scope_type'],
                'scope_id' => $credentials['scope_id'] ?? null,
            ]);

            return [$user, $role];
        });

        return $this->ok(
            'User registered successfully',
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $role->name,
                'scope_type' => $credentials['scope_type'],
                'scope_id' => $credentials['scope_id'] ?? null,
            ]
        );
    }

    // Login
    public function login(LoginRequest $request)
    {

        $credentials = $request->validated();
        $allowedRoles = [
            'MINISTRY_ADMIN',
            'MINISTRY_STAFF',
            'UNIVERSITY_ADMIN',
            'UNIVERSITY_STAFF',
            'DEAN',
            'DEPARTMENT_HEAD',
        ];


        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }
        $user = Auth::user();
        if (! $user->hasAnyRole($allowedRoles)) {
            Auth::logout();

            return $this->error('You are not allowed to access the admin panel.', 403);
        }

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
