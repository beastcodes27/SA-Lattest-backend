@extends('emails.layout')

@section('title', 'New organization registration')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#200f35;">New registration request</h1>

    <p style="margin:0 0 16px;">A new organization has registered on SmartAttend and is waiting for review.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:0 0 20px;">
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Organization</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $organization->name }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Plan</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ ucfirst($organization->plan) }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Admin</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $admin->name }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Admin email</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $admin->email }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Admin phone</td>
            <td style="padding:4px 0;font-size:13px;font-weight:600;color:#111827;">{{ $admin->phone }}</td>
        </tr>
    </table>

    <p style="margin:0 0 22px;">Review this request in the system admin portal.</p>

    <a href="{{ route('portal.login') }}" style="display:inline-block;background:#200f35;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 20px;border-radius:10px;">
        Open admin portal
    </a>
@endsection
