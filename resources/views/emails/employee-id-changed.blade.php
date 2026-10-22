@extends('emails.layout')

@section('title', 'Your employee ID has been updated')
@section('preheader', 'Your organization updated your employee sign-in ID')
@section('badge', 'Account update')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Your employee ID has changed</h1>

    <p style="margin:0 0 4px;">Hello {{ $employee->name }},</p>

    <p style="margin:0 0 4px;">
        {{ $organizationName }} updated its employee ID prefix. Your sign-in ID has been replaced with a new one.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Organization' => $organizationName,
        'Previous employee ID' => $oldEmployeeId,
        'New employee ID' => $newEmployeeId,
    ]])

    @include('emails.partials.panel', [
        'tone' => 'info',
        'title' => 'Use your new ID next time you sign in',
        'body' => 'Your password has not changed. Sign in with your new employee ID and your existing password. Your attendance history and account settings are unchanged.',
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">If you were not expecting this change, contact your organization administrator.</p>
@endsection
