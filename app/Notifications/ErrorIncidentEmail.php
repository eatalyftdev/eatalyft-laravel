<?php

namespace App\Notifications;

use App\Models\ErrorIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ErrorIncidentEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private ErrorIncident $incident,
        private string $severity,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url') . '/admin/monitoring/errors/' . $this->incident->id;

        return (new MailMessage)
            ->subject(sprintf('[%s] EataLyft Error: %s', strtoupper($this->severity), $this->incident->exception_class))
            ->line(sprintf('An error occurred in the %s environment.', app()->environment()))
            ->line(sprintf('Exception: %s', $this->incident->exception_class))
            ->line(sprintf('Message: %s', $this->incident->message))
            ->line(sprintf('File: %s', $this->incident->file ?? 'unknown'))
            ->line(sprintf('Line: %s', $this->incident->line ?? 'unknown'))
            ->line(sprintf('Request: %s %s', $this->incident->request_method ?? 'unknown', $this->incident->request_url ?? 'unknown'))
            ->line(sprintf('Occurrences: %d', $this->incident->occurrence_count))
            ->line(sprintf('First seen: %s', $this->incident->first_seen_at?->format('Y-m-d H:i:s') ?? 'unknown'))
            ->line(sprintf('Last seen: %s', $this->incident->last_seen_at?->format('Y-m-d H:i:s') ?? 'unknown'))
            ->action('View Incident', $url)
            ->line('This is an automated error monitoring notification.');
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
            'file' => $this->incident->file,
            'line' => $this->incident->line,
        ];
    }
}
