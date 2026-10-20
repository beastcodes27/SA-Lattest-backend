@php
    $theme = config('mail_theme');
    $colors = $theme['colors'];
    $tone = $tone ?? 'primary';
    $styles = match ($tone) {
        'danger' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'border' => '#FECACA'],
        'ghost' => ['bg' => '#FFFFFF', 'text' => $colors['ink'], 'border' => $colors['border']],
        default => ['bg' => $colors['accent'], 'text' => $colors['accent_text'], 'border' => $colors['accent']],
    };
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 8px;">
    <tr>
        <td align="center" style="border-radius:10px;background:{{ $styles['bg'] }};border:1px solid {{ $styles['border'] }};">
            <a href="{{ $url }}" target="_blank" rel="noopener" style="display:inline-block;padding:12px 22px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;line-height:1.2;color:{{ $styles['text'] }};text-decoration:none;border-radius:10px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
