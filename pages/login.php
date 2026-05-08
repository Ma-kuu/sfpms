<?php
// ============================================================
// pages/login.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } elseif (!Auth::login($email, $password)) {
        $error = 'Invalid email or password. Please try again.';
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — SFPMS</title>
  <meta name="description" content="Sign in to the School Feeding Program Management System">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root { --primary: #2D6A4F; --accent: #52B788; --neutral: #F8F9FA; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      position: relative; overflow: hidden;
    }
    .bg-photo {
      position: fixed; inset: 0; z-index: 0;
      background-image: url('../assets/images/bg-login.jpg');
      background-size: cover;
      background-position: center;
      transform: scale(1);
    }
    /* Warm yellowish-green overlay */
    .bg-overlay {
      position: fixed; inset: 0; z-index: 1;
      background: linear-gradient(
        135deg,
        rgba(180, 140, 20, 0.55) 0%,
        rgba(45, 106, 79, 0.65) 50%,
        rgba(20, 60, 30, 0.80) 100%
      );
    }
    .login-wrapper {
      position: relative; z-index: 2;
      width: 100%; max-width: 400px;
      padding: 1rem;
    }
    .login-card {
      background: #fff;
      border-radius: 14px;
      padding: 2.5rem 2.25rem 2rem;
      width: 100%; max-width: 400px;
      box-shadow: 0 24px 48px rgba(0,0,0,.28);
    }
    .brand-logo {
      width: 58px; height: 58px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; color: #fff;
      margin: 0 auto 1rem;
    }
    .login-title {
      text-align: center; font-weight: 800; font-size: 1.25rem;
      color: #1B1B1B; letter-spacing: -.01em;
    }
    .login-sub {
      text-align: center; font-size: .8rem; color: #6c757d; margin-bottom: 1.75rem;
    }
    .form-control {
      border-radius: 8px; padding: .6rem .9rem;
      border-color: #dee2e6; font-size: .875rem;
    }
    .form-control:focus {
      border-color: var(--accent); box-shadow: 0 0 0 3px rgba(82,183,136,.2);
    }
    .form-label { font-size: .82rem; font-weight: 500; color: #374151; }
    .btn-login {
      background: var(--primary); color: #fff; border: none;
      border-radius: 8px; width: 100%; padding: .65rem;
      font-size: .9rem; font-weight: 600; cursor: pointer;
      transition: background .15s;
    }
    .btn-login:hover { background: #245840; }
    .alert-danger-custom {
      background: #fee2e2; color: #991b1b; border-radius: 8px;
      padding: .6rem .9rem; font-size: .82rem; margin-bottom: 1rem;
      display: flex; gap: .5rem; align-items: center;
    }
    .input-icon-wrap { position: relative; }
    .input-icon-wrap .form-control { padding-left: 2.2rem; }
    .input-icon-wrap i {
      position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
      color: #9ca3af; font-size: .95rem;
    }
    .hint-text {
      text-align: center; font-size: .72rem; color: #9ca3af; margin-top: 1.25rem;
    }
  </style>
</head>
<body>
  <div class="bg-photo"></div>
  <div class="bg-overlay"></div>
  <div class="login-wrapper">
  <div class="login-card">
    <div class="brand-logo"><i class="bi bi-leaf-fill"></i></div>
    <h1 class="login-title">SFPMS</h1>
    <p class="login-sub">School Feeding Program Management System<br>
       <span style="font-size:.72rem;color:#52B788;font-weight:600;">Davao del Norte</span></p>

    <?php if ($error): ?>
    <div class="alert-danger-custom">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <i class="bi bi-envelope"></i>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="you@sfpms.edu.ph"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-icon-wrap">
          <i class="bi bi-lock"></i>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" id="btn-login" class="btn-login">
        Sign In
      </button>
    </form>

    <p class="hint-text">Demo: <code>admin@sfpms.edu.ph</code> / <code>password</code></p>
  </div>
  </div><!-- /login-wrapper -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
