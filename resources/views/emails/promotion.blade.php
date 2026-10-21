@extends('emails.layout')

@section('title', $title)
@section('preheader', $offerLabel.' — use code '.$code)
@section('badge', 'Special offer')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">{{ $title }}</h1>

    <div style="margin:0 0 4px;">{!! nl2br(e($body)) !!}</div>

    @include('emails.partials.details', ['rows' => array_filter([
        'Offer' => $offerLabel,
        'Promo code' => $code,
        'Valid until' => $expiresAt,
    ])])

    @include('emails.partials.code', [
        'code' => $code,
        'label' => 'Use this code at checkout',
    ])

    @if (! empty($actionUrl) && ! empty($actionLabel))
        @include('emails.partials.button', ['url' => $actionUrl, 'label' => $actionLabel])
    @endif

    <p style="margin:0;font-size:13px;color:#6B7280;">Enter the code when upgrading or renewing your subscription. Terms and expiry dates apply.</p>
@endsection
