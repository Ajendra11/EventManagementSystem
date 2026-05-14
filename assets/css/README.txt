EventHub CSS Pack

This pack is page-by-page and does not change your PHP files.

Files
- base-core.css = shared visual system
- browse-events.css = index.php and events/index.php
- event-details.css = events/show.php
- login.css = auth/login.php
- register.css = auth/register.php
- forgot-password.css = auth/forgot-password.php
- reset-password.css = auth/reset-password.php
- resend-verification.css = auth/resend-verification.php
- verify-email.css = auth/verify-email.php
- profile.css = auth/profile.php
- my-bookings.css = bookings/my-bookings.php
- khalti-simulator.css = bookings/khalti_simulator.php
- submit-review.css = reviews/submit.php
- my-reviews.css = reviews/my-reviews.php
- ticket-verify.css = tickets/verify.php
- admin-login.css = admin/login.php
- admin-dashboard.css = admin/index.php
- admin-events.css = admin/events.php
- admin-events-form.css = admin/events_form.php
- admin-bookings.css = admin/bookings.php
- admin-reviews.css = admin/reviews.php
- admin-users.css = admin/users.php
- privacy.css = privacy.php

Important
- payment.php does not render a page, so it does not need CSS.
- dashboard.php is only a redirect, so it does not need CSS.
- Each page CSS imports base-core.css, so if all files stay in the same folder you only need to link the page file for that page.

Example link tags
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pages/login.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pages/browse-events.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pages/admin-dashboard.css">

Very important
These CSS files are built to fit your existing HTML as much as possible, but your project has a lot of inline styles.
So this is a strong visual layer, not a perfect pixel-perfect final without linking each page and doing a few small template cleanups later.

About your Three.js prompt
That prompt is for a 3D portfolio website, not for an event booking system.
If you use that whole concept on EventHub, the site will become heavy and harder to use.
For EventHub, a premium cinematic UI with hover, glow, glass panels, and smooth transitions is the better choice.
