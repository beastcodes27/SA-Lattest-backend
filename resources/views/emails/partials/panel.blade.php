@php
    $theme = config('mail_theme');
    $colors = $theme['colors'];
    $tone = $tone ?? 'neutral';
    $palette = match ($tone) {
        'success' => ['bg' => '#ECFDF5', 'border' => '#A7F3D0', 'title' => '#047857', 'text' => '#065F46'],
        'warning' => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'title' => '#B45309', 'text' => '#92400E'],
        'danger' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'title' => '#B91C1C', 'text' => '#991B1B'],
        'info' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'title' => '#1D4ED8', 'text' => '#1E40AF'],
        default => ['bg' => $colors['surface_muted'], 'border' => $colors['border'], 'title' => $colors['muted'], 'text' => $colors['text']],
    };
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;background:{{ $palette['bg'] }};border:1px solid {{ $palette['border'] }};border-radius:12px;">
    <tr>
        <td style="padding:16px 18px;font-size:14px;line-height:1.7;color:{{ $palette['text'] }};">
            @if (! empty($title))
                <div style="margin-bottom:6px;font-size:11px;font-weight:800;letter-spacing:0.6px;text-transform:uppercase;color:{{ $palette['title'] }};">{{ $title }}</div>
            @endif
            {!! nl2br(e($body ?? '')) !!}
        </td>
    </tr>
</table>
