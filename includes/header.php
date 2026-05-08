<?php
// ============================================================
// includes/header.php
// ============================================================
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

  <style>
    :root {
      --primary:  #2D6A4F;
      --accent:   #52B788;
      --neutral:  #F8F9FA;
      --text:     #1B1B1B;
      --danger:   #DC3545;
      --sidebar-w: 240px;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                   Oxygen, Ubuntu, Cantarell, sans-serif;
      background: #fff;
      color: var(--text);
      min-height: 100vh;
    }

    /* ── Sidebar ── */
    #sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-w); height: 100vh;
      background: #fff;
      border-right: 1px solid #eee;
      display: flex; flex-direction: column;
      z-index: 1000;
      overflow-y: auto;
    }

    .sidebar-brand {
      padding: .85rem 1.25rem;
      border-bottom: 1px solid #f0f0f0;
      min-height: 53px;
      display: flex; flex-direction: column; justify-content: center;
    }
    .sidebar-brand h6 {
      color: var(--primary); font-weight: 800; font-size: .75rem;
      letter-spacing: .08em; text-transform: uppercase; margin: 0;
    }
    .sidebar-brand p {
      color: #9ca3af; font-size: .65rem; margin: .15rem 0 0;
    }

    .sidebar-nav { padding: .5rem 0; flex: 1; }

    .nav-link-item {
      display: flex; align-items: center; gap: .65rem;
      padding: .55rem 1.25rem;
      color: #6b7280;
      text-decoration: none; font-size: .855rem;
      border-left: 3px solid transparent;
      transition: background .12s, color .12s;
      margin: .1rem .5rem;
      border-radius: 7px;
    }
    .nav-link-item:hover {
      background: #f3f4f6;
      color: var(--primary);
    }
    .nav-link-item.active {
      background: rgba(45,106,79,.1);
      color: var(--primary);
      font-weight: 600;
    }
    .nav-link-item i { font-size: 1rem; flex-shrink: 0; }

    .sidebar-footer {
      padding: 1rem 1.25rem;
      border-top: 1px solid #f0f0f0;
    }
    .sidebar-footer .user-name { color: var(--text); font-size: .82rem; font-weight: 600; }
    .sidebar-footer .badge-role {
      display: inline-block; font-size: .65rem; padding: .15rem .5rem;
      background: rgba(45,106,79,.1); color: var(--primary);
      border-radius: 20px; margin-top: .25rem;
    }

    /* ── Main content ── */
    #main-content {
      margin-left: var(--sidebar-w);
      min-height: 100vh;
    }

    .topbar {
      background: #fff;
      padding: .85rem 1.75rem;
      min-height: 53px;
      border-bottom: 1px solid #eee;
      display: flex; align-items: center; justify-content: space-between;
    }
    .topbar h5 { margin: 0; font-weight: 700; font-size: 1.1rem; color: var(--text); }

    .page-body { padding: 1.75rem; }

    /* ── KPI stats — no card, just clean numbers ── */
    .kpi-card {
      background: transparent;
      padding: 1rem 1.25rem 1rem 0;
    }
    .kpi-card .kpi-icon {
      width: 38px; height: 38px;
      background: rgba(45,106,79,.08);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: var(--primary);
    }
    .kpi-card .kpi-val {
      font-size: 1.65rem; font-weight: 800; color: var(--text); line-height: 1;
    }
    .kpi-card .kpi-lbl {
      font-size: .75rem; color: #9ca3af; margin-top: .15rem;
    }

    /* ── Tables ── */
    .table-wrapper {
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .table-wrapper .table-header {
      padding: 1rem 1.25rem;
      display: flex; align-items: center; justify-content: space-between;
      gap: .75rem; flex-wrap: wrap;
      border-bottom: 1px solid #eee;
    }
    .table { margin: 0; }
    .table th {
      font-size: .75rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: .05em; color: #6c757d;
      border-bottom: 1px solid #eee; padding: .75rem 1rem;
      background: #fff;
    }
    .table td {
      padding: .75rem 1rem; vertical-align: middle;
      border-bottom: 1px solid #eee; font-size: .875rem;
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table tbody tr:hover { background: rgba(82,183,136,.04); }

    /* ── Badges ── */
    .badge-active   { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
    .badge-success  { background: #d1fae5; color: #065f46; }
    .badge-warning  { background: #fef3c7; color: #92400e; }
    .badge-danger   { background: #fee2e2; color: #991b1b; }
    .badge-low      { background: #fee2e2; color: #991b1b; }
    .badge-ok       { background: #d1fae5; color: #065f46; }

    .status-badge {
      display: inline-block; padding: .2rem .65rem;
      border-radius: 20px; font-size: .72rem; font-weight: 600;
    }

    /* ── Buttons ── */
    .btn-primary-custom {
      background: var(--primary); color: #fff; border: none;
      border-radius: 7px; padding: .45rem .9rem; font-size: .855rem;
      cursor: pointer; transition: background .15s;
      text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-primary-custom:hover { background: #245840; color: #fff; }

    .btn-outline-custom {
      background: transparent; color: var(--primary);
      border: 1.5px solid var(--primary);
      border-radius: 7px; padding: .4rem .85rem; font-size: .855rem;
      cursor: pointer; transition: all .15s;
      text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
    }
    .btn-outline-custom:hover {
      background: var(--primary); color: #fff;
    }

    /* Dropdown dots button */
    .btn-dots {
      background: none; border: none; padding: .25rem .5rem;
      color: #6c757d; cursor: pointer; border-radius: 5px;
      transition: background .12s;
    }
    .btn-dots:hover { background: #f3f4f6; }

    /* ── Forms ── */
    .form-section-label {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--primary);
      margin-bottom: .6rem; padding-bottom: .3rem;
      border-bottom: 2px solid var(--accent);
      display: inline-block;
    }
    .form-control, .form-select {
      border-radius: 7px; border-color: #dee2e6;
      font-size: .875rem; padding: .5rem .85rem;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--accent); box-shadow: 0 0 0 3px rgba(82,183,136,.18);
    }
    .form-label { font-size: .82rem; font-weight: 500; margin-bottom: .3rem; }

    /* ── Alert blocks ── */
    .alert-block {
      border-radius: 9px; padding: .85rem 1rem;
      display: flex; gap: .65rem; align-items: flex-start;
      margin-bottom: .75rem; font-size: .855rem;
    }
    .alert-block.danger { background: #fee2e2; color: #991b1b; }
    .alert-block.warning { background: #fef3c7; color: #92400e; }
    .alert-block i { font-size: 1.05rem; flex-shrink: 0; margin-top: .05rem; }

    /* ── Modal ── */
    .modal-content { border: none; border-radius: 12px; }
    .modal-header {
      border-bottom: 1px solid #eee; padding: 1rem 1.25rem;
      background: var(--primary); border-radius: 12px 12px 0 0;
    }
    .modal-title { color: #fff; font-weight: 600; font-size: 1rem; }
    .btn-close-white { filter: invert(1); }
    .modal-body { padding: 1.25rem; }
    .modal-footer { border-top: 1px solid #eee; padding: .75rem 1.25rem; }

    /* ── Search/filter bar ── */
    .filter-bar { display: flex; gap: .5rem; flex-wrap: wrap; }
    .filter-bar .form-control,
    .filter-bar .form-select { max-width: 200px; }

    /* ── Charts container ── */
    .chart-card {
      background: #fff;
      border-radius: 10px;
      padding: 1.25rem 1.5rem;
      box-shadow: none;
    }
    .chart-card h6 {
      font-size: .75rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .06em; color: #9ca3af; margin-bottom: .75rem;
    }

    /* ── Print ── */
    @media print {
      #sidebar, .topbar, .no-print { display: none !important; }
      #main-content { margin-left: 0; }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      #sidebar { width: 200px; }
      #main-content { margin-left: 200px; }
    }
  </style>
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
  <script>
    (function(){
      function tick(){
        const now = new Date();
        document.getElementById('live-clock').textContent =
          now.toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
      }
      tick(); setInterval(tick, 1000);
    })();
  </script>
  <div class="page-body">
