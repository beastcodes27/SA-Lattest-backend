@extends('emails.layout')

@section('title', 'Payment receipt '.$payment->reference)
@section('preheader', 'Payment received — '.$payment->formatted_amount)
@section('badge', 'Receipt')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">Payment received</h1>

    <p style="margin:0 0 4px;">Hello {{ $organization?->name ?? 'there' }},</p>

    <p style="margin:0 0 4px;">Your payment was received successfully and your subscription is active. Thank you!</p>

    @include('emails.partials.details', ['rows' => array_filter([
        'Receipt reference' => $payment->reference,
        'Plan' => ucfirst($payment->plan).' ('.ucfirst($payment->billing_cycle).')',
        'Payment method' => $payment->provider_name,
        'Transaction ID' => $payment->external_transaction_id,
        'Paid on' => $payment->paid_at?->format('d M Y, H:i'),
    ])])

    @include('emails.partials.panel', [
        'tone' => 'success',
        'title' => 'Total paid',
        'body' => $payment->formatted_amount,
    ])

    <p style="margin:0;font-size:13px;color:#6B7280;">Keep this email for your records. You can also view this payment in the app under subscription billing.</p>
@endsection
