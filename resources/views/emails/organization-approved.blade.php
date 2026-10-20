@extends('emails.layout')

@section('title', $organization->name.' has been approved')
@section('preheader', 'Your organization is active on '.config('mail_theme.brand_name'))
@section('badge', 'Account approved')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Your organization has been approved</h1>

    <p style="margin:0 0 4px;">Hello {{ $organization->name }} team,</p>

    <p style="margin:0 0 4px;">
        Your {{ config('mail_theme.brand_name') }} registration has been approved. You can now sign in with the employee ID and password you registered with, then start adding branches and employees.
    </p>

    @include('emails.partials.details', ['rows' => array_filter([
        'Organization' => $organization->name,
        'Plan' => ucfirst($organization->plan),
        'Trial ends' => $organization->trial_ends_at?->format('d M Y'),
    ])])

    @include('emails.partials.panel', [
        'tone' => 'success',
        'title' => 'What happens next',
        'body' => 'Add your branches, invite employees, and set your attendance rules. Your team can start checking in right away.',
    ])

    @include('emails.partials.button', ['url' => config('app.url'), 'label' => 'Open '.config('mail_theme.brand_name')])
@endsection
