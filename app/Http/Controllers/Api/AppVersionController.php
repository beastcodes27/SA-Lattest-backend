<?php

namespace App\Http\Controllers\Api;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppVersionController extends Controller
{
    /**
     * Public endpoint for mobile app version check.
     */
    public function check(Request $request): JsonResponse
    {
        $platform = strtolower($request->query('platform', 'android'));
        if (! in_array($platform, ['android', 'ios'])) {
            $platform = 'android';
        }

        $currentVersion = $request->query('version', '1.0.0');

        $config = AppVersion::forPlatform($platform);

        $isBelowMin = version_compare($currentVersion, $config->min_version, '<');
        $isOlderThanLatest = version_compare($currentVersion, $config->latest_version, '<');

        $updateType = 'none';
        if ($isBelowMin || ($config->force_update && $isOlderThanLatest)) {
            $updateType = 'forced';
        } elseif ($isOlderThanLatest) {
            $updateType = 'smooth';
        }

        return response()->json([
            'platform' => $config->platform,
            'current_installed' => $currentVersion,
            'latest_version' => $config->latest_version,
            'min_version' => $config->min_version,
            'force_update' => (bool) $config->force_update,
            'update_available' => $updateType !== 'none',
            'update_type' => $updateType, // 'none', 'smooth', 'forced'
            'title' => $config->title,
            'release_notes' => $config->release_notes,
            'apk_url' => $config->apk_url,
            'store_url' => $config->store_url,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * System Admin: Get current app version settings.
     */
    public function adminShow(Request $request): JsonResponse
    {
        $platform = strtolower($request->query('platform', 'android'));
        $config = AppVersion::forPlatform($platform);

        return response()->json(['config' => $config]);
    }

    /**
     * System Admin: Update app version settings.
     */
    public function adminUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'latest_version' => ['required', 'string', 'max:20'],
            'min_version' => ['required', 'string', 'max:20'],
            'force_update' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'release_notes' => ['nullable', 'string', 'max:2000'],
            'apk_url' => ['nullable', 'url', 'max:500'],
            'store_url' => ['nullable', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $platform = strtolower($request->input('platform', 'android'));
        $config = AppVersion::forPlatform($platform);

        $config->update($validator->validated());

        return response()->json([
            'message' => 'App update settings updated successfully.',
            'config' => $config->refresh(),
        ]);
    }
}
