@extends('emails.layout')

@section('title', 'Your password reset code')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#200f35;">Reset your password</h1>

    <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>

    <p style="margin:0 0 20px;">Use the code below to reset your SmartAttend password. It expires in {{ $expiresInMinutes }} minutes.</p>

    <div style="background:#200f35;border-radius:12px;padding:22px;text-align:center;margin:0 0 20px;">
        <span style="font-size:32px;font-weight:700;letter-spacing:10px;color:#ffffff;">{{ $code }}</span>
    </div>

    <p style="margin:0 0 16px;">Enter this code in the app together with your new password.</p>

    <p style="margin:0;color:#6b7280;font-size:13px;">If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
@endsection
