<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

function register_user(array $data): array
{
    $errors  = [];
    $name    = trim($data['full_name'] ?? '');
    $email   = strtolower(trim($data['email'] ?? ''));
    $pass    = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirm_password'] ?? '');

    // Basic name validation
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }

    // Email validation
    if (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password length check
    if (strlen($pass) < 8 || strlen($pass) > 64) {
    $errors[] = 'Password must be between 8 and 64 characters.';
    }

    // Password strength
    if (!validate_password($pass)) {
    $errors[] = 'Password must include uppercase, lowercase, a number, and a special character.';
    }

    // Confirm password
    if ($pass !== $confirm) {
    $errors[] = 'Passwords do not match.';
    }

    // Duplicate email check
    if (find_user_by_email($email)) {
        $errors[] = 'That email address is already registered.';
    }

    if ($errors) {
     return $errors;
    }

    // Insert user into database
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

    if (!$user) {
        return ["Invalid email or password."];
    }

    if (is_account_locked($user)) {
        return ["Account locked. Try again later."];
    }

    if (!password_verify($password, $user['password_hash'])) {
        record_failed_attempt($user);
        return ["Invalid email or password."];
    }

    if ($user['status'] !== 'active') {
        return ["Account inactive."];
    }

    reset_attempts($user);

    // session regenerate
    login_user($user);

    return [];
}