<?php

namespace App\Http\Controllers\Api;

use App\Services\FaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please upload a JPEG, PNG or WebP image under 5 MB.'], 422);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar_path && $user->avatar_path !== $path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        return response()->json([
            'message' => 'Profile photo updated.',
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }

    public function enrollFace(Request $request, FaceService $faces): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please upload a JPEG, PNG or WebP image under 5 MB.'], 422);
        }

        $result = $faces->enroll($request->file('image'));

        if (! $result['ok']) {
            return response()->json(['message' => $result['error'] ?? 'Could not read your face. Try better lighting.'], 422);
        }

        $path = $request->file('image')->store('faces', 'public');

        if ($user->face_photo_path && $user->face_photo_path !== $path) {
            Storage::disk('public')->delete($user->face_photo_path);
        }

        $user->forceFill([
            'face_enrolled' => true,
            'face_signature' => json_encode($result['signature']),
            'face_photo_path' => $path,
        ])->save();

        return response()->json([
            'message' => 'Face enrolled. You can now check in with a face scan.',
            'user' => AuthController::userPayload($user->fresh()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Password must be at least 6 characters.'], 422);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'New password must be different from the temporary one.'], 422);
        }

        $user->forceFill([
            'password' => $request->new_password,
            'must_change_password' => false,
        ])->save();

        return response()->json(['message' => 'Password set. You can now check in.', 'user' => AuthController::userPayload($user->fresh())]);
    }
}
