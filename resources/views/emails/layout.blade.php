<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', 'Notification')</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, Helvetica, sans-serif !important; }
        .email-container { width: 600px !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        body { margin: 0; padding: 0; width: 100% !important; }
        img { border: 0; outline: none; text-decoration: none; }
        a { text-decoration: none; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .email-header { padding: 20px 22px !important; }
            .email-body { padding: 26px 22px !important; }
            .email-footer { padding: 18px 22px !important; }
            .stack { display: block !important; width: 100% !important; text-align: left !important; padding: 3px 0 !important; }
            .stack-right { text-align: left !important; }
        }
    </style>
</head>
@php
    $theme = config('mail_theme');
    $colors = $theme['colors'];
@endphp
<body style="margin:0;padding:0;background:{{ $colors['page'] }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{{ $colors['text'] }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        @yield('preheader', $theme['tagline'])
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $colors['page'] }};">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:{{ $colors['surface'] }};border-radius:14px;overflow:hidden;border:1px solid {{ $colors['border'] }};">
                    <tr>
                        <td style="height:4px;background:{{ $colors['accent'] }};font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="email-header" style="background:{{ $colors['ink'] }};padding:22px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="stack" style="vertical-align:middle;">
                                        @if (! empty($theme['brand_name']))
                                            <div style="font-size:19px;font-weight:700;letter-spacing:-0.3px;color:#FFFFFF;">{{ $theme['brand_name'] }}</div>
                                            @if (! empty($theme['tagline']))
                                                <div style="margin-top:3px;font-size:12px;font-weight:500;color:#C8BFDD;">{{ $theme['tagline'] }}</div>
                                            @endif
                                        @else
                                            <div style="font-size:17px;font-weight:700;letter-spacing:-0.3px;color:#FFFFFF;">@yield('badge', 'Notification')</div>
                                        @endif
                                    </td>
                                    <td class="stack stack-right" align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;border-radius:999px;background:rgba(255,255,255,0.12);padding:6px 12px;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:{{ $colors['accent'] }};">@yield('badge', 'Notification')</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-body" style="background:{{ $colors['surface'] }};padding:32px;font-size:15px;line-height:1.7;color:{{ $colors['text'] }};">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="email-footer" style="background:{{ $colors['surface_muted'] }};padding:20px 32px;border-top:1px solid {{ $colors['border'] }};font-size:12px;line-height:1.6;color:{{ $colors['muted'] }};">
                            @if (! empty($theme['brand_name']))
                                <p style="margin:0 0 6px;font-weight:600;color:{{ $colors['ink'] }};">{{ $theme['brand_name'] }}</p>
                            @endif
                            <p style="margin:0 0 6px;">{{ $theme['footer_note'] }}</p>
                            @if (! empty($theme['support_email']))
                                <p style="margin:0 0 6px;">Need help? Contact <a href="mailto:{{ $theme['support_email'] }}" style="color:{{ $colors['ink'] }};font-weight:600;text-decoration:underline;">{{ $theme['support_email'] }}</a>.</p>
                            @endif
                            <p style="margin:0;">&copy; {{ date('Y') }}{{ ! empty($theme['brand_name']) ? ' '.$theme['brand_name'] : '' }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
