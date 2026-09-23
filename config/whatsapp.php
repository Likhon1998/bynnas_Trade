<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp acknowledgement
    |--------------------------------------------------------------------------
    |
    | driver:
    | - log   → write message to log (local / no API yet)
    | - meta  → Meta WhatsApp Cloud API
    | - off   → disable auto-send (manual wa.me button still works)
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'log'),

    'from_name' => env('WHATSAPP_FROM_NAME', env('APP_NAME', 'Bynnas Trade')),

    'portal_login_url' => env('WHATSAPP_PORTAL_URL', env('APP_URL').'/portal/login'),

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY', '880'),

    'meta' => [
        'token' => env('WHATSAPP_META_TOKEN'),
        'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_META_API_VERSION', 'v21.0'),
        // Optional approved template for business-initiated chats
        'template_name' => env('WHATSAPP_META_TEMPLATE'),
        'template_lang' => env('WHATSAPP_META_TEMPLATE_LANG', 'en'),
    ],
];
