<?php
// pages/beneficiaries.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
Auth::check();

$user      = Auth::user();
$isSA      = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId  = $isSA ? null : (int)$user['school_id'];

// Filters — teachers locked to their grade + section
$search    = trim($_GET['search'] ?? '');
$grade     = $isTeacher ? $user['grade_level'] : trim($_GET['grade'] ?? '');
$section   = $isTeacher ? $user['section'] : null;
$filterSch = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;

$totalCount = Beneficiary::countAll($filterSch, $grade ?: null, $search ?: null, $section);
$pag        = paginate($totalCount, 20);

$sortBy  = in_array($_GET['sort'] ?? '', ['lrn', 'last_name', 'school_name', 'grade_level', 'status']) ? $_GET['sort'] : 'last_name';
$sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$rows   = Beneficiary::getAll($filterSch, $grade ?: null, $search ?: null, $section, $pag['page'], $pag['perPage'], $sortBy, $sortDir);
$grades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

require_once __DIR__ . '/../classes/School.php';
$schools = School::getList();

$pageTitle = 'Beneficiaries';
