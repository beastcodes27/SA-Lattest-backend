@extends('emails.layout')

@section('title', 'Your system administrator account')
@section('preheader', 'Your system administrator account is ready')
@section('badge', 'Admin access')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Your administrator account is ready</h1>

    <p style="margin:0 0 4px;">Hello {{ $admin->name }},</p>

    <p style="margin:0 0 4px;">
        {{ $createdByName }} created a system administrator account for you. You can use the details below to sign in to the management console.
    </p>

    @include('emails.partials.details', ['rows' => array_filter([
        'Account' => $admin->name,
        'Sign-in ID' => $admin->employee_id,
        'Role' => ucfirst(str_replace('_', ' ', $admin->role)),
        'Email' => $admin->email,
        'Temporary password' => $temporaryPassword,
    ])])

    @include('emails.partials.panel', [
        'tone' => 'warning',
        'title' => 'Change your password after signing in',
        'body' => 'Your temporary password was set by another administrator. Sign in and change it to a private password as soon as possible. Never share your credentials with anyone.',
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">If you were not expecting this account, contact the platform owner immediately.</p>
@endsection
