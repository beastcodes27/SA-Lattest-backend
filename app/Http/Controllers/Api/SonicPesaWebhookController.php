<?php

namespace App\Http\Controllers\Api;

use App\Services\SonicPesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SonicPesaWebhookController extends Controller
{
    protected SonicPesaService $sonicPesa;

    public function __construct(SonicPesaService $sonicPesa)
    {
        $this->sonicPesa = $sonicPesa;
    }

    /**
     * Handle incoming webhook notification from SonicPesa.
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('SonicPesa Webhook incoming request', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'status' => 'received',
            'message' => 'Webhook received',
        ]);
    }
}
