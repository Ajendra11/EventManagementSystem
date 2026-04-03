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