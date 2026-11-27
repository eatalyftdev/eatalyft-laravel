<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected string $driver;
    protected string $apiUrl;
    protected string $token;
    protected string $phoneNumberId;

    public function __construct()
    {
        $this->driver = config('services.whatsapp.driver', 'meta');
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->token = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
    }

    public function sendTemplate(string $to, string $templateName, array $params = []): bool
    {
        $to = $this->formatPhone($to);

        if ($this->driver === 'waha') {
            return $this->sendViaWaha($to, $templateName, $params);
        }

        return $this->sendViaMeta($to, $templateName, $params);
    }

    protected function sendViaMeta(string $to, string $templateName, array $params): bool
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::channel('otp')->warning('WhatsApp not configured, skipping send');
            return false;
        }

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => $p], $params),
            ];
        }

        $response = Http::withToken($this->token)->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
                'components' => $components,
            ],
        ]);

        return $response->successful();
    }

    protected function sendViaWaha(string $to, string $templateName, array $params): bool
    {
        $wahaUrl = config('services.whatsapp.waha_url', 'http://localhost:3000');
        $response = Http::post("{$wahaUrl}/api/sendText", [
            'chatId' => $to . '@c.us',
            'text' => $templateName . ': ' . implode(', ', $params),
            'session' => 'default',
        ]);

        return $response->successful();
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }
        return ltrim($phone, '+');
    }
}