<?php
// pages/dashboard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../classes/FeedingLog.php';
require_once __DIR__ . '/../classes/Inventory.php';
Auth::check();

$user      = Auth::user();
$schoolId  = Auth::isSuperAdmin() ? null : $user['school_id'];

$totalBeneficiaries = Beneficiary::countAll($schoolId);
$totalSessions      = FeedingLog::countSessions($schoolId);
$lowStockCount      = Inventory::getLowStockCount($schoolId);
$flaggedAbsent      = Beneficiary::getFlaggedAbsent($schoolId);
$flaggedCount       = count($flaggedAbsent);

$chartData          = FeedingLog::getBeneficiaryCountPerSchool();
$chartLabels        = array_column($chartData, 'school_name');
$chartValues        = array_column($chartData, 'total');

$lowStockItems      = Inventory::getAll($schoolId);
$lowStockItems      = array_filter($lowStockItems, fn($i) => $i['is_low']);

// Chart 2 — BMI Classification breakdown
require_once __DIR__ . '/../config/db.php';
$pdo = getPDO();
$bmiWhere  = $schoolId ? 'AND b.school_id = ' . (int)$schoolId : '';
$bmiRows   = $pdo->query("
    SELECT
        CASE
            WHEN nr.bmi < 14   THEN 'Severely Wasted'
            WHEN nr.bmi < 16   THEN 'Wasted'
            WHEN nr.bmi < 25   THEN 'Normal'
            WHEN nr.bmi < 30   THEN 'Overweight'
            ELSE 'Obese'
        END AS classification,
        COUNT(*) AS total
    FROM (
        SELECT nr.bmi, nr.beneficiary_id
        FROM nutritional_records nr
        JOIN (SELECT MAX(id) AS maxid FROM nutritional_records GROUP BY beneficiary_id) latest
            ON nr.id = latest.maxid
        JOIN beneficiaries b ON b.id = nr.beneficiary_id
        WHERE 1=1 {$bmiWhere}
    ) nr
    GROUP BY classification
    ORDER BY total DESC
")->fetchAll();
$bmiLabels = array_column($bmiRows, 'classification');
$bmiValues = array_map('intval', array_column($bmiRows, 'total'));
$bmiColors = array_map(fn($l) => match($l) {
    'Severely Wasted' => '#DC3545',
    'Wasted'          => '#fd7e14',
    'Normal'          => '#2D6A4F',
    'Overweight'      => '#ffc107',
    'Obese'           => '#6f42c1',
    default           => '#adb5bd'
}, $bmiLabels);

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

<!-- KPI Strip -->
<div class="kpi-strip">
  <div class="kpi-stat">
    <div class="kpi-stat-label">Total Beneficiaries</div>
    <div class="kpi-stat-val"><?= $totalBeneficiaries ?></div>
  </div>
  <div class="kpi-divider"></div>
  <div class="kpi-stat">
    <div class="kpi-stat-label">Feeding Sessions</div>
    <div class="kpi-stat-val"><?= $totalSessions ?></div>
  </div>
  <div class="kpi-divider"></div>
  <div class="kpi-stat">
    <div class="kpi-stat-label">Missed 3+ Sessions</div>
    <div class="kpi-stat-val" style="color:<?= $flaggedCount > 0 ? '#DC3545' : 'inherit' ?>">
      <?= $flaggedCount ?>
    </div>
  </div>
  <div class="kpi-divider"></div>
  <div class="kpi-stat">
    <div class="kpi-stat-label">Low Stock Items</div>
    <div class="kpi-stat-val" style="color:<?= $lowStockCount > 0 ? '#DC3545' : 'inherit' ?>">
      <?= $lowStockCount ?>
    </div>
  </div>
</div>


<!-- Alert blocks -->
<?php if ($flaggedCount > 0): ?>
<div class="alert-block danger mb-3">
  <i class="bi bi-person-x-fill"></i>
  <div>
    <strong><?= $flaggedCount ?> beneficiar<?= $flaggedCount > 1 ? 'ies have' : 'y has' ?> missed 3 or more consecutive feeding sessions.</strong>
    <div style="font-size:.8rem;margin-top:.2rem;">
      <?= implode(', ', array_map(fn($b) => htmlspecialchars($b['first_name'] . ' ' . $b['last_name']), $flaggedAbsent)) ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($lowStockItems)): ?>
<div class="alert-block warning mb-4">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <div>
    <strong>Low stock alert:</strong>
    <?= implode(', ', array_map(fn($i) => htmlspecialchars($i['item_name'] . ' (' . $i['school_name'] . ')'), $lowStockItems)) ?>
  </div>
</div>
<?php endif; ?>

<!-- Chart + Quick Summary -->
<div class="row g-3">
  <div class="col-md-8">
    <div class="chart-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0"><i class="bi bi-bar-chart-line-fill me-1"></i> Beneficiaries per School</h6>
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
          <span style="color:#374151;">Total Sessions Logged</span>
          <span class="status-badge badge-active"><?= $totalSessions ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #eee;">
          <span style="color:#374151;">Total Beneficiaries</span>
          <span class="status-badge badge-success"><?= $totalBeneficiaries ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span style="color:#374151;">Low Stock Items</span>
          <span class="status-badge <?= $lowStockCount > 0 ? 'badge-danger' : 'badge-success' ?>">
            <?= $lowStockCount ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 2nd Chart Row -->
<div class="row g-3 mt-1">
  <!-- BMI Doughnut -->
  <div class="col-md-5">
    <div class="chart-card">
      <h6><i class="bi bi-heart-pulse-fill me-1"></i> BMI Classification</h6>
      <div style="position:relative;height:220px;display:flex;align-items:center;justify-content:center;">
        <canvas id="bmiChart"></canvas>
      </div>
    </div>
  </div>
  <!-- Attendance Trend -->
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
  bmiLabels:   <?= json_encode($bmiLabels) ?>,
  bmiValues:   <?= json_encode($bmiValues) ?>,
  bmiColors:   <?= json_encode($bmiColors) ?>,
  attLabels:   <?= json_encode($attLabels) ?>,
  attValues:   <?= json_encode($attValues) ?>
};
</script>
<script src="../assets/js/dashboard-charts.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
