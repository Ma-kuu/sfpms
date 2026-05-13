<?php
// pages/feeding_log.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/FeedingLog.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
Auth::check();

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];
require_once __DIR__ . '/../classes/School.php';
$schools  = School::getList();

// Attendance view
$attendanceView = null;
$attendance     = [];
if (isset($_GET['session_id'])) {
    $sid            = (int)$_GET['session_id'];
    $attendanceView = FeedingLog::getSessionById($sid);
    
    $teacherGrade   = Auth::isTeacher() ? $user['grade_level'] : null;
    $teacherSection = Auth::isTeacher() ? $user['section'] : null;
    $attendance     = FeedingLog::getAttendanceForSession($sid, $teacherGrade, $teacherSection);
}

$totalCount = FeedingLog::countSessions($schoolId);
$pag = paginate($totalCount, 15);

$sortBy  = in_array($_GET['sort'] ?? '', ['session_date', 'school_name', 'meal_type', 'present_count']) ? $_GET['sort'] : 'session_date';
$sortDir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc'; // Default DESC for dates

$sessions  = FeedingLog::getSessions($schoolId, $pag['page'], $pag['perPage'], $sortBy, $sortDir);

$pageTitle = 'Feeding Log';
