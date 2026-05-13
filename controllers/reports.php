<?php
// pages/reports.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/School.php';
require_once __DIR__ . '/../classes/Report.php';
Auth::check();

$user      = Auth::user();
$isSA      = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId  = $isSA ? null : (int)$user['school_id'];
$schools   = School::getList();

$type     = $_GET['type']      ?? '';
$schFil   = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

$reportData = [];

if ($type === 'attendance' && $dateFrom && $dateTo) {
    $reportData = Report::getAttendance($dateFrom, $dateTo, $schFil);
}

if ($type === 'nutritional' && $dateFrom && $dateTo) {
    $reportData = Report::getNutritional($dateFrom, $dateTo, $schFil);
}

if ($type === 'inventory') {
    $reportData = Report::getInventory($schFil);
}

$pageTitle = 'Reports';
