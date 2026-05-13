<?php
require_once __DIR__ . '/../controllers/login.php';
?>
<!doctype html><html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — SFPMS</title>
  <meta name="description" content="Sign in to the School Feeding Program Management System">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
  <div class="bg-photo"></div>
  <div class="bg-overlay"></div>
  <div class="login-wrapper">
  <div class="login-card">
    <div class="brand-logo">
      <img src="../assets/images/deped-panabo-seal.png" alt="DepEd Panabo City Seal">
    </div>
    <h1 class="login-title">SFPMS</h1>
    <p class="login-sub">School Feeding Program Management System<br>
       <span style="font-size:.72rem;color:#52B788;font-weight:600;">DepED Panabo City</span></p>

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

    <div class="seal-row">
      <img src="../assets/images/deped-seal.png" alt="DepEd Seal">
      <img src="../assets/images/panabo-seal.png" alt="Panabo City Seal">
      <img src="../assets/images/bp-seal.png" alt="Bagong Pilipinas Seal">
    </div>
  </div>
  </div><!-- /login-wrapper -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
