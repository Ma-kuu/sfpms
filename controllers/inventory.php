<?php
// pages/inventory.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
Auth::checkRole(['super_admin', 'school_admin']);

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];
require_once __DIR__ . '/../classes/School.php';
$schools  = School::getList();

$viewSchoolId = $_GET['view_school'] ?? null;
if (!$isSA && $viewSchoolId != $schoolId && $viewSchoolId) {
    $viewSchoolId = $schoolId;
}

if ($viewSchoolId) {
    $statusFilter = $_GET['status'] ?? '';
    
    $totalCount = Inventory::countAll($viewSchoolId, $statusFilter);
    $pag = paginate($totalCount, 20);

    $sortBy  = in_array($_GET['sort'] ?? '', ['item_name', 'quantity', 'unit']) ? $_GET['sort'] : 'item_name';
    $sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

    $items = Inventory::getAll($viewSchoolId, $statusFilter, $pag['page'], $pag['perPage'], $sortBy, $sortDir);
}

$pageTitle = 'Inventory';
