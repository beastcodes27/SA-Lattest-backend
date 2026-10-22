<?php

namespace App\Http\Controllers\Api;

use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => AuthController::userPayload($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $user->forceFill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ])->save();

        return response()->json(['message' => 'Profile updated.', 'user' => AuthController::userPayload($user->fresh())]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $path = null;

        if ($request->hasFile('avatar')) {
            $validator = Validator::make($request->all(), [
                'avatar' => ['required', 'image', 'mimes:jpeg,png,webp,jpg', 'max:5120'],
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Please upload a JPEG, PNG or WebP image under 5 MB.'], 422);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->filled('avatar_base64')) {
            $base64Data = $request->input('avatar_base64');
            $type = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $matchedType = strtolower($matches[1]);
                if (in_array($matchedType, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $type = $matchedType === 'jpeg' ? 'jpg' : $matchedType;
                }
            }
            $imageBinary = base64_decode($base64Data);
            if ($imageBinary === false) {
                return response()->json(['message' => 'Invalid image data.'], 422);
            }
            $filename = 'avatars/'.uniqid('avatar_', true).'.'.$type;
            Storage::disk('public')->put($filename, $imageBinary);
            $path = $filename;
        } else {
            return response()->json(['message' => 'Please select a valid image to upload.'], 422);
        }

        if ($path) {
            if ($user->avatar_path && $user->avatar_path !== $path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->forceFill(['avatar_path' => $path])->save();
        }

        return response()->json([
            'message' => 'Profile photo updated.',
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }

    public function sendPasswordChangeCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! OtpService::isAdmin($user)) {
            return response()->json(['message' => 'Email verification is not required for this account.'], 403);
        }

        $rateLimitKey = 'password-change-otp:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, OtpService::MAX_EMAILS_PER_HOUR)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()
                ->json([
                    'message' => 'You have requested too many verification codes. Please wait before trying again.',
                    'retry_after' => $retryAfter,
                ], 429)
                ->header('Retry-After', (string) $retryAfter);
        }

        $sent = OtpService::send($user, 'password_change', 'password change');

        if (! $sent) {
            return response()->json(['message' => 'Could not send the verification email. Please try again.'], 502);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        return response()->json([
            'message' => "A verification code was sent to {$user->email}.",
            'expires_in_minutes' => OtpService::TTL_MINUTES,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $requiresOtp = OtpService::isAdmin($user);

        $rules = [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ];

        if ($requiresOtp) {
            $rules['otp'] = ['required', 'digits:6'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
        }

        $orgDefaultPassword = $user->organization?->default_employee_password;
        if ($orgDefaultPassword && $request->new_password === $orgDefaultPassword) {
            return response()->json(['message' => 'New password cannot be the organization default password. Please choose a unique password.'], 422);
        }

        if ($requiresOtp && ! OtpService::verify($user, 'password_change', (string) $request->input('otp'))) {
            return response()->json([
                'message' => 'The verification code is invalid or has expired.',
                'errors' => ['otp' => ['The verification code is invalid or has expired.']],
            ], 422);
        }

        $user->forceFill([
            'password' => $request->new_password,
            'must_change_password' => false,
        ])->save();

        return response()->json(['message' => 'Password changed.']);
    }

    public function forceChangePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return response()->json(['message' => 'Password already set.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:6'],
            // Contact details are only required when the account does not already
            // have them (e.g. after an admin password reset the profile is intact).
            'email' => [$user->email ? 'nullable' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => [$user->phone ? 'nullable' : 'required', 'string', 'max:40'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $orgDefaultPassword = $user->organization?->default_employee_password ?: 'SmartAttend@123';

        if (Hash::check($request->new_password, $user->password) || $request->new_password === $orgDefaultPassword) {
            return response()->json([
                'message' => 'New password cannot be the organization default or temporary password. Please choose a unique password.',
            ], 422);
        }

        $updates = [
            'password' => $request->new_password,
            'must_change_password' => false,
        ];

        if ($request->filled('email')) {
            $updates['email'] = $request->email;
        }
        if ($request->filled('phone')) {
            $updates['phone'] = $request->phone;
        }

        $user->forceFill($updates)->save();

        return response()->json([
            'message' => 'Account setup complete. You can now check in.',
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }
}
