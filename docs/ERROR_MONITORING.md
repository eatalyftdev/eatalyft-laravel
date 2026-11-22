# Error Monitoring — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Complete  
**Project:** `D:\Git Projects\eatalyft-laravel`

---

## 1. Overview

The Error Monitoring System provides centralized error capture, incident tracking, and notification for the EataLyft Laravel platform. It automatically detects application failures, captures diagnostic information, stores error incidents in the database, and notifies authorized technical administrators through configured notification channels (email and/or Telegram).

## 2. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Application Exceptions                        │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              bootstrap/app.php (Exception Handler)               │
│  └─ report() callback → ErrorMonitoringService::report()        │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              App\Services\ErrorMonitoringService                 │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐   │
│  │  Severity       │ │  Fingerprint    │ │  Sanitization   │   │
│  │  Classification │ │  Generation     │ & Suppression     │   │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘   │
│  ┌─────────────────┐ ┌─────────────────┐                       │
│  │  Incident Store │ │  Notification   │                       │
│  │  (ErrorIncident)│ │  Dispatcher     │                       │
│  └─────────────────┘ └─────────────────┘                       │
└─────────────────────────────────────────────────────────────────┘
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
    ┌──────────────────┐      ┌──────────────────┐
    │  Email Channel   │      │ Telegram Channel │
    │  (MailMessage)   │      │  (HTTP to Bot API)│
    └──────────────────┘      └──────────────────┘
```

### Key Components

| Component | File | Responsibility |
|-----------|------|----------------|
| Config | `config/error_monitoring.php` | Environment-based configuration |
| Service | `app/Services/ErrorMonitoringService.php` | Core monitoring logic: fingerprinting, dedup, notification |
| Model | `app/Models/ErrorIncident.php` | Incident persistence with status management |
| Migration | `database/migrations/*_create_error_incidents_table.php` | Database schema |
| Email Notification | `app/Notifications/ErrorIncidentEmail.php` | Email alert formatting |
| Telegram Notification | `app/Notifications/ErrorIncidentTelegram.php` | Telegram alert formatting |
| Telegram Channel | `app/Channels/TelegramChannel.php` | Telegram Bot API HTTP client |
| Controller | `app/Http/Controllers/Admin/ErrorMonitoringController.php` | Admin dashboard |
| Views | `resources/views/admin/monitoring/errors/*.blade.php` | Dashboard UI |

### Exception Capture Flow

1. An exception occurs anywhere in the application
2. Laravel's exception handler catches it via `bootstrap/app.php` `withExceptions()` callback
3. The callback invokes `ErrorMonitoringService::report()`
4. The service:
   a. Checks if monitoring is enabled (config + environment filter)
   b. Checks if the exception should be ignored (404s, validation exceptions, etc.)
   c. Checks for recursive reporting (exceptions from within the monitoring system)
   d. Classifies severity (critical for Errors, 5xx; error for everything else)
   e. Generates a SHA-256 fingerprint from class + normalized message + file + line
   f. Sanitizes sensitive information (passwords, tokens, keys, etc.)
   g. Finds or creates an `ErrorIncident` record (incrementing count on duplicates)
   h. Checks suppression interval to avoid duplicate notifications
   i. Dispatches notifications (email, Telegram) if configured

---

## 3. Configuration

### Environment Variables

Add the following to your `.env` file:

| Variable | Default | Description |
|----------|---------|-------------|
| `ERROR_MONITORING_ENABLED` | `true` | Master switch for error monitoring |
| `ERROR_ALERTS_ENABLED` | `false` | Enable email notifications |
| `ERROR_ALERT_EMAIL` | (empty) | Email address for error alerts |
| `ERROR_TELEGRAM_ENABLED` | `false` | Enable Telegram notifications |
| `ERROR_TELEGRAM_BOT_TOKEN` | (empty) | Telegram bot token |
| `ERROR_TELEGRAM_CHAT_ID` | (empty) | Telegram chat ID |
| `ERROR_SUPPRESSION_INTERVAL_MINUTES` | `60` | Minutes between duplicate notifications |
| `ERROR_MIN_NOTIFICATION_SEVERITY` | `error` | Minimum severity to notify (debug, info, warning, error, critical) |
| `ERROR_MONITORING_ENVIRONMENTS` | (empty) | Comma-separated list of environments to monitor (empty = all) |
| `ERROR_SHOW_STACK_TRACES` | `true` | Show stack traces in dashboard |
| `ERROR_QUEUE_NOTIFICATIONS` | `false` | Send notifications via queue |
| `ERROR_PREVENT_RECURSION` | `true` | Prevent recursive error reporting |

### Config File

`config/error_monitoring.php` contains all configuration with sensible defaults. Environment variables override config values.

---

## 4. Incident Severity Levels

| Severity | Description | Examples |
|----------|-------------|----------|
| `critical` | System-level failures | PHP Fatal errors, uncaught Exceptions, 5xx HTTP errors, database connection failures |
| `error` | Application errors | Standard PHP Exceptions, application logic errors |
| `warning` | Non-critical warnings | PHP Warnings (not used by default) |
| `info` | Informational messages | Not used by default |
| `debug` | Debug information | Not used by default |

Only `error` and `critical` severities trigger notifications by default.

---

## 5. Duplicate Suppression

To prevent alert fatigue, the system uses fingerprint-based deduplication:

1. Each exception generates a fingerprint based on: exception class + normalized message + file + line number
2. If the same fingerprint occurs again within the suppression interval (default: 60 minutes), no new notification is sent
3. The occurrence count is incremented and `last_seen_at` is updated
4. After the suppression interval expires, a new notification is sent if the error recurs
5. First occurrence always triggers a notification

### Fingerprint Example

```
SHA256("App\\Exceptions\\ServiceException|connection refused|/app/Service.php|42")
  = e3c728160576213649fdd097cba6592419bc0184081f6778beb4611c1000dceb
```

---

## 6. Sensitive Information Sanitization

The system automatically sanitizes sensitive data from exception messages and stack traces:

### Redacted Keys

| Key Pattern | Replacement |
|-------------|-------------|
| `password` | `[REDACTED]` |
| `api_key` | `[REDACTED]` |
| `secret` | `[REDACTED]` |
| `token` | `[REDACTED]` |
| `authorization` | `[REDACTED]` |
| `private_key` | `[REDACTED]` |
| And many more... | `[REDACTED]` |

### Example

Before: `PDOException: SQLSTATE[HY000]: password=mysecret123 api_key=abc-def-ghi`  
After: `PDOException: SQLSTATE[HY000]: password=[REDACTED] api_key=[REDACTED]`

---

## 7. Admin Error Monitoring Dashboard

### Access

- URL: `/admin/monitoring/errors`
- Permission: `system.audit.read`
- Available to: Superadmin, Admin roles

### Features

| Feature | Description |
|---------|-------------|
| Incident List | Paginated list of all incidents with filtering |
| Status Filter | Filter by open, investigating, resolved, ignored |
| Severity Filter | Filter by debug, info, warning, error, critical |
| Environment Filter | Filter by application environment |
| Search | Search by exception class, message, or fingerprint |
| Incident Details | Full stack trace, request info, occurrence count |
| Status Transitions | Mark as investigating, resolved, or reopen |
| Delete | Remove incidents (with confirmation) |

### Incident Lifecycle

```
open → investigating → resolved
         ↑              ↓
         └────── reopen ────┘
```

---

## 8. Notification Channels

### Email

- Configured via `ERROR_ALERTS_ENABLED=true` and `ERROR_ALERT_EMAIL=admin@example.com`
- Sends HTML email with incident details and link to dashboard
- Can be queued via `ERROR_QUEUE_NOTIFICATIONS=true`

### Telegram

- Configured via `ERROR_TELEGRAM_ENABLED=true`, `ERROR_TELEGRAM_BOT_TOKEN`, `ERROR_TELEGRAM_CHAT_ID`
- Sends MarkdownV2 formatted message with incident summary
- Never includes stack traces or sensitive data

---

## 9. Security

| Security Measure | Implementation |
|------------------|----------------|
| No stack traces in responses | Exceptions never leaked to HTTP responses |
| No `.env` exposure | Environment variables never logged or displayed |
| No sensitive data in notifications | Passwords, tokens, keys redacted |
| Admin-only dashboard | Protected by Spatie `system.audit.read` permission |
| No client-side exposure | Error details only visible to authenticated admins |
| Recursive protection | Monitoring service exceptions don't trigger re-reporting |
| Fallback to logging | If notification fails, error is logged via Laravel's logger |

---

## 10. Testing

Run the test suite:

```bash
php artisan test --filter="ErrorMonitoring"
```

Tests cover:
- Severity classification (critical, error)
- Fingerprint generation (deterministic, different for different messages)
- Sensitive data sanitization
- Incident storage and retrieval
- Duplicate fingerprint incrementing
- Notification suppression within interval
- Notification after suppression interval
- Guest access denial to dashboard
- Admin dashboard access
- Incident resolution and reopening
- Admin login rate limiting

---

## 11. Manual Verification Steps

1. **Configure environment**: Set `ERROR_ALERTS_ENABLED=true` and `ERROR_ALERT_EMAIL=your-email@example.com` in `.env`
2. **Trigger an error**: Visit a non-existent route or cause a 500 error
3. **Check dashboard**: Visit `/admin/monitoring/errors` as an admin user
4. **Verify incident**: Confirm the error appears with correct severity, fingerprint, and count
5. **Test notification**: Check your email for the alert
6. **Test resolution**: Mark the incident as resolved and verify status change
7. **Test deduplication**: Trigger the same error again and verify no duplicate notification within suppression interval

---

*This document is part of the EataLyft platform documentation. It is preserved alongside Phase 00–04 documents.*