@extends('emails.layout')

@section('title', 'Organization approved')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#200f35;">Your organization has been approved</h1>

    <p style="margin:0 0 16px;">Hello {{ $organization->name }} team,</p>

    <p style="margin:0 0 16px;">
        Your SmartAttend registration has been approved. Your organization is now active on the
        <strong>{{ ucfirst($organization->plan) }}</strong> plan.
        @if ($organization->trial_ends_at)
            Your free trial runs until <strong>{{ $organization->trial_ends_at->format('d M Y') }}</strong>.
        @endif
    </p>

    <p style="margin:0 0 20px;">You can now sign in to the SmartAttend app with the employee ID and password you registered with, and start adding branches and employees.</p>

    <a href="{{ config('app.url') }}" style="display:inline-block;background:#200f35;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 20px;border-radius:10px;">
        Open SmartAttend
    </a>
@endsection
