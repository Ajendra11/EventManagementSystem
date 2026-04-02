<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];

if (is_post()) {
    $errors = register_user($_POST);
}

render_header('Create Account');
?>


<div class="container">
    <form class="card form-card" method="post">
        <h2>Create account</h2>

        <div class="form-group">
            <label>Full name</label>
            <input type="text" name="full_name">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password">
        </div>

        <div class="form-group">
            <label>Confirm password</label>
            <input type="password" name="confirm_password">
        </div>

        <button type="submit">Create account</button>
    </form>
</div>

<?php render_footer(); ?>