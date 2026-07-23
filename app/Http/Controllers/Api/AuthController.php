<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\Enable2FARequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyRequest;
use App\Http\Resources\UserResource;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use App\Models\UserScope;
use App\Services\TwoFactorAuthenticationService;
use App\Services\UserScopeResolverService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @group Authentication
 *
 * APIs authenticate user.
 */
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
            $user->refresh();

            return [$user, $role];
        });

        return $this->ok(
            'User registered successfully',
            (new UserResource($user->load(['userScopes.role:id,name', 'role:id,name'])))->resolve()
        );
    }

    // Login
    public function login(LoginRequest $request, UserScopeResolverService $scopeResolver, TwoFactorAuthenticationService $twoFactorAuthenticationService)
    {

        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }
        $user = Auth::user();
        if (! $user->canAccessAdminPanel()) {
            Auth::logout();

            return $this->error('You are not allowed to access the admin panel.', 403);
        }

        if ($user->is_two_factor_enabled) {
            $challengeToken = $twoFactorAuthenticationService->execute($user);

            return $this->ok('OTP sent to your email', [

                'challenge_token' => $challengeToken,
            ], 202);
        }
        $token = $user->createToken('api-token', ['admin'])->plainTextToken;

        $user->load('roles:id,name');

        $userData = (new UserResource($user))->resolve();

        $userData['scopes'] = $scopeResolver->execute($user);

        return $this->ok(
            'User logged in successfully',
            [
                'token' => $token,
                'user' => $userData,
            ]
        );
    }

    public function moodleLogin(LoginRequest $request, UserScopeResolverService $scopeResolver, TwoFactorAuthenticationService $twoFactorAuthenticationService)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = Auth::user();
        if (! $user->canAccessMoodlePanel()) {
            Auth::logout();

            return $this->error('You are not allowed to access the Moodle panel.', 403);
        }
        // TWO-FACTOR-AUTHENTICATION
        //        if($user->is_two_factor_enabled){
        //            $challengeToken = $twoFactorAuthenticationService->execute($user);
        //            return $this->ok('OTP sent to your email', [
        //                'challenge_token' => $challengeToken,
        //            ], 202);
        //        }

        $token = $user->createToken('moodle-token', ['moodle'])->plainTextToken;

        $user->load('roles:id,name');

        $userData = (new UserResource($user))->resolve();

        $userData['scopes'] = $scopeResolver->execute($user);

        return $this->ok(
            'User logged in successfully',
            [
                'token' => $token,
                'user' => $userData,
            ]
        );

    }

    public function verify(VerifyRequest $request, UserScopeResolverService $scopeResolver)
    {
        $userId = cache()->get("2fa_challenge_{$request->challenge_token}");

        if (! $userId) {
            return $this->error('Invalid or expired challenge token', 401);
        }

        $user = User::findOrFail($userId);

        // ↓ brute-force counter goes here, before any OTP check
        $failKey = "2fa_fails_{$request->challenge_token}";
        $fails = cache()->get($failKey, 0);

        if ($fails >= 5) {
            cache()->forget("2fa_challenge_{$request->challenge_token}");

            return $this->error('Too many attempts, please login again', 429);
        }

        if (! Hash::check((string) $request->otp, $user->two_factor_code)) {
            cache()->put($failKey, $fails + 1, now()->addMinutes(10));

            return $this->error('Invalid OTP', 401);
        }

        if (now()->isAfter($user->two_factor_expires_at)) {
            return $this->error('OTP has expired, please login again', 401);
        }

        cache()->forget("2fa_challenge_{$request->challenge_token}");
        cache()->forget($failKey);

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);
        $user->load('roles:id,name');

        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        $userData = (new UserResource($user))->resolve();

        $userData['scopes'] = $scopeResolver->execute($user);

        return $this->ok(
            'User logged in successfully',
            [
                'token' => $token,
                'user' => $userData,
            ]
        );
    }

    public function prepareTwoFactor(Request $request)
    {
        $user = Auth::user();

        $otp = random_int(100000, 999999);
        $user->update([
            'two_factor_code' => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->queue(new TwoFactorCodeMail($otp, $user));

        return $this->ok('OTP sent to your email');
    }

    public function enableTwoFactor(Enable2FARequest $request)
    {
        $user = $request->user();

        if (! Hash::check((string) $request->otp, $user->two_factor_code)) {
            return $this->error('Invalid OTP', 401);
        }
        if (now()->isAfter($user->two_factor_expires_at)) {
            return $this->error('OTP has expired, please login again', 401);
        }

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'is_two_factor_enabled' => true,
        ]);

        return $this->ok('Two factor authentication enabled');
    }

    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();

        if (! Hash::check((string) $request->otp, $user->two_factor_code)) {
            return $this->error('Invalid OTP', 401);
        }
        if (now()->isAfter($user->two_factor_expires_at)) {
            return $this->error('OTP has expired, please login again', 401);
        }
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'is_two_factor_enabled' => false,
        ]);

        return $this->ok('Two factor authentication disabled');
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

    // Get Profile
    public function profile(UserScopeResolverService $scopeResolver)
    {
        $user = Auth::user();

        $user->load('roles:id,name');

        $userData = (new UserResource($user))->resolve();

        $userData['scopes'] = $scopeResolver->execute($user);

        return $this->ok(
            'User logged in successfully',
            $userData

        );
    }
}
