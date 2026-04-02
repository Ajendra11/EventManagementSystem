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

    return $errors;
}
