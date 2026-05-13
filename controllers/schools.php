<?php
// pages/schools.php  — Super Admin only
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/School.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::checkRole(['super_admin']);

// Handle messages from router
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Pagination & Sort
$search     = trim($_GET['search'] ?? '');
$sortBy     = in_array($_GET['sort'] ?? '', ['name','address','beneficiary_count']) ? $_GET['sort'] : 'name';
$sortDir    = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$totalCount = School::countAll();
$pag        = paginate($totalCount, 20);
$schools    = School::getAll($pag['page'], $pag['perPage'], $sortBy, $sortDir);

// Simple search filter (PHP-side since list is small)
if ($search) {
    $schools = array_filter($schools, fn($s) =>
        stripos($s['name'], $search) !== false ||
        stripos($s['address'], $search) !== false
    );
}



$pageTitle = 'Schools';
