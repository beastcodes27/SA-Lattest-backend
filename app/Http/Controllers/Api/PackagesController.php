<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackagesController extends Controller
{
    public function publicList(): JsonResponse
    {
        return response()->json([
            'packages' => Package::query()
                ->where('active', true)
                ->orderBy('position')
                ->get()
                ->map(fn (Package $p) => $this->payload($p))
                ->values(),
        ]);
    }

    public function systemIndex(): JsonResponse
    {
        return response()->json([
            'packages' => Package::query()
                ->orderBy('position')
                ->get()
                ->map(fn (Package $p) => $this->payload($p))
                ->values(),
        ]);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'price_label' => ['nullable', 'string', 'max:40'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'employee_limit' => ['nullable', 'integer', 'min:1'],
            'branch_limit' => ['nullable', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $package->forceFill([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'price_label' => $data['price_label'] ?? null,
            'features' => $data['features'] ?? [],
            'employee_limit' => $data['employee_limit'] ?? null,
            'branch_limit' => $data['branch_limit'] ?? null,
            'active' => (bool) $data['active'],
            'position' => $data['position'] ?? $package->position,
        ])->save();

        return response()->json([
            'message' => $package->name.' updated.',
            'package' => $this->payload($package->fresh()),
        ]);
    }

    private function payload(Package $package): array
    {
        return [
            'id' => $package->id,
            'code' => $package->code,
            'name' => $package->name,
            'tagline' => $package->tagline,
            'price_label' => $package->price_label,
            'features' => $package->features ?? [],
            'employee_limit' => $package->employee_limit,
            'branch_limit' => $package->branch_limit,
            'active' => (bool) $package->active,
            'position' => (int) $package->position,
        ];
    }
}
