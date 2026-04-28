<?php

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
