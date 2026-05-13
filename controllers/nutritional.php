<?php
// pages/nutritional.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NutritionalRecord.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
Auth::check();

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];
require_once __DIR__ . '/../classes/School.php';
$schools      = School::getList();
$schFilter    = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;
$search       = trim($_GET['search'] ?? '');

$isTeacher = Auth::isTeacher();
$teacherGrade   = $isTeacher ? $user['grade_level'] : null;
$teacherSection = $isTeacher ? $user['section'] : null;

// All active beneficiaries for add/edit dropdowns
$beneficiaries = Beneficiary::getListForDropdown($schoolId, $teacherGrade, $teacherSection);

$classFilter = $_GET['classification'] ?? '';

$totalCount = NutritionalRecord::countAll($schFilter, $search ?: null, $teacherGrade, $teacherSection, $classFilter);
$pag = paginate($totalCount, 20);

$sortBy  = in_array($_GET['sort'] ?? '', ['full_name', 'record_date', 'weight_kg', 'height_cm', 'bmi']) ? $_GET['sort'] : 'full_name';
$sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$records = NutritionalRecord::getAll($schFilter, $search ?: null, $teacherGrade, $teacherSection, $classFilter, $pag['page'], $pag['perPage'], $sortBy, $sortDir);
$pageTitle = 'Nutritional Records';
