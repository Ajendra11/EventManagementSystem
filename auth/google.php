<?php
require_once __DIR__ . '/../includes/functions.php';

if (!eventhub_require_vendor_autoload() || !class_exists(Google_Client::class)) {
    flash('error', 'Google sign-in is not available. Run composer install and configure Google OAuth first.');
    redirect('auth/login.php');
}

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    flash('error', 'Google sign-in is not configured.');
    redirect('auth/login.php');
}

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

$client->addScope('email');
$client->addScope('profile');
$client->setAccessType('online');
$client->setPrompt('select_account');

$_SESSION['google_oauth_state'] = bin2hex(random_bytes(32));
$client->setState($_SESSION['google_oauth_state']);

header('Location: ' . $client->createAuthUrl());
exit;
