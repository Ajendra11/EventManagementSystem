<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

/**
 * Send a plain-text email.
 *
 * In development (MAIL_DRIVER = 'log'), writes to logs/mail.log.
 * In production  (MAIL_DRIVER = 'smtp'), uses PHPMailer via Composer.
 *
 * Sprint 1: email is used for log-driver only (no SMTP needed for Sprint 1).
 * Sprint 2 will add email verification, password reset, and booking confirmation emails.
 */
function send_email(string $to, string $subject, string $body): bool
{
    if (MAIL_DRIVER === 'smtp') {
        return _send_smtp($to, $subject, $body);
    }
    return _send_log($to, $subject, $body);
}

// ── Log driver (development) ─────────────────────────────────────────────────

function _send_log(string $to, string $subject, string $body): bool
{
    $dir = dirname(MAIL_LOG);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $entry = implode("\n", [
        str_repeat('─', 60),
        '[' . date('Y-m-d H:i:s') . '] TO: ' . $to,
        'SUBJECT: ' . $subject,
        '',
        $body,
        '',
    ]);
    return (bool) @file_put_contents(MAIL_LOG, $entry, FILE_APPEND | LOCK_EX);
}

// ── SMTP driver (production via PHPMailer) ───────────────────────────────────

function _send_smtp(string $to, string $subject, string $body): bool
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        log_app_error('PHPMailer not installed. Run: composer install', __FILE__, __LINE__);
        return _send_log($to, $subject, $body);
    }

    require_once $autoload;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (\Exception $e) {
        log_app_error('PHPMailer: ' . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}
