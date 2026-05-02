<?php
require_once __DIR__ . '/includes/layout.php';
render_header('Privacy Policy');
?>
<div class="container section">
    <article class="panel">
        <h1>Privacy Policy</h1>
        <p class="muted">This policy explains how EventHub collects and uses personal data for event booking and ticketing.</p>
        
        <h2>Data we collect</h2>
        <p>EventHub stores your name, email address, encrypted password, booking history, review submissions, and ticket check-in information needed to provide the service.</p>
        
        <h2>Payments</h2>
        <p>EventHub does not store card details. Paid-event processing is delegated to Khalti, and only payment references and status logs are stored.</p>
        
        <h2>Email</h2>
        <p>Transactional emails may be logged during local development or sent through configured SMTP in production.</p>
        
        <h2>Your choices</h2>
        <p>Participants can update their profile, change their password, cancel eligible bookings, manage pending reviews, and delete their account from the account pages.</p>
    </article>
</div>
<?php render_footer(); ?>