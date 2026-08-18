<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\Disable2FARequest;
use App\Http\Requests\Enable2FARequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterZankolineStudentRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SetPasswordRequest;
use App\Http\Requests\VerifyRequest;
use App\Http\Requests\ZankolineLoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ZankolineStudentResource;
use App\Mail\TwoFactorCodeMail;
use App\Models\HighSchoolStudent;
use App\Models\StudentAccountSetupToken;
use App\Models\User;
use App\Models\UserScope;
use App\Services\TwoFactorAuthenticationService;
use App\Services\UserScopeResolverService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * @group Authentication
 *
 * APIs authenticate user.
 */
class AuthController extends Controller
{
    use ApiResponses;

    public function register(RegisterRequest $request): JsonResponse
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

        return $this->created(
            'User registered successfully',
            (new UserResource($user->load(['userScopes.role:id,name', 'role:id,name'])))->resolve()
        );
    }

    // Login
    public function login(LoginRequest $request, UserScopeResolverService $scopeResolver, TwoFactorAuthenticationService $twoFactorAuthenticationService): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = $request->user();

        if (! $user->is_active) {
            return $this->error('Your account is deactivated.', 403);
        }

        if (! $user->canAccessAdminPanel()) {
            return $this->error('You are not allowed to access the admin panel.', 403);
        }

        if ($user->is_two_factor_enabled) {
            $challengeToken = $twoFactorAuthenticationService->execute($user);

            return $this->success('OTP sent to your email', [
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

    public function moodleLogin(LoginRequest $request, UserScopeResolverService $scopeResolver, TwoFactorAuthenticationService $twoFactorAuthenticationService): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        $user = $request->user();

        if (! $user->is_active) {
            return $this->error('Your account is deactivated.', 403);
        }

        if (! $user->canAccessMoodlePanel()) {
            return $this->error('You are not allowed to access the Moodle panel.', 403);
        }

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

    public function zankolineLogin(ZankolineLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $student = HighSchoolStudent::where('code', $credentials['code'])->first();

        if (! $student || ! Hash::check($credentials['password'], $student->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $token = $student->createToken('zankoline-token', ['zankoline'])->plainTextToken;

        return $this->ok('Logged in successfully', [
            'token' => $token,
            'student' => $student,
        ]);
    }

    public function zankolineRegister(RegisterZankolineStudentRequest $request)
    {
        $credentials = $request->validated();

        $student = HighSchoolStudent::create($credentials);

        return $this->created('Student created successfully', (new ZankolineStudentResource($student))->resolve());
    }

    public function verify(VerifyRequest $request, UserScopeResolverService $scopeResolver): JsonResponse
    {
        $userId = cache()->get("2fa_challenge_{$request->challenge_token}");

        if (! $userId) {
            return $this->error('Invalid or expired challenge token', 401);
        }

        $user = User::findOrFail($userId);

        if (! $user->is_active) {
            cache()->forget("2fa_challenge_{$request->challenge_token}");

            return $this->error('Your account is deactivated.', 403);
        }

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

    public function prepareTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        $otp = random_int(100000, 999999);
        $user->update([
            'two_factor_code' => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->queue(new TwoFactorCodeMail($otp, $user));

        return $this->ok('OTP sent to your email');
    }

    public function enableTwoFactor(Enable2FARequest $request): JsonResponse
    {
        $user = $request->user();
        $failKey = "2fa_manage_fails_{$user->id}";

        if (cache()->get($failKey, 0) >= 5) {
            $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
            cache()->forget($failKey);

            return $this->error('Too many invalid attempts. Request a new code.', 429);
        }

        if (! $user->two_factor_expires_at || now()->isAfter($user->two_factor_expires_at)) {
            return $this->error('OTP has expired, please request a new one.', 401);
        }

        if (! Hash::check((string) $request->otp, $user->two_factor_code)) {
            cache()->put($failKey, cache()->get($failKey, 0) + 1, now()->addMinutes(15));

            return $this->error('Invalid OTP', 401);
        }

        cache()->forget($failKey);

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'is_two_factor_enabled' => true,
        ]);

        return $this->ok('Two factor authentication enabled');
    }

    public function disableTwoFactor(Disable2FARequest $request): JsonResponse
    {
        $user = $request->user();
        $failKey = "2fa_manage_fails_{$user->id}";

        if (cache()->get($failKey, 0) >= 5) {
            $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
            cache()->forget($failKey);

            return $this->error('Too many invalid attempts. Request a new code.', 429);
        }

        if (! $user->two_factor_expires_at || now()->isAfter($user->two_factor_expires_at)) {
            return $this->error('OTP has expired, please request a new one.', 401);
        }

        if (! Hash::check((string) $request->otp, $user->two_factor_code)) {
            cache()->put($failKey, cache()->get($failKey, 0) + 1, now()->addMinutes(15));

            return $this->error('Invalid OTP', 401);
        }

        cache()->forget($failKey);

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'is_two_factor_enabled' => false,
        ]);

        return $this->ok('Two factor authentication disabled');
    }

    // Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok('Logged out successfully');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $request->user();

        if (! Hash::check($credentials['current_password'], $user->password)) {
            return $this->error('Current password is incorrect.', 401);
        }
        if ($credentials['password'] === $credentials['current_password']) {
            return $this->error("New password can't be the same as current one.", 422);
        }

        $user->update(['password' => $credentials['password']]);
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->ok('Password changed successfully');
    }

    // Forget Password
    public function forgetPassword(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            return $this->ok('If an account exists for that email, a reset link has been sent.');
        }

        Password::sendResetLink(['email' => $credentials['email']]);

        return $this->ok('If an account exists for that email, a reset link has been sent.');
    }

    // Reset Password
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $request->email)->first();

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is deactivated.'],
            ]);
        }
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->ok('Password reset successfully.')
            : $this->error('Invalid token or email, Please request a new reset link.', 422);
    }

    public function profile(Request $request, UserScopeResolverService $scopeResolver): JsonResponse
    {
        $user = $request->user();

        $user->load('roles:id,name');

        $userData = (new UserResource($user))->resolve();

        $userData['scopes'] = $scopeResolver->execute($user);

        return $this->ok(
            'User profile retrieved successfully',
            $userData
        );
    }

    public function zankolineMe(Request $request)
    {
        $user = $request->user();

        $user->load('contacts');

        return $this->ok(
            'Student profile retrieved successfully.',
            (new ZankolineStudentResource($user))->resolve()
        );
    }

    public function setPassword(SetPasswordRequest $request)
    {
        $credentials = $request->validated();
        DB::transaction(function () use ($credentials) {
            $tokenHash = hash('sha256', $credentials['token']);
            $token = StudentAccountSetupToken::where('token_hash', $tokenHash)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (! $token) {
                throw ValidationException::withMessages([
                    'token' => 'This setup link is invalid or has already been used.',
                ]);
            }

            if ($token->isExpired()) {
                throw ValidationException::withMessages([
                    'token' => 'This setup link has expired.',
                ]);
            }

            $user = $token->user;

            if (! $user) {
                throw ValidationException::withMessages([
                    'token' => 'No valid user associated with this token.',
                ]);
            }

            $user->update([
                'password' => Hash::make($credentials['password']),
            ]);

            $token->update(['used_at' => now()]);

        });

        return $this->ok('Password set successfully.');
    }
}
