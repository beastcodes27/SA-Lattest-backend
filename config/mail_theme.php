<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Theme
    |--------------------------------------------------------------------------
    |
    | Branding used by every transactional email template. Keep the palette
    | limited to colours that render consistently across email clients.
    |
    */

    'brand_name' => env('MAIL_BRAND_NAME', ''),

    'tagline' => env('MAIL_TAGLINE', ''),

    'support_email' => env('MAIL_SUPPORT_EMAIL', 'help@smartattend.com'),

    'footer_note' => env('MAIL_FOOTER_NOTE', 'This is an automated message. Please do not reply to this email.'),

    'colors' => [
        'ink' => env('MAIL_THEME_INK', '#200F35'),
        'accent' => env('MAIL_THEME_ACCENT', '#10B981'),
        'accent_text' => env('MAIL_THEME_ACCENT_TEXT', '#04291D'),
        'page' => env('MAIL_THEME_PAGE', '#F3F4F6'),
        'surface' => env('MAIL_THEME_SURFACE', '#FFFFFF'),
        'surface_muted' => env('MAIL_THEME_SURFACE_MUTED', '#F9FAFB'),
        'border' => env('MAIL_THEME_BORDER', '#E5E7EB'),
        'text' => env('MAIL_THEME_TEXT', '#374151'),
        'muted' => env('MAIL_THEME_MUTED', '#6B7280'),
    ],

];
