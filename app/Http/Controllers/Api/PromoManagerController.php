<?php

namespace App\Http\Controllers\Api;

use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoManagerController extends Controller
{
    public function index(): JsonResponse
    {
        $promos = Promo::query()
            ->withCount('redemptions as redeemed')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Promo $p) => $this->payload($p))
            ->values();

        return response()->json(['promos' => $promos]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:promos,code'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:trial_days,discount_percent'],
            'value' => ['required', 'integer', 'between:1,90'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'ends_at' => ['nullable', 'date'],
            'active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $promo = Promo::create([
            'code' => strtoupper($data['code']),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'max_uses' => $data['max_uses'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'active' => (bool) $data['active'],
        ]);

        return response()->json(['message' => "Promo {$promo->code} created.", 'promo' => $this->payload($promo)], 201);
    }

    public function update(Request $request, Promo $promo): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:trial_days,discount_percent'],
            'value' => ['required', 'integer', 'between:1,90'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'ends_at' => ['nullable', 'date'],
            'active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $promo->forceFill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'max_uses' => $data['max_uses'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'active' => (bool) $data['active'],
        ])->save();

        return response()->json(['message' => "Promo {$promo->code} updated.", 'promo' => $this->payload($promo->fresh())]);
    }

    private function payload(Promo $promo): array
    {
        return [
            'id' => $promo->id,
            'code' => $promo->code,
            'title' => $promo->title,
            'description' => $promo->description,
            'type' => $promo->type,
            'value' => $promo->value,
            'label' => $promo->label(),
            'max_uses' => $promo->max_uses,
            'uses_count' => (int) $promo->uses_count,
            'redeemed' => (int) ($promo->redeemed ?? 0),
            'starts_at' => $promo->starts_at?->toIso8601String(),
            'ends_at' => $promo->ends_at?->toIso8601String(),
            'active' => (bool) $promo->active,
            'created_at' => $promo->created_at?->toIso8601String(),
        ];
    }
}
