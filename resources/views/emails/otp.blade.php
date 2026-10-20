@extends('emails.layout')

@section('title', 'Your verification code')
@section('preheader', 'Your one-time code expires in '.($expiresInMinutes ?? 15).' minutes')
@section('badge', 'Security code')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Your {{ $purpose ?? 'verification' }} code</h1>

    <p style="margin:0 0 4px;">
        @if (! empty($name))
            Hello {{ $name }},
        @else
            Hello,
        @endif
    </p>

    <p style="margin:0 0 4px;">Use the one-time code below to continue. It can only be used once.</p>

    @include('emails.partials.code', [
        'code' => $code,
        'label' => 'One-time code',
        'expiresInMinutes' => $expiresInMinutes ?? 15,
    ])

    @include('emails.partials.panel', [
        'tone' => 'warning',
        'title' => 'Keep this code private',
        'body' => 'Never share this code with anyone. Our team will never ask you for it.',
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">If you did not request this code, you can safely ignore this email.</p>
@endsection
