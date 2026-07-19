<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Login — authenticate user and return Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_id' => 'nullable|uuid', // optional: login within specific tenant
            'token_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Find user by email (optionally scoped to tenant)
        $query = User::where('email', $request->email);

        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => __('auth.inactive'),
            ], Response::HTTP_FORBIDDEN);
        }

        // 2FA check — if enabled, require code (you can extend this)
        if ($user->two_factor_enabled) {
            // For MVP, we skip actual 2FA verification and just flag it
            // In production, validate OTP here before issuing token
        }

        // Create token
        $tokenName = $request->input('token_name', $request->userAgent() ?? 'api-token');
        $token = $user->createToken($tokenName, ['*']);

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => __('auth.login_success'),
            'user' => $this->userResource($user),
            'token' => $token->plainTextToken,
            'tenant' => $user->tenant ? [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name ?? $user->tenant->id,
            ] : null,
        ]);
    }

    /**
     * Register a new user (within a tenant).
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required_without:tenant_id|string|max:255',
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'nullable|string|in:pt_BR,en,es',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create tenant if needed
        $tenantId = $request->tenant_id;

        if (! $tenantId) {
            $tenant = Tenant::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'name' => $request->tenant_name,
            ]);
            $tenantId = $tenant->id;
        }

        // Create user
        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'locale' => $request->input('locale', 'pt_BR'),
        ]);

        // Assign default role
        $user->assignRole('admin');

        $token = $user->createToken('registration', ['*']);

        return response()->json([
            'message' => __('auth.register_success'),
            'user' => $this->userResource($user),
            'token' => $token->plainTextToken,
            'tenant' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name ?? $user->tenant->id,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return response()->json([
            'user' => $this->userResource($user),
            'tenant' => $user->tenant ? [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name ?? $user->tenant->id,
                'created_at' => $user->tenant->created_at,
            ] : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'locale' => 'sometimes|string|in:pt_BR,en,es',
            'timezone' => 'sometimes|string|max:50',
            'avatar_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->user()->update($validator->validated());

        return response()->json([
            'message' => __('auth.profile_updated'),
            'user' => $this->userResource($request->user()->fresh()),
        ]);
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password_mismatch')],
            ]);
        }

        $user->update(['password' => $request->password]);

        // Revoke all tokens except current
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => __('auth.password_changed'),
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json(['message' => __('auth.2fa_already_enabled')], Response::HTTP_CONFLICT);
        }

        // Generate a secret (in production, use a proper 2FA library)
        $secret = \Illuminate\Support\Str::random(32);

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
        ]);

        return response()->json([
            'message' => __('auth.2fa_enabled'),
            'secret' => $secret, // In production, return QR code URL instead
        ]);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        return response()->json(['message' => __('auth.2fa_disabled')]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * Format user for API response.
     */
    private function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'avatar_url' => $user->avatar_url,
            'two_factor_enabled' => $user->two_factor_enabled,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
