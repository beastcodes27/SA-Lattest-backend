@extends('emails.layout')

@section('title', 'Payment receipt')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#200f35;">Payment receipt</h1>

    <p style="margin:0 0 16px;">Hello {{ $organization?->name ?? 'SmartAttend user' }},</p>

    <p style="margin:0 0 20px;">Your payment was received successfully and your subscription is now active. Thank you!</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:0 0 20px;">
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Receipt reference</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $payment->reference }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Plan</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ ucfirst($payment->plan) }} ({{ ucfirst($payment->billing_cycle) }})</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Payment method</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $payment->provider_name }}</td>
        </tr>
        @if ($payment->external_transaction_id)
            <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Transaction ID</td>
                <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $payment->external_transaction_id }}</td>
            </tr>
        @endif
        @if ($payment->paid_at)
            <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Paid on</td>
                <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;text-align:right;">{{ $payment->paid_at->format('d M Y, H:i') }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 0 4px;font-size:15px;font-weight:700;color:#200f35;border-top:1px solid #e5e7eb;">Total paid</td>
            <td style="padding:12px 0 4px;font-size:15px;font-weight:700;color:#200f35;text-align:right;border-top:1px solid #e5e7eb;">{{ $payment->formatted_amount }}</td>
        </tr>
    </table>

    <p style="margin:0;color:#6b7280;font-size:13px;">Keep this email for your records. You can also view this payment in the app under subscription billing.</p>
@endsection
