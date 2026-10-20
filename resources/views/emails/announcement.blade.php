@extends('emails.layout')

@section('title', $title)
@section('preheader', $title)
@section('badge', 'Announcement')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">{{ $title }}</h1>

    @if (! empty($recipientName))
        <p style="margin:0 0 4px;">Hello {{ $recipientName }},</p>
    @endif

    <div style="margin:0 0 4px;">{!! nl2br(e($body)) !!}</div>

    @if (! empty($actionUrl) && ! empty($actionLabel))
        @include('emails.partials.button', ['url' => $actionUrl, 'label' => $actionLabel])
    @endif
@endsection
