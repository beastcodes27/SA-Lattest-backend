@extends('emails.layout')

@section('title', 'Registration update')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#200f35;">Registration update</h1>

    <p style="margin:0 0 16px;">Hello {{ $organization->name }} team,</p>

    <p style="margin:0 0 16px;">Your registration request for {{ $organization->name }} was not approved at this time.</p>

    @if ($reason)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;margin:0 0 20px;">
            <tr>
                <td style="font-size:13px;line-height:1.6;color:#991b1b;">
                    <strong>Reason:</strong> {{ $reason }}
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0;">If you believe this was a mistake or need help, please contact SmartAttend support and we will be happy to assist.</p>
@endsection
