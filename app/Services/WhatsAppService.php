<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    public function enabled(): bool
    {
        return config('whatsapp.driver') !== 'off';
    }

    /**
     * Build a click-to-chat URL (no API required).
     * Admin can open this and send from their WhatsApp Business app.
     */
    public function chatUrl(?string $phone, string $message): ?string
    {
        $digits = $this->normalizePhone($phone);
        if (! $digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    public function approvalMessage(string $shopName, string $loginEmail, string $password): string
    {
        $brand = config('whatsapp.from_name');
        $portal = config('whatsapp.portal_login_url');

        return "Hello from {$brand}.\n\n"
            ."Your wholesale partner request for {$shopName} is approved.\n\n"
            ."Login: {$portal}\n"
            ."Email: {$loginEmail}\n"
            ."Password: {$password}\n\n"
            .'Please sign in and change your password after first login.';
    }

    /**
     * Auto-send when Meta (or log) driver is configured.
     * Returns ['ok' => bool, 'via' => string, 'chat_url' => ?string, 'error' => ?string]
     */
    public function notifyApproval(?string $phone, string $shopName, string $loginEmail, string $password): array
    {
        $message = $this->approvalMessage($shopName, $loginEmail, $password);
        $chatUrl = $this->chatUrl($phone, $message);
        $digits = $this->normalizePhone($phone);

        if (! $digits) {
            return [
                'ok' => false,
                'via' => 'none',
                'chat_url' => null,
                'error' => 'Shop has no valid phone number for WhatsApp.',
            ];
        }

        $driver = config('whatsapp.driver', 'log');

        if ($driver === 'off') {
            return [
                'ok' => true,
                'via' => 'manual',
                'chat_url' => $chatUrl,
                'error' => null,
            ];
        }

        try {
            if ($driver === 'meta') {
                $this->sendViaMeta($digits, $message, $shopName, $loginEmail, $password);
            } else {
                Log::info('WhatsApp approval message (log driver)', [
                    'to' => $digits,
                    'message' => $message,
                ]);
            }

            return [
                'ok' => true,
                'via' => $driver,
                'chat_url' => $chatUrl,
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('WhatsApp send failed', [
                'to' => $digits,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'via' => $driver,
                'chat_url' => $chatUrl,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return null;
        }

        // Local BD: 01XXXXXXXXX → 8801XXXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = config('whatsapp.default_country_code').substr($digits, 1);
        }

        // BD without country: 1XXXXXXXXX
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = config('whatsapp.default_country_code').$digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }

    protected function sendViaMeta(string $digits, string $message, string $shopName, string $loginEmail, string $password): void
    {
        $token = config('whatsapp.meta.token');
        $phoneNumberId = config('whatsapp.meta.phone_number_id');
        $version = config('whatsapp.meta.api_version', 'v21.0');
        $template = config('whatsapp.meta.template_name');

        if (! $token || ! $phoneNumberId) {
            throw new \RuntimeException('WhatsApp Meta token / phone_number_id missing in .env');
        }

        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $payload = $template
            ? [
                'messaging_product' => 'whatsapp',
                'to' => $digits,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => config('whatsapp.meta.template_lang', 'en')],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $shopName],
                                ['type' => 'text', 'text' => $loginEmail],
                                ['type' => 'text', 'text' => $password],
                                ['type' => 'text', 'text' => config('whatsapp.portal_login_url')],
                            ],
                        ],
                    ],
                ],
            ]
            : [
                'messaging_product' => 'whatsapp',
                'to' => $digits,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message],
            ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Meta API: '.$response->body());
        }
    }
}
