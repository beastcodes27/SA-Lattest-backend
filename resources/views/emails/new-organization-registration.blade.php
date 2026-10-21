@extends('emails.layout')

@section('title', 'New organization registration')
@section('preheader', $organization->name.' is waiting for review')
@section('badge', 'Admin alert')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">New registration request</h1>

    <p style="margin:0 0 4px;">A new organization has registered and is waiting for review.</p>

    @include('emails.partials.details', ['rows' => [
        'Organization' => $organization->name,
        'Plan' => ucfirst($organization->plan),
        'Admin' => $admin->name,
        'Admin email' => $admin->email,
        'Admin phone' => $admin->phone ?: '—',
    ]])

    <p style="margin:0;">Sign in to the system admin console to review the request and approve or reject it.</p>
@endsection
