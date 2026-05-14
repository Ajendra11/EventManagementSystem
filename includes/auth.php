<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

/** Find a user by email address. */
function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

/** Find a user by numeric ID. */
function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/** Determine whether a user is currently under login lockout. */
function is_account_locked(array $user): bool
{
    return !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time();
}

/** Return the remaining lockout duration in minutes. */
function lockout_remaining_minutes(array $user): int
{
    if (empty($user['locked_until'])) {
        return 0;
    }
    return (int) ceil(max(0, strtotime((string) $user['locked_until']) - time()) / 60);
}

/** Record a failed login attempt and apply 15-minute lockout after 5 failures. */
function record_failed_attempt(array $user): void
{
    $current = (int) ($user['failed_login_attempts'] ?? 0);
    $newCount  = $current + 1;
    $lockUntil = null;

    if ($newCount >= 5) {
        $lockUntil = date('Y-m-d H:i:s', time() + 900);
        $newCount  = 0;
    }

    db()->prepare('UPDATE users SET failed_login_attempts = :c, locked_until = :l WHERE id = :id')
        ->execute(['c' => $newCount, 'l' => $lockUntil, 'id' => $user['id']]);
}

/** Clear failed login counters after successful authentication. */
function reset_login_attempts(array $user): void
{
    db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id')
        ->execute(['id' => $user['id']]);
}

/** Create and email an account verification token. */
function create_email_verification(int $userId, string $email): void
{
    $token = generate_secure_token(32);
    db()->prepare('DELETE FROM email_verifications WHERE user_id = :uid OR expires_at < NOW()')
        ->execute(['uid' => $userId]);

    db()->prepare(
        'INSERT INTO email_verifications (user_id, token, expires_at, created_at)
         VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())'
    )->execute(['uid' => $userId, 'token' => $token]);

    $link = APP_URL . '/auth/verify-email.php?token=' . urlencode($token);
    send_email(
        $email,
        'Verify your EventHub email',
        "Welcome to EventHub.\n\nPlease verify your email address using this link:\n$link\n\nThis link expires in 24 hours."
    );
}

/** Verify an email-verification token and activate the user email. */
function verify_email_token(string $token): array
{
    $stmt = db()->prepare(
        'SELECT ev.*, u.email
         FROM email_verifications ev
         INNER JOIN users u ON u.id = ev.user_id
         WHERE ev.token = :token AND ev.used_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['token' => $token]);
    $record = $stmt->fetch();

    if (!$record || strtotime((string) $record['expires_at']) < time()) {
        return ['Invalid or expired verification link.'];
    }

    db()->beginTransaction();
    try {
        db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :uid')
            ->execute(['uid' => $record['user_id']]);
        db()->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = :id')
            ->execute(['id' => $record['id']]);
        db()->commit();
        return [];
    } catch (Throwable $e) {
        db()->rollBack();
        log_app_error('verify_email_token: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['Unable to verify email right now. Please try again.'];
    }
}

/** Resend a verification token for an unverified account. */
function resend_verification_email(string $email): array
{
    $user = find_user_by_email($email);
    if (!$user) {
        return []; // avoid account enumeration
    }
    if (!empty($user['email_verified_at'])) {
        return ['This email address is already verified.'];
    }
    create_email_verification((int) $user['id'], (string) $user['email']);
    return [];
}

/** Register a participant and send welcome + verification email. */
function register_user(array $data): array
{
    $errors  = [];
    $name    = trim($data['full_name'] ?? '');
    $email   = strtolower(trim($data['email'] ?? ''));
    $pass    = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirm_password'] ?? '');

    $nameLen = mb_strlen($name);
    if ($nameLen < 2 || $nameLen > 100) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }
    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!validate_password($pass)) {
        $errors[] = 'Password must be 8–64 characters and include uppercase, lowercase, a number, and a special character.';
    }
    if ($pass !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$errors && find_user_by_email($email)) {
        $errors[] = 'That email address is already registered.';
    }

    if ($errors) {
        return $errors;
    }

    db()->beginTransaction();
    try {
        db()->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, status, email_verified_at, created_at)
             VALUES (:n, :e, :p, "participant", "active", NULL, NOW())'
        )->execute([
            'n' => $name,
            'e' => $email,
            'p' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $userId = (int) db()->lastInsertId();
        db()->commit();

        send_email($email, 'Welcome to EventHub', "Hello $name,\n\nYour EventHub account has been created successfully.");
        create_email_verification($userId, $email);
        return [];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        log_app_error('register_user: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['Unable to create your account right now. Please try again.'];
    }
}

/** Authenticate a participant/admin through the public login portal. */
function attempt_login(string $email, string $password): array
{
    $user = find_user_by_email($email);

    if (!$user) {
        return ['Invalid email or password.'];
    }
    if (is_account_locked($user)) {
        return ['Your account is temporarily locked. Try again in ' . lockout_remaining_minutes($user) . ' minute(s).'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        record_failed_attempt($user);
        $fresh = find_user_by_email($email);
        if ($fresh && is_account_locked($fresh)) {
            return ['Too many failed attempts. Your account is locked for 15 minutes.'];
        }
        return ['Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return ['Your account is blocked. Please contact the administrator.'];
    }
    // Email verification is fully activated in Sprint 2.
    // In Sprint 1 (MAIL_DRIVER=log / APP_ENV=local) the gate is bypassed so
    // newly registered users can sign in without clicking a verification link.
    $emailVerificationActive = defined('MAIL_DRIVER') && MAIL_DRIVER === 'smtp'
        && defined('APP_ENV') && APP_ENV !== 'local';
    if ($emailVerificationActive && empty($user['email_verified_at'])) {
        return ['Please verify your email before signing in. Use the resend verification link if needed.'];
    }

    reset_login_attempts($user);
    login_user($user);
    return [];
}

/** Authenticate only administrators through the isolated admin portal. */
function attempt_admin_login(string $email, string $password): array
{
    $user = find_user_by_email($email);

    if (!$user) {
        return ['Invalid email or password.'];
    }
    if (is_account_locked($user)) {
        return ['Account is temporarily locked. Try again in ' . lockout_remaining_minutes($user) . ' minute(s).'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        record_failed_attempt($user);
        return ['Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return ['Your account is blocked. Please contact the administrator.'];
    }
    if ($user['role'] !== 'admin') {
        return ['Access denied. Participant accounts cannot use the admin portal.'];
    }

    reset_login_attempts($user);
    login_user($user);
    return [];
}

/** Update a participant display name. */
function update_profile_name(int $userId, string $fullName): array
{
    $name = trim($fullName);
    $len  = mb_strlen($name);
    if ($len < 2 || $len > 100) {
        return ['Full name must be between 2 and 100 characters.'];
    }
    db()->prepare('UPDATE users SET full_name = :n WHERE id = :id')
        ->execute(['n' => $name, 'id' => $userId]);
    $_SESSION['user']['name'] = $name;
    return [];
}

/** Change password after validating the current password. */
function change_password(int $userId, string $currentPass, string $newPass, string $confirmPass): array
{
    $user = find_user_by_id($userId);
    if (!$user) {
        return ['User not found.'];
    }
    if (!password_verify($currentPass, $user['password_hash'])) {
        return ['Current password is incorrect.'];
    }
    return set_user_password($userId, $newPass, $confirmPass);
}

/** Set a password using the project's complexity rules. */
function set_user_password(int $userId, string $newPass, string $confirmPass): array
{
    if (!validate_password($newPass)) {
        return ['New password must be 8–64 characters and include uppercase, lowercase, a number, and a special character.'];
    }
    if ($newPass !== $confirmPass) {
        return ['New passwords do not match.'];
    }
    db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
        ->execute([
            'h'  => password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]),
            'id' => $userId,
        ]);
    return [];
}

/** Create and email a password reset token. */
function request_password_reset(string $email): array
{
    $user = find_user_by_email($email);
    if (!$user) {
        return []; // avoid account enumeration
    }

    $token = generate_secure_token(32);
    db()->prepare('DELETE FROM password_resets WHERE email = :email OR expires_at < NOW()')
        ->execute(['email' => strtolower(trim($email))]);

    db()->prepare(
        'INSERT INTO password_resets (email, token, expires_at, created_at)
         VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())'
    )->execute(['email' => strtolower(trim($email)), 'token' => $token]);

    $link = APP_URL . '/auth/reset-password.php?token=' . urlencode($token);
    send_email(
        (string) $user['email'],
        'Reset your EventHub password',
        "Use this link to reset your EventHub password:\n$link\n\nThe link expires in 1 hour."
    );

    return [];
}

/** Validate a password reset token and update the user password. */
function reset_password_with_token(string $token, string $password, string $confirm): array
{
    $stmt = db()->prepare(
        'SELECT * FROM password_resets
         WHERE token = :token AND used_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['token' => $token]);
    $record = $stmt->fetch();

    if (!$record || strtotime((string) $record['expires_at']) < time()) {
        return ['Invalid or expired reset link.'];
    }

    $user = find_user_by_email((string) $record['email']);
    if (!$user) {
        return ['Account not found.'];
    }

    $errors = set_user_password((int) $user['id'], $password, $confirm);
    if ($errors) {
        return $errors;
    }

    db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id')
        ->execute(['id' => $record['id']]);

    return [];
}

/** Delete a non-admin account after password verification. */
function delete_user_account(int $userId, string $password): array
{
    $user = find_user_by_id($userId);

    if (!$user) {
        return ['User not found.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['Password is incorrect.'];
    }
    if ($user['role'] === 'admin') {
        return ['Admin accounts cannot be deleted from this page.'];
    }

    try {
        db()->beginTransaction();
        db()->prepare('DELETE FROM users WHERE id = :id LIMIT 1')->execute(['id' => $userId]);
        db()->commit();
        return [];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        log_app_error('delete_user_account: ' . $e->getMessage(), __FILE__, __LINE__);
        return ['Unable to delete account right now. Please try again.'];
    }
}
