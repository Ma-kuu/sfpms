<?php
// pages/dashboard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../classes/FeedingLog.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$user      = Auth::user();
$isSA      = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId  = $isSA ? null : (int)$user['school_id'];
$pdo       = getPDO();

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

$recheckWhere = $schoolId ? 'AND b.school_id = ' . (int)$schoolId : '';
if ($isTeacher) {
    $recheckWhere .= " AND b.grade_level = " . $pdo->quote($teacherGrade)
                  .  " AND b.section = " . $pdo->quote($teacherSection);
}
$needsRecheck = (int)$pdo->query("
    SELECT COUNT(*) FROM beneficiaries b
    WHERE b.status = 'Active' {$recheckWhere}
    AND b.id NOT IN (
        SELECT beneficiary_id FROM nutritional_records
        WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    )
")->fetchColumn();

// Chart 1 — role-specific
// SA: beneficiaries per school | School Admin: per grade | Teacher: their section students list
if ($isSA) {
    $chartData   = FeedingLog::getBeneficiaryCountPerSchool();
    $chartLabels = array_column($chartData, 'school_name');
    $chartValues = array_map('intval', array_column($chartData, 'total'));
    $chartTitle  = 'Beneficiaries per School';
} elseif ($isTeacher) {
    // Teacher: show their students' attendance rate
    $myStudents  = Beneficiary::getAll($schoolId, $teacherGrade, null, $teacherSection);
    $chartLabels = array_map(fn($s) => $s['first_name'] . ' ' . substr($s['last_name'], 0, 1) . '.', $myStudents);
    $chartValues = array_map(fn($s) => 1, $myStudents); // just count
    $chartTitle  = "My Students ({$teacherGrade} - {$teacherSection})";
} else {
    // School Admin: beneficiaries per grade in their school
    $gradeData = $pdo->prepare("
        SELECT grade_level, COUNT(*) AS total
        FROM beneficiaries WHERE school_id = ? AND status='Active'
        GROUP BY grade_level ORDER BY grade_level
    ");
    $gradeData->execute([$schoolId]);
    $gradeRows   = $gradeData->fetchAll();
    $chartLabels = array_column($gradeRows, 'grade_level');
    $chartValues = array_map('intval', array_column($gradeRows, 'total'));
    $chartTitle  = 'Beneficiaries per Grade';
}

// Chart 2 — Attendance Today
$latestSession = $pdo->query("SELECT id, session_date FROM feeding_sessions WHERE 1=1 " . ($schoolId ? "AND school_id = " . (int)$schoolId : "") . " ORDER BY session_date DESC LIMIT 1")->fetch();

$todayDate = 'No Sessions Yet';
$todayLabels = ['Present', 'Absent'];
$todayValues = [0, 0];

if ($latestSession) {
    $todayDate = date('M j, Y', strtotime($latestSession['session_date']));
    $sid = $latestSession['id'];
    
    $attTodayWhere = "";
    if ($isTeacher) {
        $attTodayWhere .= " AND b.grade_level = " . $pdo->quote($teacherGrade)
                      .  " AND b.section = " . $pdo->quote($teacherSection);
    }
    
    $present = $pdo->query("SELECT SUM(fa.present) FROM feeding_attendance fa JOIN beneficiaries b ON b.id = fa.beneficiary_id WHERE fa.session_id = {$sid} {$attTodayWhere}")->fetchColumn() ?: 0;
    $total = $pdo->query("SELECT COUNT(*) FROM feeding_attendance fa JOIN beneficiaries b ON b.id = fa.beneficiary_id WHERE fa.session_id = {$sid} {$attTodayWhere}")->fetchColumn() ?: 0;
    $absent = $total - $present;
    
    $todayValues = [(int)$present, (int)$absent];
}

// Chart 3 — Attendance trend (last 10 sessions)
$attWhere  = $schoolId ? 'AND fs.school_id = ' . (int)$schoolId : '';
$attRows   = $pdo->query("
    SELECT fs.session_date, SUM(fa.present) AS present, COUNT(fa.id) AS total
    FROM feeding_sessions fs
    JOIN feeding_attendance fa ON fa.session_id = fs.id
    WHERE 1=1 {$attWhere}
    GROUP BY fs.id
    ORDER BY fs.session_date ASC
    LIMIT 10
")->fetchAll();
$attLabels = array_map(fn($r) => date('M j', strtotime($r['session_date'])), $attRows);
$attValues = array_map(fn($r) => $r['total'] > 0 ? round(($r['present']/$r['total'])*100) : 0, $attRows);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Chart + Quick Summary -->
<div class="row g-3">
  <div class="col-md-8">
    <div class="chart-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0"><i class="bi bi-bar-chart-line-fill me-1"></i> <?= $chartTitle ?></h6>
        <div class="d-flex align-items-center gap-2">
          <span id="chart-page-label" style="font-size:.72rem;color:#9ca3af;"></span>
          <button id="chart-prev" class="btn-dots" title="Previous"><i class="bi bi-chevron-left"></i></button>
          <button id="chart-next" class="btn-dots" title="Next"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
      <div style="position:relative;height:220px;">
        <canvas id="beneficiaryChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="chart-card h-100">
      <h6><i class="bi bi-info-circle-fill me-1"></i> Quick Info</h6>
      <div style="font-size:.875rem;">
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #eee;">
          <span style="color:#374151;">Total Sessions</span>
          <span class="status-badge badge-active"><?= $totalSessions ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #eee;">
          <span style="color:#374151;"><?= $isTeacher ? 'My Students' : 'Beneficiaries' ?></span>
          <span class="status-badge badge-success"><?= $totalBeneficiaries ?></span>
        </div>
        <?php if (!$isTeacher): ?>
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #eee;">
          <span style="color:#374151;">Low Stock Items</span>
          <span class="status-badge <?= $lowStockCount > 0 ? 'badge-danger' : 'badge-success' ?>">
            <?= $lowStockCount ?>
          </span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span style="color:#374151;">Needs Recheck</span>
          <span class="status-badge <?= $needsRecheck > 0 ? 'badge-warning' : 'badge-success' ?>">
            <?= $needsRecheck ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 2nd Chart Row -->
<div class="row g-3 mt-1">
  <div class="col-md-5">
    <div class="chart-card">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="bi bi-calendar2-check-fill me-1"></i> Attendance Today</h6>
        <span style="font-size:.72rem;color:#9ca3af;"><?= $todayDate ?></span>
      </div>
      <div style="position:relative;height:200px;display:flex;align-items:center;justify-content:center;">
        <canvas id="todayChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="chart-card">
      <h6><i class="bi bi-graph-up me-1"></i> Attendance Rate Trend (%)</h6>
      <div style="position:relative;height:220px;">
        <canvas id="attendanceChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
window.pageData = {
  chartLabels: <?= json_encode($chartLabels) ?>,
  chartValues: <?= json_encode(array_map('intval', $chartValues)) ?>,
  todayLabels: <?= json_encode($todayLabels) ?>,
  todayValues: <?= json_encode($todayValues) ?>,
  attLabels:   <?= json_encode($attLabels) ?>,
  attValues:   <?= json_encode($attValues) ?>
};
</script>
<script src="../assets/js/dashboard-charts.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
