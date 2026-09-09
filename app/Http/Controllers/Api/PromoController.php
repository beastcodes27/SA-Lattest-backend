<?php

namespace App\Http\Controllers\Api;

use App\Models\Promo;
use App\Models\PromoRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoController extends Controller
{
    public function redeem(Request $request): JsonResponse
    {
        $org = $request->user()->organization;

        $code = strtoupper(trim((string) $request->input('code')));

        if ($code === '') {
            return response()->json(['message' => 'Enter a promo code.'], 422);
        }

        $promo = Promo::where('code', $code)->first();

        if (! $promo || ! $promo->isRedeemable()) {
            return response()->json(['message' => 'This promo code is invalid or no longer available.'], 422);
        }

        if (PromoRedemption::where('promo_id', $promo->id)->where('org_id', $org->id)->exists()) {
            return response()->json(['message' => 'Your organization has already used this promo code.'], 422);
        }

        DB::transaction(function () use ($promo, $org, $code) {
            if ($promo->type === 'trial_days') {
                $base = $org->trial_ends_at && $org->trial_ends_at->isFuture() ? $org->trial_ends_at : now();
                if ($org->trial_started_at === null) {
                    $org->forceFill(['trial_started_at' => now()])->save();
                }
                $org->forceFill(['trial_ends_at' => $base->copy()->addDays((int) $promo->value)])->save();
            } elseif ($promo->type === 'discount_percent') {
                $org->forceFill(['discount_percent' => min(90, (int) $promo->value)])->save();
            }

            PromoRedemption::create(['promo_id' => $promo->id, 'org_id' => $org->id]);
            $promo->increment('uses_count');
        });

        return response()->json([
            'message' => "Promo '{$code}' applied: ".$promo->label().'.',
            'reward' => ['type' => $promo->type, 'value' => $promo->value, 'label' => $promo->label()],
            'subscription' => SubscriptionController::payload($org->fresh()),
        ]);
    }
}
