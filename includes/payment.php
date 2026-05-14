<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

/** Record a Khalti/payment lifecycle event for admin/audit tracking. */
function log_payment_event(?int $bookingId, string $event, float $amount, string $status, array $payload = []): void
{
    try {
        db()->prepare(
            'INSERT INTO payment_logs (booking_id, event, amount, status, payload, created_at)
             VALUES (:bid, :event, :amount, :status, :payload, NOW())'
        )->execute([
            'bid' => $bookingId,
            'event' => $event,
            'amount' => $amount,
            'status' => $status,
            'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $e) {
        log_app_error('log_payment_event: ' . $e->getMessage(), __FILE__, __LINE__);
    }
}

function khalti_is_mock_mode(): bool
{
    return KHALTI_MODE === 'mock';
}

/** Build Khalti authorization header safely. */
function khalti_authorization_header(): string
{
    $secret = trim((string) KHALTI_SECRET_KEY);

    if (preg_match('/^key\s+/i', $secret)) {
        return 'Authorization: ' . $secret;
    }

    return 'Authorization: Key ' . $secret;
}

/** Make a JSON Khalti ePayment API request. */
function khalti_api_request(string $endpoint, array $payload, int $maxAttempts = 3): array
{
    if (khalti_is_mock_mode()) {
        return [
            'ok' => false,
            'mock' => true,
            'status' => 0,
            'message' => 'Khalti mock mode is enabled.',
        ];
    }

    if (trim((string) KHALTI_SECRET_KEY) === '') {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'KHALTI_SECRET_KEY is required when KHALTI_MODE is not mock.',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'message' => 'PHP cURL extension is not enabled.',
        ];
    }

    $url = rtrim((string) KHALTI_BASE_URL, '/') . '/' . ltrim($endpoint, '/');
    $lastError = 'Unknown Khalti error.';
    $lastStatus = 0;
    $lastDecoded = null;
    $lastRaw = '';

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                khalti_authorization_header(),
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $lastStatus = $status;
        $lastRaw = (string) $raw;
        $decoded = json_decode((string) $raw, true);
        $lastDecoded = is_array($decoded) ? $decoded : null;

        if ($errno) {
            $lastError = $error;
        } elseif ($status >= 200 && $status < 300 && is_array($decoded)) {
            return [
                'ok' => true,
                'data' => $decoded,
                'status' => $status,
                'raw' => $lastRaw,
            ];
        } else {
            $lastError = is_array($decoded)
                ? json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string) $raw;
        }

        if ($status >= 400 && $status < 500) {
            break;
        }

        usleep((int) (150000 * (2 ** ($attempt - 1))));
    }

    return [
        'ok' => false,
        'status' => $lastStatus,
        'message' => $lastError,
        'payload' => $lastDecoded ?? ['raw' => $lastRaw],
    ];
}

function build_khalti_payment_payload(array $booking): array
{
    $bookingId = (int) $booking['id'];
    $amount = (float) $booking['amount'];

    return [
        'return_url' => APP_URL . '/payments/callback.php',
        'website_url' => APP_URL . '/',
        // Khalti expects amount in paisa.
        'amount' => (int) round($amount * 100),
        'purchase_order_id' => (string) $bookingId,
        'purchase_order_name' => 'EventHub booking #' . $bookingId . ' - ' . (string) ($booking['event_title'] ?? 'Event ticket'),
        'customer_info' => [
            'name' => (string) ($booking['full_name'] ?? 'EventHub Participant'),
            'email' => (string) ($booking['email'] ?? ''),
        ],
    ];
}

/** Initiate Khalti payment for a pending paid booking. */
function initiate_khalti_payment(array $booking): array
{
    $bookingId = (int) $booking['id'];
    $amount = (float) $booking['amount'];

    if ($bookingId <= 0 || $amount <= 0) {
        return ['ok' => false, 'message' => 'Invalid paid booking details.'];
    }

    if (!empty($booking['khalti_pidx']) && !khalti_is_mock_mode()) {
        return [
            'ok' => true,
            'pidx' => (string) $booking['khalti_pidx'],
            'payment_url' => '',
            'requires_reinitiation' => true,
            'message' => 'Payment was already initiated. Please cancel this pending booking and book again if the Khalti session expired.',
        ];
    }

    if (khalti_is_mock_mode()) {
        $pidx = 'mock_' . generate_secure_token(12);

        db()->prepare('UPDATE bookings SET khalti_pidx = :pidx WHERE id = :id')
            ->execute(['pidx' => $pidx, 'id' => $bookingId]);

        log_payment_event($bookingId, 'initiation', $amount, 'mock_pending', [
            'pidx' => $pidx,
            'mode' => 'mock',
        ]);

        return [
            'ok' => true,
            'pidx' => $pidx,
            'payment_url' => APP_URL . '/payments/checkout.php?booking_id=' . $bookingId . '&pidx=' . urlencode($pidx),
            'mock' => true,
        ];
    }

    $payload = build_khalti_payment_payload($booking);
    $response = khalti_api_request('/epayment/initiate/', $payload);

    if (($response['ok'] ?? false) && !empty($response['data']['pidx']) && !empty($response['data']['payment_url'])) {
        db()->prepare('UPDATE bookings SET khalti_pidx = :pidx WHERE id = :id')
            ->execute(['pidx' => $response['data']['pidx'], 'id' => $bookingId]);

        log_payment_event($bookingId, 'initiation', $amount, 'sent', $response['data']);

        return [
            'ok' => true,
            'pidx' => (string) $response['data']['pidx'],
            'payment_url' => (string) $response['data']['payment_url'],
            'mock' => false,
        ];
    }

    log_payment_event($bookingId, 'initiation', $amount, 'failed', $response);

    return [
        'ok' => false,
        'message' => khalti_friendly_error($response, 'Payment initiation failed. Please check Khalti configuration.'),
        'payload' => $response,
    ];
}

/** Verify payment status by Khalti PIDX. */
function verify_khalti_payment(string $pidx): array
{
    $pidx = trim($pidx);

    if ($pidx === '') {
        return ['ok' => false, 'status' => 'MissingPIDX', 'payload' => []];
    }

    if (khalti_is_mock_mode()) {
        if (!str_starts_with($pidx, 'mock_')) {
            return ['ok' => false, 'status' => 'InvalidMockPIDX', 'payload' => ['pidx' => $pidx]];
        }

        return [
            'ok' => true,
            'status' => 'Completed',
            'transaction_id' => 'MOCK-' . strtoupper(substr(hash('sha256', $pidx), 0, 12)),
            'payload' => [
                'pidx' => $pidx,
                'status' => 'Completed',
                'mode' => 'mock',
            ],
        ];
    }

    $response = khalti_api_request('/epayment/lookup/', ['pidx' => $pidx]);

    if (($response['ok'] ?? false)) {
        $data = $response['data'];
        $status = (string) ($data['status'] ?? '');
        $transactionId = (string) ($data['transaction_id'] ?? ($data['idx'] ?? $pidx));

        return [
            'ok' => $status === 'Completed',
            'status' => $status !== '' ? $status : 'Unknown',
            'transaction_id' => $transactionId,
            'payload' => $data,
        ];
    }

    return [
        'ok' => false,
        'status' => 'LookupFailed',
        'payload' => $response,
    ];
}

/** Verify Khalti return/lookup metadata against the booking. */
function khalti_validate_booking_payment(array $booking, array $returnPayload, array $lookupPayload): array
{
    if (!empty($returnPayload['purchase_order_id']) && (string) $returnPayload['purchase_order_id'] !== (string) $booking['id']) {
        return ['Khalti purchase order does not match this booking.'];
    }

    foreach (['amount', 'total_amount'] as $amountKey) {
        if (isset($lookupPayload[$amountKey]) && is_numeric($lookupPayload[$amountKey])) {
            $expected = (int) round((float) $booking['amount'] * 100);
            $actual = (int) $lookupPayload[$amountKey];
            if ($actual !== $expected) {
                return ['Khalti amount does not match the booking amount.'];
            }
        }
    }

    return [];
}

/** Translate common Khalti/API errors into user-friendly text. */
function khalti_friendly_error(array $response, string $default): string
{
    $status = (int) ($response['status'] ?? 0);
    $message = (string) ($response['message'] ?? '');

    if ($status === 401 || str_contains(strtolower($message), 'unauthorized')) {
        return 'Khalti authentication failed. Please check KHALTI_SECRET_KEY in .env.';
    }

    if ($status === 400) {
        return 'Khalti rejected the payment request. Please check amount, return URL, and Khalti merchant settings.';
    }

    if ($status === 0 && trim((string) KHALTI_SECRET_KEY) === '' && !khalti_is_mock_mode()) {
        return 'KHALTI_SECRET_KEY is required when KHALTI_MODE is not mock.';
    }

    if ($status === 0 && str_contains(strtolower($message), 'curl')) {
        return 'Could not connect to Khalti. Please check internet connection and PHP cURL.';
    }

    return $message !== '' ? $default . ' Details: ' . $message : $default;
}

/** Refund paid bookings. Mock mode succeeds; real mode calls Khalti when configured. */
function refund_khalti_payment(array $booking): array
{
    $bookingId = (int) $booking['id'];
    $amount = (float) $booking['amount'];

    if (khalti_is_mock_mode()) {
        log_payment_event($bookingId, 'refund', $amount, 'mock_success', ['booking_id' => $bookingId]);
        return ['ok' => true, 'payload' => ['mode' => 'mock']];
    }

    if (trim((string) KHALTI_SECRET_KEY) === '') {
        log_payment_event($bookingId, 'refund', $amount, 'failed', ['reason' => 'Missing KHALTI_SECRET_KEY']);
        return ['ok' => false, 'payload' => ['reason' => 'Missing KHALTI_SECRET_KEY']];
    }

    $payload = [
        'pidx' => (string) ($booking['khalti_pidx'] ?? ''),
        'amount' => (int) round($amount * 100),
    ];

    $response = khalti_api_request('/epayment/refund/', $payload);
    $ok = (bool) ($response['ok'] ?? false);

    log_payment_event($bookingId, 'refund', $amount, $ok ? 'success' : 'failed', $response);

    if (!$ok) {
        send_email(
            ADMIN_EMAIL,
            'EventHub refund failure',
            "Refund failed for booking #$bookingId. Please check the admin payment logs."
        );
    }

    return ['ok' => $ok, 'payload' => $response];
}
