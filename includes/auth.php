<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function is_account_locked(array $user): bool
{
    if (empty($user['locked_until'])) return false;
    return strtotime($user['locked_until']) > time();
}

function lockout_remaining_minutes(array $user): int
{
    if (empty($user['locked_until'])) return 0;
    return (int) ceil(max(0, strtotime($user['locked_until']) - time()) / 60);
}

function record_failed_attempt(array $user): void
{
    $newCount  = (int) $user['failed_login_attempts'] + 1;
    $lockUntil = null;
    if ($newCount >= 5) {
        $lockUntil = date('Y-m-d H:i:s', time() + 900);
        $newCount  = 0;
    }
    db()->prepare('UPDATE users SET failed_login_attempts = :c, locked_until = :l WHERE id = :id')
        ->execute(['c' => $newCount, 'l' => $lockUntil, 'id' => $user['id']]);
}

function reset_login_attempts(array $user): void
{
    db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id')
        ->execute(['id' => $user['id']]);
}
Ajendra Rai
ajendrarai2047
Online

Sachin_0 — 03/05/2026, 15:32
ani
includes/booking.php:
/** Expire all pending paid bookings older than 15 minutes. */
function expire_pending_bookings(): int
{
    $stmt = db()->prepare(
        "SELECT id, amount FROM bookings
         WHERE status = 'Pending' AND booking_date < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return 0;
    }

    $ids = array_map(fn($r) => (int) $r['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare("UPDATE bookings SET status = 'Cancelled', cancelled_at = NOW() WHERE id IN ($placeholders)")
        ->execute($ids);

    foreach ($rows as $row) {
        log_payment_event((int) $row['id'], 'auto_expiry', (float) $row['amount'], 'cancelled');
    }

    return count($rows);
}
Sachin_0 — 04/05/2026, 14:42
<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

// ── Core query ────────────────────────────────────────────────────────────────

message.txt
11 KB
Sachin_0 — 13:42
........
auth folder ma verify-email.php file banau ani tala ko code hala
<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token !== '') {
    $errors = verify_email_token($token);
    flash($errors ? 'error' : 'success', $errors[0] ?? 'Email verified successfully. You can now sign in.');
    redirect('auth/login.php');
}

render_header('Verify Email');
?>
<div class="container section">
    <div class="panel" style="max-width:640px;">
        <h2>Verify email</h2>
        <p class="muted">Open the verification link sent to your email address. Need a new link?</p>
        <a class="btn btn-primary" href="<?= APP_URL ?>/auth/resend-verification.php">Resend verification email</a>
    </div>
</div>
<?php render_footer(); ?>
git add auth/verify-email.php 
git commit -m "add verify email file"
auth folder ma resend-verification.php file banau  tala ko code hala
<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

$errors = [];
if (is_post()) {
    verify_csrf();
    $errors = resend_verification_email((string)($_POST['email'] ?? ''));
    flash($errors ? 'error' : 'success', $errors[0] ?? 'If the account exists and is unverified, a new verification link has been sent.');
    redirect('auth/login.php');
}

render_header('Resend Verification');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Resend verification email</h2>
        <p class="muted">Enter your account email address.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= old('email') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Send verification link</button>
    </form>
</div>
<?php render_footer(); ?>
---------------------------------------
includes bhanne folder ma auth.php file xa tesma tala ko code add gara
/** Create and email an account verification token. */
function create_email_verification(int $userId, string $email): void
{
    $token = generate_secure_token(32);
    db()->prepare('DELETE FROM email_verifications WHERE user_id = :uid OR expires_at < NOW()')
        ->execute(['uid' => $userId]);

message.txt
3 KB
Sachin_0 — 13:49
functionn register_user(){} bhanne function bhanda mathi paste gara
----------------------------------------------
includes/mail.php ma bhako code replace with code below
<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

message.txt
3 KB
--------------------------------
auth folder ma forgot-password.php bhanne file add and paste the code below
<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

if (is_post()) {
    verify_csrf();
    request_password_reset((string)($_POST['email'] ?? ''));
    flash('success', 'If that email exists, a password reset link has been sent.');
    redirect('auth/login.php');
}

render_header('Forgot Password');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Forgot password</h2>
        <p class="muted">We will send a password reset link if your email exists in EventHub.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= old('email') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Send reset link</button>
    </form>
</div>
<?php render_footer(); ?>
----------------------------
auth folder ma reset-password.php file create and paste the code below
<?php
require_once DIR . '/../includes/layout.php';
require_once DIR . '/../includes/auth.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];

if ($token === '') {
    flash('error', 'Missing password reset token.');
    redirect('auth/forgot-password.php');
}

if (is_post()) {
    verify_csrf();
    $errors = reset_password_with_token($token, (string)($_POST['password'] ?? ''), (string)($_POST['confirm_password'] ?? ''));
    if (!$errors) {
        flash('success', 'Password reset successfully. Please sign in.');
        redirect('auth/login.php');
    }
}

render_header('Reset Password');
?>
<div class="container section">
    <form class="panel" method="post" style="max-width:640px;">
        <h2>Reset password</h2>
        <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
            <label>New password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <p class="muted">Password must include uppercase, lowercase, number, and special character.</p>
        <button class="btn btn-primary" type="submit">Reset password</button>
    </form>
</div>
<?php render_footer(); ?>
-------------------------------
Sachin_0 — 13:58
includes/auth.php file ma add the code below
/** Set a password using the project's complexity rules. */
function set_user_password(int $userId, string $newPass, string $confirmPass): array
{
    if (!validate_password($newPass)) {
        return ['New password must be 8–64 characters and include uppercase, lowercase, a number, and a special character.'];
    }

message.txt
3 KB
paste these before  function delete_user_account(){}
------------------------------------------
git add . nagara ,
git add includes/auth.php esari palai palo add ,commit and last ma ekai choti push hana both timro branch ra main branch ma
﻿
Sachin_0
da_mitra
 
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


function register_user(array $data): array
{
    $errors  = [];
    $name    = trim($data['full_name'] ?? '');
    $email   = strtolower(trim($data['email'] ?? ''));
    $pass    = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirm_password'] ?? '');

    $nameLen = mb_strlen($name);
    if ($nameLen < 2 || $nameLen > 100) $errors[] = 'Full name must be between 2 and 100 characters.';
    if (!validate_email($email)) $errors[] = 'Please enter a valid email address.';
    if (!validate_password($pass)) $errors[] = 'Password must be 8–64 characters and include uppercase, lowercase, a number, and a special character.';
    if ($pass !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors && find_user_by_email($email)) $errors[] = 'That email address is already registered.';

    if ($errors) return $errors;

    db()->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, status, email_verified_at, created_at)
         VALUES (:n, :e, :p, "participant", "active", NOW(), NOW())'
    )->execute([
        'n' => $name,
        'e' => $email,
        'p' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
    ]);

    return [];
}

function attempt_login(string $email, string $password): array
{
    $user = find_user_by_email($email);
    if (!$user) return ['Invalid email or password.'];

    if (is_account_locked($user)) {
        $mins = lockout_remaining_minutes($user);
        return ["Your account is temporarily locked. Try again in $mins minute(s)."];
    }

    if (!password_verify($password, $user['password_hash'])) {
        record_failed_attempt($user);
        $user = find_user_by_email($email);
        if ($user && is_account_locked($user))
            return ['Too many failed attempts. Your account is locked for 15 minutes.'];
        return ['Invalid email or password.'];
    }

    if ($user['status'] !== 'active') return ['Your account is not active. Please contact the administrator.'];

    reset_login_attempts($user);
    login_user($user);
    return [];
}

function attempt_admin_login(string $email, string $password): array
{
    $user = find_user_by_email($email);
    if (!$user) return ['Invalid email or password.'];

    if (is_account_locked($user)) {
        $mins = lockout_remaining_minutes($user);
        return ["Account is temporarily locked. Try again in $mins minute(s)."];
    }

    if (!password_verify($password, $user['password_hash'])) {
        record_failed_attempt($user);
        return ['Invalid email or password.'];
    }

    if ($user['status'] !== 'active') return ['Your account is not active. Please contact the administrator.'];
    if ($user['role'] !== 'admin') return ['Invalid email or password.'];

    reset_login_attempts($user);
    login_user($user);
    return [];
}

function update_profile_name(int $userId, string $fullName): array
{
    $name = trim($fullName);
    $len  = mb_strlen($name);
    if ($len < 2 || $len > 100) return ['Full name must be between 2 and 100 characters.'];
    db()->prepare('UPDATE users SET full_name = :n WHERE id = :id')->execute(['n' => $name, 'id' => $userId]);
    $_SESSION['user']['name'] = $name;
    return [];
}

function change_password(int $userId, string $currentPass, string $newPass, string $confirmPass): array
{
    $user = find_user_by_id($userId);
    if (!$user) return ['User not found.'];
    if (!password_verify($currentPass, $user['password_hash'])) return ['Current password is incorrect.'];
    if (!validate_password($newPass)) return ['New password must be 8–64 characters and include uppercase, lowercase, a number, and a special character.'];
    if ($newPass !== $confirmPass) return ['New passwords do not match.'];
    db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
        ->execute(['h' => password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]), 'id' => $userId]);
    return [];
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