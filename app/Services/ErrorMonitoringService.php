<?php

namespace App\Services;

use App\Models\ErrorIncident;
use App\Notifications\ErrorIncidentEmail;
use App\Notifications\ErrorIncidentTelegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Centralized error monitoring service.
 *
 * Responsibilities:
 *   - Accept captured exceptions
 *   - Classify severity
 *   - Generate unique incident fingerprint
 *   - Sanitize sensitive information
 *   - Store incident information
 *   - Dispatch notifications
 *   - Suppress duplicate alerts within configurable interval
 *   - Record notification delivery status
 *
 * The service never throws exceptions itself — all failures are logged
 * and swallowed so the error reporting mechanism cannot crash the app
 * or trigger recursive reporting.
 */
class ErrorMonitoringService
{
    /**
     * Severity levels in order of importance.
     */
    private const SEVERITY_LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4,
    ];

    /**
     * Sensitive keys that should be sanitized from messages.
     */
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey',
        'access_token', 'refresh_token', 'authorization', 'cookie', 'session',
        'private_key', 'secret_key', 'encryption_key', 'signing_key',
        'db_password', 'database_password', 'mail_password', 'smtp_password',
        'ftp_password', 'aws_secret', 'stripe_key', 'paypal_secret',
    ];

    /**
     * Exception classes that should never be reported (expected failures).
     */
    private const IGNORED_EXCEPTION_CLASSES = [
        'Illuminate\\Auth\\AuthenticationException',
        'Illuminate\\Validation\\ValidationException',
        'Illuminate\\Session\\TokenMismatchException',
        'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException',
        'Symfony\\Component\\HttpKernel\\Exception\\MethodNotAllowedHttpException',
    ];

    public function __construct(
        private array $config = []
    ) {
    }

    /**
     * Report an exception to the monitoring system.
     *
     * This is the main entry point. It never throws — all errors are
     * caught and logged internally.
     */
    public function report(Throwable $exception, array $context = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldIgnore($exception)) {
            return;
        }

        if ($this->isRecursive($exception)) {
            Log::warning('Error monitoring: recursive report detected, skipping', [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            return;
        }

        try {
            $severity = $this->classifySeverity($exception);
            $fingerprint = $this->generateFingerprint($exception);
            $sanitizedMessage = $this->sanitize($exception->getMessage());
            $sanitizedTrace = $this->sanitizeStackTrace($exception->getTraceAsString());

            $incident = $this->findOrCreateIncident(
                $fingerprint,
                $severity,
                $exception,
                $sanitizedMessage,
                $sanitizedTrace,
                $context
            );

            if ($this->shouldNotify($incident, $severity)) {
                $this->dispatchNotifications($incident, $severity);
            }
        } catch (Throwable $e) {
            // Never let error monitoring crash the application
            Log::error('Error monitoring service failure', [
                'original_class' => get_class($exception),
                'original_message' => $exception->getMessage(),
                'monitor_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if monitoring is enabled.
     */
    public function isEnabled(): bool
    {
        if (! empty($this->config['enabled']) && $this->config['enabled'] === false) {
            return false;
        }

        $environments = $this->config['environments'] ?? null;
        if (! empty($environments)) {
            $envs = is_string($environments) ? explode(',', $environments) : $environments;
            if (! in_array(app()->environment(), $envs)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the exception should be ignored.
     */
    private function shouldIgnore(Throwable $exception): bool
    {
        $class = get_class($exception);

        foreach (self::IGNORED_EXCEPTION_CLASSES as $ignored) {
            if ($class === $ignored || is_subclass_of($class, $ignored)) {
                return true;
            }
        }

        // Ignore 404s and other expected HTTP exceptions
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode >= 400 && $statusCode < 500) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this exception originated from within the monitoring system.
     */
    private function isRecursive(Throwable $exception): bool
    {
        if (! $this->config['prevent_recursion'] ?? true) {
            return false;
        }

        $trace = $exception->getTraceAsString();
        return str_contains($trace, 'ErrorMonitoringService') ||
            str_contains($trace, 'ErrorIncident');
    }

    /**
     * Classify the severity of an exception.
     */
    private function classifySeverity(Throwable $exception): string
    {
        $class = get_class($exception);

        // Fatal errors and uncaught exceptions are critical
        if ($exception instanceof \Error) {
            return 'critical';
        }

        // Symfony HttpException with 5xx status
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $status = $exception->getStatusCode();
            if ($status >= 500) {
                return 'critical';
            }
        }

        // Database connection errors
        if (str_contains($class, 'PDOException') ||
            str_contains($class, 'QueryException')) {
            return 'critical';
        }

        // Everything else is an error
        return 'error';
    }

    /**
     * Generate a stable fingerprint for the exception.
     *
     * The fingerprint groups identical errors together so that repeated
     * occurrences update an existing incident rather than creating new ones.
     */
    private function generateFingerprint(Throwable $exception): string
    {
        $class = get_class($exception);
        $message = $this->normalizeMessage($exception->getMessage());
        $file = $exception->getFile() ?: 'unknown';
        $line = $exception->getLine();

        $raw = sprintf('%s|%s|%s|%d', $class, $message, $file, $line);

        return hash('sha256', $raw);
    }

    /**
     * Normalize a message by removing variable data (numbers, IDs, etc.).
     */
    private function normalizeMessage(string $message): string
    {
        // Replace numbers with placeholder
        $message = preg_replace('/\b\d+\b/', 'N', $message);
        // Replace UUIDs
        $message = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', 'UUID', $message);
        // Replace email addresses
        $message = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', 'EMAIL', $message);
        // Replace IP addresses
        $message = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', 'IP', $message);

        return trim($message);
    }

    /**
     * Sanitize sensitive information from a message.
     */
    public function sanitize(string $message): string
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            // Match key=value or key:"value" or key: value patterns
            $message = preg_replace(
                '/(' . $key . ')\s*[:=]\s*["\']?[^"\'\s,}]+["\']?/i',
                '$1=[REDACTED]',
                $message
            );
        }

        return $message;
    }

    /**
     * Sanitize a stack trace.
     */
    private function sanitizeStackTrace(string $trace): string
    {
        return $this->sanitize($trace);
    }

    /**
     * Find an existing incident by fingerprint or create a new one.
     */
    private function findOrCreateIncident(
        string $fingerprint,
        string $severity,
        Throwable $exception,
        string $sanitizedMessage,
        string $sanitizedTrace,
        array $context
    ): ErrorIncident {
        $incident = ErrorIncident::where('fingerprint', $fingerprint)->first();

        if ($incident) {
            $incident->recordOccurrence();

            // Update severity if current is higher
            $currentLevel = self::SEVERITY_LEVELS[$incident->severity] ?? 0;
            $newLevel = self::SEVERITY_LEVELS[$severity] ?? 0;
            if ($newLevel > $currentLevel) {
                $incident->update(['severity' => $severity]);
            }

            return $incident;
        }

        return ErrorIncident::create([
            'fingerprint' => $fingerprint,
            'severity' => $severity,
            'exception_class' => get_class($exception),
            'message' => $sanitizedMessage,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $sanitizedTrace,
            'environment' => app()->environment(),
            'request_method' => $context['request_method'] ?? null,
            'request_url' => $context['request_url'] ?? null,
            'route_name' => $context['route_name'] ?? null,
            'http_status' => $context['http_status'] ?? null,
            'request_id' => $context['request_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'status' => 'open',
        ]);
    }

    /**
     * Determine if a notification should be sent for this incident.
     */
    private function shouldNotify(ErrorIncident $incident, string $severity): bool
    {
        // Check minimum severity
        $minSeverity = $this->config['min_notification_severity'] ?? 'error';
        $minLevel = self::SEVERITY_LEVELS[$minSeverity] ?? 3;
        $currentLevel = self::SEVERITY_LEVELS[$severity] ?? 3;

        if ($currentLevel < $minLevel) {
            return false;
        }

        // If this is a new incident (occurrence_count === 1), always notify
        if ($incident->occurrence_count === 1) {
            return true;
        }

        // For repeat occurrences, check suppression interval
        $suppressionMinutes = $this->config['suppression_interval_minutes'] ?? 60;
        $lastNotification = $incident->last_seen_at;

        if (!$lastNotification) {
            return true;
        }

        return $lastNotification->addMinutes($suppressionMinutes)->isPast();
    }

    /**
     * Dispatch notifications for an incident.
     */
    private function dispatchNotifications(ErrorIncident $incident, string $severity): void
    {
        // Email notifications
        if ($this->config['email']['enabled'] ?? false) {
            $this->dispatchEmailNotification($incident, $severity);
        }

        // Telegram notifications
        if ($this->config['telegram']['enabled'] ?? false) {
            $this->dispatchTelegramNotification($incident, $severity);
        }
    }

    /**
     * Dispatch email notification.
     */
    private function dispatchEmailNotification(ErrorIncident $incident, string $severity): void
    {
        $recipient = $this->config['email']['recipient'] ?? null;

        if (empty($recipient)) {
            Log::warning('Error monitoring: email enabled but no recipient configured');
            return;
        }

        try {
            if ($this->config['queue_notifications'] ?? false) {
                Notification::route('mail', $recipient)
                    ->queue(new ErrorIncidentEmail($incident, $severity));
            } else {
                Notification::route('mail', $recipient)
                    ->send(new ErrorIncidentEmail($incident, $severity));
            }
        } catch (Throwable $e) {
            Log::error('Error monitoring: email notification failed', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch Telegram notification.
     */
    private function dispatchTelegramNotification(ErrorIncident $incident, string $severity): void
    {
        $botToken = $this->config['telegram']['bot_token'] ?? null;
        $chatId = $this->config['telegram']['chat_id'] ?? null;

        if (empty($botToken) || empty($chatId)) {
            Log::warning('Error monitoring: telegram enabled but bot_token or chat_id not configured');
            return;
        }

        try {
            if ($this->config['queue_notifications'] ?? false) {
                Notification::route('telegram', $chatId)
                    ->queue(new ErrorIncidentTelegram($incident, $severity, $botToken));
            } else {
                Notification::route('telegram', $chatId)
                    ->send(new ErrorIncidentTelegram($incident, $severity, $botToken));
            }
        } catch (Throwable $e) {
            Log::error('Error monitoring: telegram notification failed', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
