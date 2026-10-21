@extends('emails.layout')

@section('title', 'Registration request received')
@section('preheader', 'We received the registration request for '.$organization->name)
@section('badge', 'Request received')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Registration request received</h1>

    <p style="margin:0 0 4px;">Hello {{ $admin->name }},</p>

    <p style="margin:0 0 4px;">
        Thank you for registering {{ $organization->name }}. Your request has been received and is now pending review.
    </p>

    @include('emails.partials.details', ['rows' => array_filter([
        'Organization' => $organization->name,
        'Plan' => ucfirst($organization->plan),
        'Admin sign-in ID' => $admin->employee_id,
        'Contact email' => $admin->email,
        'Submitted' => $organization->created_at?->format('d M Y, H:i'),
        'Status' => 'Pending review',
    ])])

    @include('emails.partials.panel', [
        'tone' => 'info',
        'title' => 'What happens next',
        'body' => 'Our team will review your organization details and documents. We will email you as soon as your registration is approved or if we need more information.',
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">You will be able to sign in with your admin ID after your organization is approved. Keep this email for your records.</p>
@endsection
