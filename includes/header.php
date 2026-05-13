<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';
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
    'users'        => ['icon' => 'person-badge-fill',   'label' => 'Users',        'roles' => ['super_admin']],
];

// Notifications Logic
require_once __DIR__ . '/../classes/Notification.php';

$isTeacher = Auth::isTeacher();
$isSA = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];

// Sync notifications for the current user
Notification::syncDynamicNotifs($user['id'], $schoolId, $user['role'], $user['grade_level'] ?? null, $user['section'] ?? null);

// Fetch unread
$notifs = Notification::getUnread($user['id']);
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
            <li id="notif-<?= $n['id'] ?>">
              <div class="dropdown-item d-flex align-items-start py-2" style="white-space: normal; flex-direction: column;">
                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                  <span style="font-size: 0.7rem; color: #9ca3af;"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></span>
                  <div>
                    <button onclick="handleNotifAction('read', <?= $n['id'] ?>)" class="btn btn-sm btn-link text-success p-0 me-2" title="Mark as Read"><i class="bi bi-check2-circle fs-6"></i></button>
                    <button onclick="handleNotifAction('delete', <?= $n['id'] ?>)" class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="bi bi-trash fs-6"></i></button>
                  </div>
                </div>
                <a href="<?= htmlspecialchars($n['link']) ?>" class="text-decoration-none text-dark d-flex">
                  <i class="bi bi-<?= htmlspecialchars($n['icon']) ?> text-<?= htmlspecialchars($n['type']) ?> me-2 mt-1 fs-6"></i>
                  <span><?= htmlspecialchars($n['message']) ?></span>
                </a>
              </div>
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
  <script>
    function handleNotifAction(action, id) {
        fetch('router.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ module: 'notification', action: action, id: id, csrf_token: '<?= csrf_token() ?>' })
        }).then(() => {
            const el = document.getElementById('notif-' + id);
            if (el) el.remove();
        });
    }
  </script>
  <div class="page-body">
