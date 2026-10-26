<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
start_session();
if (current_user()) redirect('/admin/index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login(trim($_POST['email']), $_POST['password'])) {
        redirect('/admin/index.php');
    }
    $err = 'Email atau password salah.';
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">
<title>Login — Portal KYN</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body class="auth">
<form method="post" class="card auth-card">
  <h1>KYN<span style="color:var(--accent)">●</span> Portal</h1>
  <?php if ($err): ?><div class="alert-bad"><?= e($err) ?></div><?php endif; ?>
  <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
  <label>Email <input name="email" type="email" required autofocus></label>
  <label>Password <input name="password" type="password" required></label>
  <button class="btn btn-dark" type="submit">Masuk</button>
</form></body></html>