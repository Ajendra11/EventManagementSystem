<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();

    $errors = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');

    if (!$errors) {
        flash('success', 'Login successful!');
        redirect('index.php');
    }
}

render_header('Login');
?>

<div class="container">
<form class="card form-card" method="post" data-validate="true">

<h2>Login</h2>

<?php foreach ($errors as $e): ?>
<div class="flash flash-error"><?= e($e) ?></div>
<?php endforeach; ?>

<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

<div class="form-group">
<label>Email</label>
<input type="email" name="email" data-required="true">
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" data-required="true">
</div>

<button type="submit" class="btn btn-primary btn-block">Login</button>

</form>
</div>

<?php render_footer(); ?>