<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class FaceService
{
    public function enroll(UploadedFile $file): array
    {
        try {
            $response = Http::timeout(30)
                ->asMultipart()
                ->post(config('services.face.url').'/enroll', [
                    [
                        'name' => 'image',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                    ],
                ]);
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Face service is unavailable. Please try again.'];
        }

        if ($response->failed()) {
            return ['ok' => false, 'error' => $response->json('error') ?? 'Face service error.'];
        }

        $data = $response->json();

        return [
            'ok' => (bool) ($data['detected'] ?? false),
            'error' => $data['error'] ?? null,
            'signature' => $data['signature'] ?? null,
        ];
    }

    public function verify(UploadedFile $file, string $signature): array
    {
        try {
            $response = Http::timeout(30)
                ->asMultipart()
                ->post(config('services.face.url').'/verify', [
                    ['name' => 'signature', 'contents' => $signature],
                    [
                        'name' => 'image',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                    ],
                ]);
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'Face service is unavailable. Please try again.'];
        }

        if ($response->failed()) {
            return ['ok' => false, 'error' => $response->json('error') ?? 'Face service error.'];
        }

        $data = $response->json();

        return [
            'ok' => true,
            'matched' => (bool) ($data['matched'] ?? false),
            'score' => $data['score'] ?? null,
            'error' => $data['error'] ?? null,
        ];
    }
}
