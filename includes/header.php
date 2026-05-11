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
    'inventory'    => ['icon' => 'box-seam-fill',       'label' => 'Inventory',    'roles' => ['super_admin', 'school_admin']],
    'reports'      => ['icon' => 'bar-chart-line-fill', 'label' => 'Reports',      'roles' => []],
    'schools'      => ['icon' => 'building-fill',       'label' => 'Schools',      'roles' => ['super_admin']],
];

// Notifications Logic
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../classes/Inventory.php';

$pdo = getPDO();
$notifs = [];
$isTeacher = Auth::isTeacher();
$isSA = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];

$flaggedAbsent = Beneficiary::getFlaggedAbsent($schoolId);
if (count($flaggedAbsent) > 0) {
    $notifs[] = [
        'type' => 'danger',
        'icon' => 'person-x-fill',
        'text' => count($flaggedAbsent) . ' beneficiar' . (count($flaggedAbsent) > 1 ? 'ies have' : 'y has') . ' missed 3+ sessions.',
        'link' => 'beneficiaries.php'
    ];
}

$recheckWhere = $schoolId ? 'AND b.school_id = ' . (int)$schoolId : '';
if ($isTeacher) {
    $recheckWhere .= " AND b.grade_level = " . $pdo->quote($user['grade_level'])
                  .  " AND b.section = " . $pdo->quote($user['section']);
}
$needsRecheck = $pdo->query("
    SELECT COUNT(*) FROM beneficiaries b
    WHERE b.status = 'Active' {$recheckWhere}
    AND b.id NOT IN (
        SELECT beneficiary_id FROM nutritional_records
        WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    )
")->fetchColumn();

if ($needsRecheck > 0) {
    $notifs[] = [
        'type' => 'warning',
        'icon' => 'bell-fill',
        'text' => $needsRecheck . ' student' . ($needsRecheck > 1 ? 's need' : ' needs') . ' nutritional re-check.',
        'link' => 'nutritional.php'
    ];
}

if (!$isTeacher) {
    $lowStockCount = Inventory::getLowStockCount($schoolId);
    if ($lowStockCount > 0) {
        $notifs[] = [
            'type' => 'warning',
            'icon' => 'exclamation-triangle-fill',
            'text' => $lowStockCount . ' item' . ($lowStockCount > 1 ? 's are' : ' is') . ' low on stock.',
            'link' => 'inventory.php'
        ];
    }
}
$totalNotifs = count($notifs);
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
  <link rel="stylesheet" href="../assets/css/app.css?v=<?= time() ?>">
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
    <div class="d-flex align-items-center gap-3">
      <div class="dropdown">
        <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" style="background:transparent; border:none; padding: .2rem .5rem;">
          <i class="bi bi-bell-fill fs-5" style="color:#6b7280;"></i>
          <?php if ($totalNotifs > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
            <?= $totalNotifs ?>
          </span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="width: 320px; font-size: .875rem;">
          <li><h6 class="dropdown-header">Notifications</h6></li>
          <?php if (empty($notifs)): ?>
            <li><span class="dropdown-item text-muted">No new notifications.</span></li>
          <?php else: ?>
            <?php foreach ($notifs as $n): ?>
            <li>
              <a class="dropdown-item d-flex align-items-start py-2" href="<?= $n['link'] ?>" style="white-space: normal;">
                <i class="bi bi-<?= $n['icon'] ?> text-<?= $n['type'] ?> me-2 mt-1 fs-6"></i>
                <span><?= htmlspecialchars($n['text']) ?></span>
              </a>
            </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
      <div style="text-align:right;line-height:1.3; border-left: 1px solid #e5e7eb; padding-left: 1rem;">
        <div style="font-size:.78rem;color:#9ca3af;"><?= date('l, F j, Y') ?></div>
        <div id="live-clock" style="font-size:.9rem;font-weight:700;color:var(--primary);letter-spacing:.03em;"></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/clock.js"></script>
  <div class="page-body">
