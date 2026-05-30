<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\CachesApiResponse;
use App\Http\Concerns\StoresPhotos;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RedisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

abstract class BaseAuthController extends Controller
{
    use StoresPhotos, CachesApiResponse;

    abstract protected function company(): string;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
                    ->where(function ($q) {
                        $q->where('company', $this->company())
                          ->orWhere('company', 'telcovantage');
                    })
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is ' . $user->status . '. Please contact your administrator.',
            ], 403);
        }

        $user->update(['last_login' => now()]);

        // Bust stale /me cache so the fresh login_at is visible immediately
        $this->bustCache(RedisCache::userKey($user->id, 'me'));

        $token = $user->createToken($this->company() . '-token')->plainTextToken;

        return response()->json([
            'token'                   => $token,
            'password_reset_required' => $user->password_reset_required,
            'user'                    => $this->userResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        // Bust /me cache on logout so stale data never leaks to next session
        $this->bustCache(RedisCache::userKey($user->id, 'me'));

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /me — user-private, cached 5 min per user.
     *
     * Cache key: cache:user:{id}:me
     * ETag key:  cache:user:{id}:me:etag
     *
     * Mobile calls this on every app open / resume.  With a 5-minute Redis
     * cache and ETag support, repeated calls within the TTL return 304 with
     * zero DB queries.
     */
    public function me(Request $request): JsonResponse
    {
        $user     = $request->user();
        $cacheKey = RedisCache::userKey($user->id, 'me');

        return $this->cachedResponse(
            cacheKey:   $cacheKey,
            ttl:        RedisCache::TTL_ME,
            callback:   fn () => $this->userResource($user),
            request:    $request,
            visibility: 'private',
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name'    => 'sometimes|string|max:100',
            'last_name'     => 'sometimes|string|max:100',
            'cellphone'     => 'sometimes|string|max:20',
            'address'       => 'sometimes|string',
            'profile_photo' => 'sometimes|image|max:10240',
        ]);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $this->storePhoto($request->file('profile_photo'), 'profiles');
        }

        $old = $user->only(array_keys($data));
        $user->update($data);
        \App\Models\AuditLog::record('update', $user, $old, $user->fresh()->only(array_keys($data)));

        // Bust /me cache so the next request gets fresh profile data
        $this->bustCache(RedisCache::userKey($user->id, 'me'));

        return response()->json($this->userResource($user->fresh()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required_unless:password_reset_required,true|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! $user->password_reset_required) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
        }

        $user->update([
            'password'                => Hash::make($request->password),
            'password_reset_required' => false,
            'temp_password_set_at'    => null,
        ]);

        // Bust /me cache — password_reset_required flag changed
        $this->bustCache(RedisCache::userKey($user->id, 'me'));

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)
                    ->where('company', $this->company())
                    ->first();

        if (! $user) {
            return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
        }

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'If that email exists, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            fn (User $user, string $password) => $user->update(['password' => Hash::make($password)])
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }

    protected function userResource(User $user): array
    {
        return [
            'id'                      => $user->id,
            'company'                 => $user->company,
            'role'                    => $user->role,
            'first_name'              => $user->first_name,
            'last_name'               => $user->last_name,
            'full_name'               => $user->full_name,
            'email'                   => $user->email,
            'cellphone'               => $user->cellphone,
            'address'                 => $user->address,
            'profile_photo'           => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
            'status'                  => $user->status,
            'password_reset_required' => $user->password_reset_required,
            'team_id'                 => $user->team_id,
            'subcontractor_id'        => $user->subcontractor_id,
            'last_login'              => $user->last_login?->toISOString(),
            'is_online'               => $user->isOnline(),
        ];
    }
}
