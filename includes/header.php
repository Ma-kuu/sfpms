<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../classes/Auth.php';
Auth::check();

$user    = Auth::user();
$current = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'dashboard'    => ['icon' => 'grid-fill',          'label' => 'Dashboard',    'roles' => []],
    'beneficiaries'=> ['icon' => 'people-fill',         'label' => 'Beneficiaries','roles' => []],
    'feeding_log'  => ['icon' => 'cup-hot-fill',        'label' => 'Feeding Log',  'roles' => []],
    'nutritional'  => ['icon' => 'heart-pulse-fill',    'label' => 'Nutritional',  'roles' => []],
    'inventory'    => ['icon' => 'box-seam-fill',       'label' => 'Inventory',    'roles' => []],
    'reports'      => ['icon' => 'bar-chart-line-fill', 'label' => 'Reports',      'roles' => []],
    'schools'      => ['icon' => 'building-fill',       'label' => 'Schools',      'roles' => ['super_admin']],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'SFPMS') ?> — SFPMS</title>
  <meta name="description" content="School Feeding Program Management System — Davao del Norte">

  <!-- Bootstrap 5 CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- App Styles -->
  <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <h6><i class="bi bi-leaf-fill me-1"></i> SFPMS</h6>
    <p>School Feeding Program</p>
  </div>

  <div class="sidebar-nav">
    <?php foreach ($navItems as $page => $item):
      if (!empty($item['roles']) && !in_array($user['role'], $item['roles'], true)) continue;
    ?>
    <a href="<?= $page ?>.php"
       class="nav-link-item <?= $current === $page ? 'active' : '' ?>">
      <i class="bi bi-<?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-footer">
    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
    <div class="badge-role"><?= str_replace('_', ' ', $user['role']) ?></div>
    <div class="mt-2">
      <a href="logout.php" class="btn-outline-custom" style="font-size:.75rem;">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

<!-- Main content wrapper -->
<div id="main-content">
  <div class="topbar">
    <h5><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h5>
    <div style="text-align:right;line-height:1.3;">
      <div style="font-size:.78rem;color:#9ca3af;"><?= date('l, F j, Y') ?></div>
      <div id="live-clock" style="font-size:.9rem;font-weight:700;color:var(--primary);letter-spacing:.03em;"></div>
    </div>
  </div>
  <script src="../assets/js/clock.js"></script>
  <div class="page-body">
