<?php
require_once __DIR__ . '/includes/layout.php';

render_header('Privacy Policy');
?>

<div class="privacy-page">
    <div class="container">
        <section class="privacy-hero card">
            <span class="badge">Privacy</span>
            <h1>Privacy Policy</h1>
            <p class="muted">
                We value your privacy and are committed to protecting your personal information
                while you use EventHub.
            </p>
        </section>

        <section class="privacy-grid">
            <article class="card privacy-card">
                <h2>Information we collect</h2>
                <p>
                    We may collect your name, email address, account details, booking history,
                    and other information required to provide event registration and ticketing services.
                </p>
            </article>

            <article class="card privacy-card">
                <h2>How we use your information</h2>
                <p>
                    Your information is used to manage your account, process bookings, improve
                    our services, and maintain platform security.
                </p>
            </article>

            <article class="card privacy-card">
                <h2>Data protection</h2>
                <p>
                    We apply reasonable security measures to protect your account data, including
                    authentication controls, session handling, and secure password storage.
                </p>
            </article>

            <article class="card privacy-card">
                <h2>Your rights</h2>
                <p>
                    You may review and update your profile information through your account.
                    If you need further assistance, you can contact the platform administrator.
                </p>
            </article>
        </section>
    </div>
</div>

<?php render_footer(); ?>