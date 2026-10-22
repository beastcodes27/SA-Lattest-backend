@extends('emails.layout')

@section('title', 'System admin sign-in alert')
@section('preheader', 'A system administrator account signed in to the console')
@section('badge', 'Security alert')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">System admin console sign-in</h1>

    <p style="margin:0 0 4px;">Hello {{ $user->name }},</p>

    <p style="margin:0 0 4px;">
        Your system administrator account signed in to the SmartAttend management console. If this was you, no action is needed.
    </p>

    @include('emails.partials.details', ['rows' => array_filter([
        'Account' => $user->name,
        'Sign-in ID' => $user->employee_id,
        'Role' => ucfirst(str_replace('_', ' ', $user->role)),
        'Time' => $loggedInAt,
        'IP address' => $ipAddress,
        'Device' => $userAgent,
    ])])

    @include('emails.partials.panel', [
        'tone' => 'warning',
        'title' => 'Was this not you?',
        'body' => 'If you did not sign in, change your password immediately and contact the platform owner so the session can be revoked.',
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">For security, this alert does not include any sign-in links.</p>
@endsection
