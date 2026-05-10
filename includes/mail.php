<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Send an email through the configured driver.
 *
 * @param string $to Recipient email address.
 * @param string $subject Message subject.
 * @param string $body Plain text message body.
 * @param array<int,string> $attachments Absolute paths to files that should be attached.
 */
function send_email(string $to, string $subject, string $body, array $attachments = []): bool
{
    if (MAIL_DRIVER === 'smtp') {
        return _send_smtp($to, $subject, $body, $attachments);
    }
    return _send_log($to, $subject, $body, $attachments);
}

/**
 * Development log mail driver. Does not require network access.
 *
 * @param array<int,string> $attachments
 */
function _send_log(string $to, string $subject, string $body, array $attachments = []): bool
{
    $dir = dirname(MAIL_LOG);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $entry = implode("\n", [
        str_repeat('─', 60),
        '[' . date('Y-m-d H:i:s') . '] TO: ' . $to,
        'SUBJECT: ' . $subject,
        'ATTACHMENTS: ' . ($attachments ? implode(', ', $attachments) : 'None'),
        '',
        $body,
        '',
    ]);

    return (bool) @file_put_contents(MAIL_LOG, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * SMTP driver powered by PHPMailer. Falls back to log driver if setup fails.
 *
 * @param array<int,string> $attachments
 */
function _send_smtp(string $to, string $subject, string $body, array $attachments = []): bool
{
    if (!eventhub_require_vendor_autoload() || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        log_app_error('PHPMailer not installed. Run composer install or use MAIL_DRIVER=log.', __FILE__, __LINE__);
        return _send_log($to, $subject, $body, $attachments);
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        if (MAIL_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        foreach ($attachments as $attachment) {
            if (is_file($attachment)) {
                $mail->addAttachment($attachment);
            }
        }
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        log_app_error('PHPMailer: ' . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }}