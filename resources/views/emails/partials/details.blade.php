@php
    $theme = config('mail_theme');
    $colors = $theme['colors'];
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;background:{{ $colors['surface_muted'] }};border:1px solid {{ $colors['border'] }};border-radius:12px;">
    @foreach ($rows as $label => $value)
        <tr>
            <td style="padding:7px 16px;font-size:13px;line-height:1.5;color:{{ $colors['muted'] }};{{ $loop->first ? 'padding-top:14px;' : '' }}">{{ $label }}</td>
            <td align="right" style="padding:7px 16px;font-size:13px;line-height:1.5;font-weight:600;color:{{ $colors['ink'] }};{{ $loop->first ? 'padding-top:14px;' : '' }};{{ $loop->last ? 'padding-bottom:14px;' : '' }}">{{ $value }}</td>
        </tr>
    @endforeach
</table>
