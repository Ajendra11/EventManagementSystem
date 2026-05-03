<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function qr_verification_url(string $token): string
{
    // Clean the token first - remove any non-hex characters
    $cleanToken = preg_replace('/[^a-f0-9]/i', '', trim($token));
    
    // Hex characters don't need urlencode - they are URL safe
    // But we'll use rawurlencode just to be safe (without adding extra chars)
    return APP_URL . '/tickets/verify.php?token=' . rawurlencode($cleanToken);
}

function generate_qr_with_composer(string $verificationUrl, string $path): bool
{
    if (!eventhub_require_vendor_autoload() || !class_exists(\chillerlan\QRCode\QRCode::class)) {
        return false;
    }

    try {
        $optionsClass = \chillerlan\QRCode\QROptions::class;
        $options = class_exists($optionsClass)
            ? new $optionsClass(['outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG, 'scale' => 10, 'imageBase64' => false])
            : null;
        $qr = $options ? new \chillerlan\QRCode\QRCode($options) : new \chillerlan\QRCode\QRCode();
        $qr->render($verificationUrl, $path);
        return is_file($path) && filesize($path) > 0;
    } catch (Throwable $e) {
        log_app_error('chillerlan QR generation failed: ' . $e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

function generate_qr_with_remote_fallback(string $verificationUrl, string $path): bool
{
    if (!ini_get('allow_url_fopen')) {
        return false;
    }

    $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($verificationUrl);
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $image = @file_get_contents($apiUrl, false, $context);

    if (!is_string($image) || strlen($image) < 100 || !str_starts_with($image, "\x89PNG")) {
        return false;
    }

    return (bool) file_put_contents($path, $image);
}

/** Last-resort marker when QR generation is unavailable. It also stores the verification URL as text. */
function generate_fallback_ticket_png(string $verificationUrl, string $path): void
{
    $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAASwAAAEsCAIAAAD2HxkiAAAACXBIWXMAAAsTAAALEwEAmpwYAAABhElEQVR4nO3SwQ3AIBDAwLz/0uncpBISGmXnE0g1LxYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB8Tt95x5rX6T5n2G7b3q8f+z3gPgcwGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZAGQBkAZA/wNmvx6tcn0s8QAAAABJRU5ErkJggg==');
    file_put_contents($path, $png1x1 ?: '');
    file_put_contents($path . '.txt', $verificationUrl);
    log_app_error('QR generator unavailable. Stored verification URL beside fallback image: ' . $verificationUrl, __FILE__, __LINE__);
}