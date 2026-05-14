<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'events/index.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();

    $errors = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');

    if (!$errors) {
        flash('success', 'Welcome back!');
        redirect(is_admin() ? 'admin/index.php' : 'events/index.php');
    }
}

$appUrl = rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Sign in | EventHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 25% 22%, rgba(124, 58, 237, 0.18), transparent 34%),
                radial-gradient(circle at 60% 76%, rgba(6, 182, 212, 0.16), transparent 28%),
                linear-gradient(135deg, #f8f7ff 0%, #f6f7ff 48%, #eefaff 100%);
            color: #17172b;
        }

        .auth-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 86px;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7vw;
            background: rgba(255, 255, 255, 0.84);
            border-bottom: 1px solid rgba(124, 58, 237, 0.12);
            backdrop-filter: blur(18px);
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
        }

        .auth-header-brand {
            color: #7c3aed;
            font-size: 1.65rem;
            font-weight: 900;
            text-decoration: none;
        }

        .auth-header-nav {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .auth-header-nav a {
            color: #334155;
            text-decoration: none;
            font-weight: 800;
        }

        .auth-header-nav a:hover {
            color: #7c3aed;
        }

        .auth-header-btn {
            padding: 14px 24px;
            border-radius: 999px;
            color: #fff !important;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            box-shadow: 0 16px 34px rgba(124, 58, 237, 0.24);
        }

        .theme-btn {
            border: 1px solid rgba(124, 58, 237, 0.14);
            background: rgba(255, 255, 255, 0.86);
            color: #17172b;
            padding: 12px 22px;
            border-radius: 999px;
            font-weight: 900;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.10);
            cursor: pointer;
        }

        .auth-page {
            min-height: 100vh;
            padding-top: 86px;
            display: grid;
            grid-template-columns: minmax(430px, 1fr) 540px;
            align-items: center;
            gap: 42px;
            max-width: 1420px;
            margin: 0 auto;
            padding-left: 70px;
            padding-right: 70px;
        }

        .auth-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #7c3aed;
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 28px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: white;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            box-shadow: 0 18px 38px rgba(124, 58, 237, 0.28);
        }

        svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .badge {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            margin-bottom: 28px;
            border-radius: 999px;
            color: #6d28d9;
            background: rgba(255, 255, 255, 0.62);
            border: 1px solid rgba(124, 58, 237, 0.18);
            font-weight: 700;
            box-shadow: 0 12px 28px rgba(124, 58, 237, 0.08);
        }

        .auth-left h1 {
            margin: 0 0 24px;
            max-width: 570px;
            font-size: clamp(3rem, 5vw, 4.8rem);
            line-height: 1.08;
            letter-spacing: -0.065em;
            color: #17172b;
            font-weight: 950;
        }

        .auth-left p {
            max-width: 610px;
            margin: 0;
            color: #6b668d;
            font-size: 1.2rem;
            line-height: 1.7;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 46px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 12px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(124, 58, 237, 0.16);
            color: #1e293b;
            font-weight: 700;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .chip svg {
            width: 17px;
            height: 17px;
            stroke: #7c3aed;
        }

        .auth-right {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: min(520px, 100%);
            padding: 42px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(124, 58, 237, 0.12);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(18px);
        }

        .card h2 {
            margin: 0 0 12px;
            color: #17172b;
            font-size: 1.85rem;
            font-weight: 900;
        }

        .sub {
            margin: 0 0 30px;
            color: #6b668d;
        }

        .group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #17172b;
            font-weight: 800;
        }

        input {
            width: 100%;
            min-height: 58px;
            padding: 0 18px;
            border-radius: 17px;
            border: 1px solid rgba(124, 58, 237, 0.18);
            background: rgba(255, 255, 255, 0.96);
            color: #17172b;
            font-size: 1rem;
            outline: none;
        }

        input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.13);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 56px;
        }

        .eye-btn {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #746f9a;
            cursor: pointer;
        }

        .submit {
            width: 100%;
            min-height: 58px;
            border: 0;
            border-radius: 17px;
            color: #fff;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            box-shadow: 0 18px 38px rgba(124, 58, 237, 0.24);
            font-size: 1.05rem;
            font-weight: 900;
            cursor: pointer;
        }

        .links {
            margin-top: 26px;
            text-align: center;
            color: #6b668d;
        }

        .links p {
            margin: 10px 0;
        }

        .links a {
            color: #7c3aed;
            font-weight: 900;
            text-decoration: none;
        }

        .demo {
            margin-top: 28px;
            padding: 18px;
            border-radius: 14px;
            text-align: center;
            color: #6b668d;
            background: rgba(124, 58, 237, 0.05);
        }

        .demo strong {
            display: block;
            margin-top: 6px;
            color: #17172b;
        }

        .error {
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 14px;
            color: #991b1b;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.22);
            font-weight: 700;
        }

        html[data-theme="dark"] body {
            background:
                radial-gradient(circle at 25% 22%, rgba(124, 58, 237, 0.22), transparent 34%),
                radial-gradient(circle at 58% 76%, rgba(6, 182, 212, 0.12), transparent 28%),
                linear-gradient(135deg, #080b14 0%, #0f172a 52%, #111827 100%);
            color: #f8fafc;
        }

        html[data-theme="dark"] .auth-header {
            background: rgba(8, 11, 20, 0.82);
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        html[data-theme="dark"] .auth-header-nav a,
        html[data-theme="dark"] .auth-left h1,
        html[data-theme="dark"] .card h2,
        html[data-theme="dark"] label,
        html[data-theme="dark"] .demo strong {
            color: #f8fafc;
        }

        html[data-theme="dark"] .auth-left p,
        html[data-theme="dark"] .sub,
        html[data-theme="dark"] .links {
            color: #b8c3e3;
        }

        html[data-theme="dark"] .card,
        html[data-theme="dark"] .chip,
        html[data-theme="dark"] .badge,
        html[data-theme="dark"] .theme-btn {
            background: rgba(15, 23, 42, 0.82);
            color: #f8fafc;
            border-color: rgba(255, 255, 255, 0.12);
        }

        html[data-theme="dark"] input {
            background: rgba(255, 255, 255, 0.06);
            color: #f8fafc;
            border-color: rgba(255, 255, 255, 0.12);
        }

        @media (max-width: 1000px) {
            .auth-page {
                grid-template-columns: 1fr;
                padding: 120px 28px 60px;
                gap: 34px;
            }

            .auth-header {
                padding: 0 24px;
            }

            .auth-header-nav {
                gap: 14px;
            }

            .auth-left h1 {
                font-size: 3.2rem;
            }
        }

        @media (max-width: 650px) {
            .auth-header {
                height: auto;
                padding: 16px 20px;
                flex-wrap: wrap;
                gap: 14px;
            }

            .auth-header-nav {
                width: 100%;
                justify-content: space-between;
                gap: 10px;
            }

            .auth-header-btn {
                padding: 10px 14px;
            }

            .auth-page {
                padding: 135px 20px 50px;
            }

            .card {
                padding: 28px;
            }

            .auth-left h1 {
                font-size: 2.7rem;
            }

            .theme-btn {
                padding: 10px 14px;
            }
        }


        .auth-footer {
            position: relative;
            z-index: 5;
            margin-top: 34px;
            padding: 38px 7vw 24px;
            background: rgba(255, 255, 255, 0.74);
            border-top: 1px solid rgba(124, 58, 237, 0.12);
            backdrop-filter: blur(18px);
            color: #334155;
        }

        .auth-footer-inner {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 34px;
        }

        .auth-footer h3 {
            margin: 0 0 10px;
            color: #7c3aed;
            font-size: 1.5rem;
            font-weight: 900;
        }

        .auth-footer h4 {
            margin: 0 0 12px;
            color: #17172b;
            font-size: 1rem;
        }

        .auth-footer p {
            margin: 0;
            max-width: 430px;
            line-height: 1.7;
            color: #64748b;
        }

        .auth-footer a {
            display: block;
            margin-bottom: 9px;
            color: #475569;
            text-decoration: none;
            font-weight: 700;
        }

        .auth-footer a:hover {
            color: #7c3aed;
        }

        .auth-footer-bottom {
            max-width: 1180px;
            margin: 26px auto 0;
            padding-top: 18px;
            border-top: 1px solid rgba(124, 58, 237, 0.12);
            color: #64748b;
            font-size: 0.9rem;
        }

        [data-theme="dark"] .auth-footer {
            background: rgba(15, 23, 42, 0.78);
            border-top-color: rgba(148, 163, 184, 0.16);
            color: #cbd5e1;
        }

        [data-theme="dark"] .auth-footer h3 {
            color: #c4b5fd;
        }

        [data-theme="dark"] .auth-footer h4 {
            color: #f8fafc;
        }

        [data-theme="dark"] .auth-footer p,
        [data-theme="dark"] .auth-footer-bottom {
            color: #94a3b8;
        }

        [data-theme="dark"] .auth-footer a {
            color: #cbd5e1;
        }

        [data-theme="dark"] .auth-footer a:hover {
            color: #c4b5fd;
        }

        @media (max-width: 760px) {
            .auth-footer-inner {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>
<header class="auth-header">
    <a href="<?= e($appUrl) ?>/index.php" class="auth-header-brand">EventHub</a>

    <nav class="auth-header-nav">
        <a href="<?= e($appUrl) ?>/events/index.php">Events</a>
        <button class="theme-btn" type="button" id="themeToggle">Dark</button>
        <a href="<?= e($appUrl) ?>/auth/login.php">Login</a>
        <a href="<?= e($appUrl) ?>/auth/register.php" class="auth-header-btn">Create Account</a>
    </nav>
</header>

<main class="auth-page">
    <section class="auth-left">
        <div class="logo">
            <span class="logo-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="4" y="5" width="16" height="15" rx="3"></rect>
                    <path d="M8 3v4M16 3v4M4 10h16"></path>
                </svg>
            </span>
            EventHub
        </div>

        <div class="badge">
            <svg viewBox="0 0 24 24">
                <path d="M12 3l1.2 5.1L18 10l-4.8 1.9L12 17l-1.2-5.1L6 10l4.8-1.9L12 3Z"></path>
            </svg>
            Welcome back
        </div>

        <h1>Your events,<br>all in one place</h1>
        <p>Access your bookings, explore upcoming events, and manage your event activity seamlessly.</p>

        <div class="chips">
            <span class="chip">
                <svg viewBox="0 0 24 24"><path d="M4 9a3 3 0 0 0 0 6v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3a3 3 0 0 0 0-6V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3Z"></path></svg>
                Easy Booking
            </span>

            <span class="chip">
                <svg viewBox="0 0 24 24"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"></path></svg>
                Instant Access
            </span>

            <span class="chip">
                <svg viewBox="0 0 24 24"><path d="M7 11V8a5 5 0 0 1 10 0v3"></path><rect x="5" y="11" width="14" height="10" rx="2"></rect></svg>
                Secure
            </span>
        </div>
    </section>

    <section class="auth-right">
        <div class="card">
            <h2>Sign in to EventHub</h2>
            <p class="sub">Enter your credentials to continue</p>

            <?php foreach ($errors as $err): ?>
                <div class="error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e(old('email')) ?>" placeholder="your@email.com">
                </div>

                <div class="group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" placeholder="Enter your password">
                        <button class="eye-btn" type="button" onclick="togglePassword()">
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button class="submit" type="submit">Sign in</button>

                <div class="links">
                    <p>Don’t have an account? <a href="<?= e($appUrl) ?>/auth/register.php">Create one</a></p>
                    <p>Admin? <a href="<?= e($appUrl) ?>/admin/login.php">Admin login</a></p>
                    <p><a href="<?= e($appUrl) ?>/auth/forgot-password.php">Forgot your password?</a></p>
                </div>

            </form>
        </div>
    </section>
</main>

<script>
    const root = document.documentElement;
    const btn = document.getElementById('themeToggle');

    const saved = localStorage.getItem('eventhub-theme') || 'light';
    root.setAttribute('data-theme', saved);
    btn.textContent = saved === 'dark' ? 'Light' : 'Dark';

    btn.addEventListener('click', () => {
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem('eventhub-theme', next);
        btn.textContent = next === 'dark' ? 'Light' : 'Dark';
    });

    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>


<footer class="auth-footer">
    <div class="auth-footer-inner">
        <div>
            <h3>EventHub</h3>
            <p>Secure, modern event management and online ticketing for Nepal.</p>
        </div>

        <div>
            <h4>Quick links</h4>
            <a href="<?= e($appUrl) ?>/events/index.php">Browse Events</a>
            <a href="<?= e($appUrl) ?>/auth/register.php">Create Account</a>
            <a href="<?= e($appUrl) ?>/privacy.php">Privacy Policy</a>
        </div>

        <div>
            <h4>Admin</h4>
            <a href="<?= e($appUrl) ?>/admin/login.php">Admin Portal</a>
        </div>
    </div>

    <div class="auth-footer-bottom">
        &copy; <?= date('Y') ?> EventHub. Payments powered by Khalti.
    </div>
</footer>

</body>
</html>


