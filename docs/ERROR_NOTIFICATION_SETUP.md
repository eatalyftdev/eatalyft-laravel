# Error Notification Setup — EataLyft Technologies

**Version:** 1.0  
**Date:** 2026-11-22  
**Status:** Complete  
**Project:** `D:\Git Projects\eatalyft-laravel`

---

## 1. Overview

The Error Notification System sends alerts when critical application errors occur. Two notification channels are supported: Email and Telegram. Both are disabled by default and must be explicitly enabled via environment variables.

## 2. Email Notification Setup

### Configuration

Add the following to your `.env` file:

```env
ERROR_ALERTS_ENABLED=true
ERROR_ALERT_EMAIL=your-technical-email@example.com
```

### How It Works

1. When an error incident is created or updated (after suppression interval), an email notification is dispatched
2. The email includes: exception class, message, file, line, request URL, occurrence count, timestamps
3. A link to the incident dashboard is included for quick access
4. Can be queued via `ERROR_QUEUE_NOTIFICATIONS=true` for asynchronous delivery

### Example Email

```
Subject: [ERROR] EataLyft Error: PDOException

An error occurred in the local environment.

Exception: PDOException
Message: SQLSTATE[HY000]: Connection refused
File: /var/www/html/app/Services/DatabaseService.php
Line: 42
Request: GET /api/v1/bus/trips
Occurrences: 3
First seen: 2026-11-22 14:30:00
Last seen: 2026-11-22 14:45:00

[View Incident]
```

### Testing Email Notifications

1. Set `MAIL_MAILER=log` in `.env` to capture emails in `storage/logs/laravel.log`
2. Trigger an error in the application
3. Check `storage/logs/laravel.log` for the email content
4. For real email delivery, configure SMTP settings in `.env`:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=your-username
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=tls
   ```

---

## 3. Telegram Notification Setup

### Configuration

Add the following to your `.env` file:

```env
ERROR_TELEGRAM_ENABLED=true
ERROR_TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
ERROR_TELEGRAM_CHAT_ID=123456789
```

### How to Create a Telegram Bot

1. Open Telegram and search for **@BotFather**
2. Send `/newbot` and follow the prompts to create a new bot
3. BotFather will provide you with a bot token (e.g., `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)
4. Add the bot to a group or start a private chat with it
5. Get your chat ID by sending `/start` to the bot, then visiting:
   `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
6. Look for `"chat":{"id":123456789,...}` in the response - this is your chat ID

### How It Works

1. When an error incident is created or updated (after suppression interval), a Telegram message is dispatched
2. The message includes: environment, severity, exception, message, file, line, request, occurrences, timestamps
3. Messages use MarkdownV2 formatting for readability
4. No stack traces or sensitive data are included in Telegram messages

### Example Telegram Message

```
🚨 *EataLyft Error Alert*

*Environment:* `local`
*Severity:* `ERROR`
*Exception:* `PDOException`
*Message:* `SQLSTATE[HY000]: Connection refused`
*File:* `/var/www/html/app/Database.php`
*Line:* `42`
*Request:* `GET /api/v1/bus/trips`
*Occurrences:* `3`
*First seen:* `2026-11-22 14:30:00`
*Last seen:* `2026-11-22 14:45:00`
*Incident ID:* `a1b2c3d4`
```

### Testing Telegram Notifications

1. Set `ERROR_TELEGRAM_ENABLED=true` in `.env`
2. Set your bot token and chat ID
3. Trigger an error in the application
4. Check your Telegram chat for the alert message

---

## 4. Configuration Reference

| Variable | Default | Required | Description |
|----------|---------|----------|-------------|
| `ERROR_MONITORING_ENABLED` | `true` | No | Master switch for error monitoring |
| `ERROR_ALERTS_ENABLED` | `false` | No | Enable email notifications |
| `ERROR_ALERT_EMAIL` | (empty) | Yes (if email enabled) | Recipient email address |
| `ERROR_TELEGRAM_ENABLED` | `false` | No | Enable Telegram notifications |
| `ERROR_TELEGRAM_BOT_TOKEN` | (empty) | Yes (if Telegram enabled) | Telegram bot token |
| `ERROR_TELEGRAM_CHAT_ID` | (empty) | Yes (if Telegram enabled) | Telegram chat ID |
| `ERROR_SUPPRESSION_INTERVAL_MINUTES` | `60` | No | Minutes between duplicate notifications |
| `ERROR_MIN_NOTIFICATION_SEVERITY` | `error` | No | Minimum severity for notifications |
| `ERROR_MONITORING_ENVIRONMENTS` | (empty) | No | Comma-separated environments to monitor |
| `ERROR_SHOW_STACK_TRACES` | `true` | No | Show stack traces in dashboard |
| `ERROR_QUEUE_NOTIFICATIONS` | `false` | No | Queue notifications asynchronously |
| `ERROR_PREVENT_RECURSION` | `true` | No | Prevent recursive error reporting |

---

## 5. Security Considerations

- **Never commit real credentials** to version control
- **Use environment variables** for all sensitive configuration
- **Telegram messages** never include stack traces or sensitive user data
- **Email messages** include incident details but sanitize sensitive fields
- **Dashboard access** is restricted to administrators with `system.audit.read` permission
- **Production deployments** should use separate credentials from development

---

## 6. Troubleshooting

| Issue | Solution |
|-------|----------|
| No email received | Check `ERROR_ALERTS_ENABLED=true` and `ERROR_ALERT_EMAIL` is set; check `MAIL_MAILER` configuration |
| No Telegram message | Check `ERROR_TELEGRAM_ENABLED=true` and bot token/chat ID are correct; verify bot can send messages to the chat |
| Notifications queued but not sent | Run `php artisan queue:work` to process the queue |
| Duplicate notifications | Check `ERROR_SUPPRESSION_INTERVAL_MINUTES` - increase if too frequent |
| Stack traces not visible | Check `ERROR_SHOW_STACK_TRACES=true` and user has admin permissions |
| Dashboard shows 403 | User doesn't have `system.audit.read` permission; assign admin or superadmin role |

---

*This document is part of the EataLyft platform documentation. It is preserved alongside Phase 00–04 documents.*