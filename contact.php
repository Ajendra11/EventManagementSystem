<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/mail.php';

$success = false;
$errors = [];

if (is_post()) {
    verify_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (strlen($name) < 2) {
        $errors[] = 'Please enter your full name (minimum 2 characters).';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (strlen($subject) < 3) {
        $errors[] = 'Please enter a subject (minimum 3 characters).';
    }
    
    if (strlen($message) < 10) {
        $errors[] = 'Please enter your message (minimum 10 characters).';
    }
    
    // Send email if no errors
    if (empty($errors)) {
        $emailBody = "You have received a new message from EventHub contact form.\n\n"
                   . "Name: " . $name . "\n"
                   . "Email: " . $email . "\n"
                   . "Subject: " . $subject . "\n\n"
                   . "Message:\n" . $message . "\n\n"
                   . "---\n"
                   . "This message was sent from EventHub contact form.\n"
                   . "Reply to: " . $email;
        
        $sent = send_email(
            ADMIN_EMAIL,
            "Contact Form: " . $subject,
            $emailBody
        );
        
        if ($sent) {
            $success = true;
            // Clear form fields
            $_POST = [];
        } else {
            $errors[] = 'Unable to send your message. Please try again later.';
        }
    }
}

render_header('Contact Us');
?>

<!-- Added contact-page class here -->
<div class="container section contact-page">
    <div class="grid-2" style="gap: 2rem;">
        
        <!-- Contact Form -->
        <div class="panel">
            <h1>Contact Us</h1>
            <p class="muted">Have questions about events, bookings, or need assistance? Fill out the form below and we'll get back to you within 24 hours.</p>
            
            <?php if ($success): ?>
                <div class="flash flash-success">
                    ✓ Thank you for contacting us! Your message has been sent successfully. We'll respond shortly.
                </div>
            <?php endif; ?>
            
            <?php foreach ($errors as $err): ?>
                <div class="flash flash-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            
            <form method="post" data-validate="true" style="margin-top: 1.5rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" data-required="true" data-label="Name" 
                               value="<?= e($_POST['name'] ?? '') ?>" placeholder="Your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" data-required="true" data-label="Email" 
                               value="<?= e($_POST['email'] ?? '') ?>" placeholder="your@email.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" data-required="true" data-label="Subject" 
                           value="<?= e($_POST['subject'] ?? '') ?>" placeholder="What is this regarding?">
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" data-required="true" data-label="Message" 
                    rows="6" placeholder="Please describe your question or concern in detail..."><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <div class="status-row">
                    <button class="btn btn-primary" type="submit">Send Message</button>
                    <button class="btn btn-outline" type="reset">Clear Form</button>
                </div>
            </form>
        </div>
        
        <!-- Contact Information Sidebar (Right side) -->
        <div>
            <div class="panel" style="margin-bottom: 1.5rem;">
                <h3>Get in Touch</h3>
                <div style="margin-top: 1rem;">
                    <p><strong>Address</strong><br>
                    EventHub Headquarters<br>
                    Kathmandu, Nepal</p>
                    
                    <p><strong>Email</strong><br>
                    <a href="mailto:<?= e(ADMIN_EMAIL) ?>"><?= e(ADMIN_EMAIL) ?></a></p>
                    
                    <p><strong>Phone</strong><br>
                    +977 1 1234567<br>
                    <span class="muted">(Mon-Fri, 9AM - 5PM)</span></p>
                </div>
            </div>
            
            <div class="panel">
                <h3>Frequently Asked</h3>
                <div style="margin-top: 1rem;">
                    <p><strong>How do I book an event?</strong><br>
                    <span class="muted">Simply browse events, click "Book Now", and follow the checkout process.</span></p>
                    
                    <p><strong>How do I cancel a booking?</strong><br>
                    <span class="muted">Go to "My Bookings" and click cancel (if eligible).</span></p>
                    
                    <p><strong>Where do I find my QR ticket?</strong><br>
                    <span class="muted">After booking confirmation, QR code appears in "My Bookings" and is emailed to you.</span></p>
                    
                    <p><strong>How do I leave a review?</strong><br>
                    <span class="muted">After attending an event, go to "My Bookings" and click "Review Event".</span></p>
                </div>
                
                <div style="margin-top: 1rem;">
                    <a href="<?= APP_URL ?>/events/index.php" class="btn btn-outline btn-sm">Browse Events →</a>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php render_footer(); ?>