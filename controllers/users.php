<?php
// pages/users.php — Super Admin only
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/School.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::checkRole(['super_admin']);

// Handle messages from router
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Pagination & Sort
$search     = trim($_GET['search'] ?? '');
$sortBy     = in_array($_GET['sort'] ?? '', ['name', 'email', 'role', 'school_name']) ? $_GET['sort'] : 'name';
$sortDir    = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$totalCount = User::countAll();
$pag        = paginate($totalCount, 20);
$users      = User::getAll($pag['page'], $pag['perPage'], $sortBy, $sortDir);

// Simple search filter
if ($search) {
    $users = array_filter($users, fn($u) =>
        stripos($u['name'], $search) !== false ||
        stripos($u['email'], $search) !== false ||
        stripos($u['school_name'] ?? '', $search) !== false
    );
}

// Fetch schools for the form
$schoolsList = School::getAll(1, 1000, 'name', 'asc');

$pageTitle = 'Users Management';
