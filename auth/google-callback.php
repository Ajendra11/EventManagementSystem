<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

if (!eventhub_require_vendor_autoload() || !class_exists(Google_Client::class) || !class_exists(Google_Service_Oauth2::class)) {
    flash('error', 'Google sign-in is not available. Run composer install and configure Google OAuth first.');
    redirect('auth/login.php');
}

if (
    !isset($_GET['state'], $_SESSION['google_oauth_state']) ||
    !hash_equals($_SESSION['google_oauth_state'], (string) $_GET['state'])
) {
    flash('error', 'Invalid Google sign-in session. Please try again.');
    redirect('auth/login.php');
}

unset($_SESSION['google_oauth_state']);

if (!isset($_GET['code'])) {
    flash('error', 'Google sign-in was cancelled or failed.');
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

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

if (isset($token['error'])) {
    flash('error', 'Google sign-in failed: ' . $token['error']);
    redirect('auth/login.php');
}

$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$googleUser = $oauth->userinfo->get();

$googleId = (string) $googleUser->id;
$email = strtolower(trim((string) $googleUser->email));
$name = trim((string) $googleUser->name);

if ($googleId === '' || $email === '') {
    flash('error', 'Google account did not provide required profile information.');
    redirect('auth/login.php');
}

$pdo = db();

$stmt = $pdo->prepare('
    SELECT *
    FROM users
    WHERE google_id = :google_id OR email = :email
    LIMIT 1
');
$stmt->execute(['google_id' => $googleId, 'email' => $email]);
$user = $stmt->fetch();

if ($user) {
    if (($user['status'] ?? '') === 'blocked') {
        flash('error', 'Your account has been blocked. Please contact the administrator.');
        redirect('auth/login.php');
    }

    if (empty($user['google_id'])) {
        $update = $pdo->prepare('
            UPDATE users
            SET google_id = :google_id,
                auth_provider = "google",
                email_verified_at = COALESCE(email_verified_at, NOW())
            WHERE id = :id
        ');
        $update->execute(['google_id' => $googleId, 'id' => $user['id']]);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $user['id']]);
        $user = $stmt->fetch();
    }

    login_user($user);
    flash('success', 'Signed in successfully with Google.');
    redirect('events/index.php');
}

$randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);

$insert = $pdo->prepare('
    INSERT INTO users (
        full_name,
        email,
        google_id,
        auth_provider,
        password_hash,
        role,
        status,
        email_verified_at,
        created_at
    )
    VALUES (
        :full_name,
        :email,
        :google_id,
        "google",
        :password_hash,
        "participant",
        "active",
        NOW(),
        NOW()
    )
');

$insert->execute([
    'full_name' => $name !== '' ? $name : $email,
    'email' => $email,
    'google_id' => $googleId,
    'password_hash' => $randomPasswordHash,
]);

$newUserId = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $newUserId]);
$newUser = $stmt->fetch();

if (!$newUser) {
    flash('error', 'Google account was created but login failed. Please try again.');
    redirect('auth/login.php');
}

login_user($newUser);
flash('success', 'Your EventHub account has been created using Google.');
redirect('events/index.php');
