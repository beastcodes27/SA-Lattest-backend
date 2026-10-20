@php
    $theme = config('mail_theme');
    $colors = $theme['colors'];
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0;background:{{ $colors['ink'] }};border-radius:14px;">
    <tr>
        <td align="center" style="padding:24px 20px;">
            @if (! empty($label))
                <div style="margin-bottom:8px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:{{ $colors['accent'] }};">{{ $label }}</div>
            @endif
            <div style="font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:700;letter-spacing:12px;color:#FFFFFF;line-height:1.2;">{{ $code }}</div>
            @if (! empty($expiresInMinutes))
                <div style="margin-top:10px;font-size:12px;color:#C8BFDD;">Expires in {{ $expiresInMinutes }} minutes</div>
            @endif
        </td>
    </tr>
</table>
