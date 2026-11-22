<?php

namespace App\Channels;

use App\Models\ErrorIncident;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TelegramChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification): void
    {
        $botToken = $notification->botToken ?? null;
        $chatId = $notification->chatId ?? null;
        $message = $notification->toTelegram($notifiable);

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true,
            ])->throw();
        } catch (\Throwable $e) {
            \Log::error('Telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
