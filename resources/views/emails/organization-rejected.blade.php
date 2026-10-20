@extends('emails.layout')

@section('title', 'Registration update')
@section('preheader', 'An update on your '.$organization->name.' registration')
@section('badge', 'Registration update')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Registration update</h1>

    <p style="margin:0 0 4px;">Hello {{ $organization->name }} team,</p>

    <p style="margin:0 0 4px;">Your registration request for {{ $organization->name }} was not approved at this time.</p>

    @if ($reason)
        @include('emails.partials.panel', [
            'tone' => 'danger',
            'title' => 'Reason',
            'body' => $reason,
        ])
    @endif

    <p style="margin:0;">If you believe this was a mistake or need help resolving it, contact our support team and we will be happy to assist.</p>

    @if (! empty(config('mail_theme.support_email')))
        @include('emails.partials.button', [
            'url' => 'mailto:'.config('mail_theme.support_email'),
            'label' => 'Contact support',
            'tone' => 'ghost',
        ])
    @endif
@endsection
