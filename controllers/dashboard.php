<?php
// pages/dashboard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../classes/FeedingLog.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../classes/Dashboard.php';
Auth::check();

$user      = Auth::user();
$isSA      = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId  = $isSA ? null : (int)$user['school_id'];

// Teacher: count only their section
$teacherGrade   = $isTeacher ? $user['grade_level'] : null;
$teacherSection = $isTeacher ? $user['section'] : null;

$totalBeneficiaries = $isTeacher
    ? count(Beneficiary::getAll($schoolId, $teacherGrade, null, $teacherSection))
    : Beneficiary::countAll($schoolId);
$totalSessions      = FeedingLog::countSessions($schoolId);

$lowStockCount = 0;
if (!$isTeacher) {
    $lowStockCount = Inventory::getLowStockCount($schoolId);
}

// Needs recheck — students without recent nutritional records
$needsRecheck = Dashboard::getNeedsRecheckCount($schoolId, $isTeacher, $teacherGrade, $teacherSection);

// Chart 1 — role-specific
if ($isSA) {
    $chartData   = FeedingLog::getBeneficiaryCountPerSchool();
    $chartLabels = array_column($chartData, 'school_name');
    $chartValues = array_map('intval', array_column($chartData, 'total'));
    $chartTitle  = 'Beneficiaries per School';
} elseif ($isTeacher) {
    $myStudents  = Beneficiary::getAll($schoolId, $teacherGrade, null, $teacherSection);
    $chartLabels = array_map(fn($s) => $s['first_name'] . ' ' . substr($s['last_name'], 0, 1) . '.', $myStudents);
    $chartValues = array_map(fn($s) => 1, $myStudents);
    $chartTitle  = "My Students ({$teacherGrade} - {$teacherSection})";
} else {
    $gradeRows   = Dashboard::getBeneficiariesPerGrade($schoolId);
    $chartLabels = array_column($gradeRows, 'grade_level');
    $chartValues = array_map('intval', array_column($gradeRows, 'total'));
    $chartTitle  = 'Beneficiaries per Grade';
}

// Chart 2 — Attendance Today (latest session)
$todayAtt = Dashboard::getAttendanceToday($schoolId, $isTeacher, $teacherGrade, $teacherSection);
$todayDate = $todayAtt['date'];
$todayLabels = ['Present', 'Absent'];
$todayValues = [$todayAtt['present'], $todayAtt['absent']];

// Chart 3 — Attendance trend (last 10 sessions)
$attRows = Dashboard::getAttendanceTrend($schoolId);
$attLabels = array_map(fn($r) => date('M j', strtotime($r['session_date'])), $attRows);
$attValues = array_map(fn($r) => $r['total'] > 0 ? round(($r['present']/$r['total'])*100) : 0, $attRows);

$pageTitle = 'Dashboard';
