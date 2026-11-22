<?php

namespace App\Notifications;

use App\Models\ErrorIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ErrorIncidentTelegram extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private ErrorIncident $incident,
        private string $severity,
        private string $botToken,
        private string $chatId,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): string
    {
        $env = app()->environment();
        $shortId = substr($this->incident->uuid, 0, 8);

        $message = sprintf(
            "🚨 *EataLyft Error Alert*\n\n" .
            "*Environment:* `%s`\n" .
            "*Severity:* `%s`\n" .
            "*Exception:* `%s`\n" .
            "*Message:* `%s`\n" .
            "*File:* `%s`\n" .
            "*Line:* `%s`\n" .
            "*Request:* `%s %s`\n" .
            "*Occurrences:* `%d`\n" .
            "*First seen:* `%s`\n" .
            "*Last seen:* `%s`\n" .
            "*Incident ID:* `%s`",
            $env,
            strtoupper($this->severity),
            $this->escapeMarkdown($this->incident->exception_class),
            $this->escapeMarkdown($this->incident->message),
            $this->escapeMarkdown($this->incident->file ?? 'unknown'),
            $this->incident->line ?? 'unknown',
            $this->escapeMarkdown($this->incident->request_method ?? 'unknown'),
            $this->escapeMarkdown($this->incident->request_url ?? 'unknown'),
            $this->incident->occurrence_count,
            $this->incident->first_seen_at?->format('Y-m-d H:i:s') ?? 'unknown',
            $this->incident->last_seen_at?->format('Y-m-d H:i:s') ?? 'unknown',
            $shortId
        );

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'uuid' => $this->incident->uuid,
            'severity' => $this->severity,
            'exception_class' => $this->incident->exception_class,
            'message' => $this->incident->message,
        ];
    }

    /**
     * Escape Markdown special characters.
     */
    private function escapeMarkdown(string $text): string
    {
        return preg_replace('/([_*\[\]()~`>#+\-=|{}.!\\\\])/', '\\\$1', $text);
    }
}
