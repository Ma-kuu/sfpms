<?php
// pages/reports.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$user      = Auth::user();
$isSA      = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId  = $isSA ? null : (int)$user['school_id'];
$pdo      = getPDO();
$schools  = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();

$type     = $_GET['type']      ?? '';
$schFil   = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

$reportData = [];

if ($type === 'attendance' && $dateFrom && $dateTo) {
    $w = $schFil ? 'AND fs.school_id = ?' : '';
    $p = $schFil ? [$dateFrom, $dateTo, $schFil] : [$dateFrom, $dateTo];
    $stmt = $pdo->prepare("
        SELECT
            CONCAT(b.first_name,' ',b.last_name) AS full_name,
            b.lrn, b.grade_level,
            s.name AS school_name,
            COUNT(fa.id) AS total_sessions,
            SUM(fa.present) AS total_present,
            (COUNT(fa.id) - SUM(fa.present)) AS total_absent
        FROM beneficiaries b
        JOIN schools s ON s.id = b.school_id
        JOIN feeding_attendance fa ON fa.beneficiary_id = b.id
        JOIN feeding_sessions fs ON fs.id = fa.session_id
            AND fs.session_date BETWEEN ? AND ?
        WHERE b.status = 'Active' {$w}
        GROUP BY b.id
        ORDER BY s.name, b.last_name
    ");
    $stmt->execute($p);
    $reportData = $stmt->fetchAll();
}

if ($type === 'nutritional' && $dateFrom && $dateTo) {
    $w = $schFil ? 'AND b.school_id = ?' : '';
    $p = $schFil ? [$dateFrom, $dateTo, $schFil] : [$dateFrom, $dateTo];
    $stmt = $pdo->prepare("
        SELECT
            CONCAT(b.first_name,' ',b.last_name) AS full_name,
            b.lrn, b.grade_level,
            s.name AS school_name,
            nr.record_date, nr.weight_kg, nr.height_cm, nr.bmi
        FROM nutritional_records nr
        JOIN beneficiaries b ON b.id = nr.beneficiary_id
        JOIN schools s ON s.id = b.school_id
        WHERE nr.record_date BETWEEN ? AND ? {$w}
        ORDER BY s.name, b.last_name
    ");
    $stmt->execute($p);
    $reportData = $stmt->fetchAll();
}

if ($type === 'inventory') {
    $w = $schFil ? 'WHERE i.school_id = ?' : '';
    $p = $schFil ? [$schFil] : [];
    $stmt = $pdo->prepare("
        SELECT i.*, s.name AS school_name,
               IF(i.quantity <= i.low_stock_threshold,1,0) AS is_low
        FROM inventory i
        JOIN schools s ON s.id = i.school_id
        {$w}
        ORDER BY s.name, i.item_name
    ");
    $stmt->execute($p);
    $reportData = $stmt->fetchAll();
}

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Report Type Selector -->
<div class="table-wrapper" style="padding:1.5rem;">
  <div class="mb-3">
    <div style="font-weight:600;font-size:.95rem;">Generate Report</div>
  </div>
  <form method="get" action="reports.php" class="row g-3 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Report Type</label>
      <select name="type" id="reportType" class="form-select" onchange="toggleDates(this.value)">
        <option value="">— Select Type —</option>
        <option value="attendance"  <?= $type==='attendance'  ? 'selected' : '' ?>>Attendance Summary</option>
        <option value="nutritional" <?= $type==='nutritional' ? 'selected' : '' ?>>Nutritional Status</option>
        <?php if (!$isTeacher): ?>
        <option value="inventory"   <?= $type==='inventory'   ? 'selected' : '' ?>>Inventory Status</option>
        <?php endif; ?>
      </select>
    </div>
    <?php if ($isSA): ?>
    <div class="col-md-3">
      <label class="form-label">School</label>
      <select name="school_id" class="form-select">
        <option value="">All Schools</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($schFil == $s['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <input type="hidden" name="school_id" value="<?= $schoolId ?>">
    <?php endif; ?>
    <div class="col-md-2" id="dateFromWrap" style="<?= $type==='inventory' ? 'display:none' : '' ?>">
      <label class="form-label">From</label>
      <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
    </div>
    <div class="col-md-2" id="dateToWrap" style="<?= $type==='inventory' ? 'display:none' : '' ?>">
      <label class="form-label">To</label>
      <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn-primary-custom w-100">
        <i class="bi bi-file-earmark-bar-graph"></i> Generate
      </button>
    </div>
  </form>
</div>

<!-- Rendered Report-->
<?php if ($type && !empty($reportData)): ?>
<div style="margin-top:1.5rem;">
  <div class="d-flex align-items-center justify-content-between mb-3 no-print">
    <div style="font-weight:600;font-size:.95rem;margin:0;">
      <?php
        $labels = [
            'attendance'  => 'Attendance Summary Report',
            'nutritional' => 'Nutritional Status Report',
            'inventory'   => 'Inventory Status Report',
        ];
        echo $labels[$type] ?? '';
      ?>
      <span class="text-muted" style="font-weight:400;font-size:.82rem;margin-left:.5rem;">
        <?= $type !== 'inventory' ? "($dateFrom to $dateTo)" : '' ?>
      </span>
    </div>
    <button onclick="window.print()" class="btn-outline-custom no-print">
      <i class="bi bi-printer"></i> Print
    </button>
  </div>

  <div class="table-wrapper">
    <div class="table-responsive">

      <?php if ($type === 'attendance'): ?>
      <table class="table" id="reportAttendanceTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>LRN</th>
            <th class="text-center">Grade</th>
            <th>School</th>
            <th class="text-center">Sessions</th>
            <th class="text-center">Present</th>
            <th class="text-center">Absent</th>
            <th class="text-center">Rate</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportData as $r):
            $rate = $r['total_sessions'] > 0
                  ? round(($r['total_present'] / $r['total_sessions']) * 100) : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td style="font-size:.78rem;color:#6c757d;"><?= htmlspecialchars($r['lrn']) ?></td>
            <td class="text-center"><?= htmlspecialchars($r['grade_level']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($r['school_name']) ?></td>
            <td class="text-center"><?= $r['total_sessions'] ?></td>
            <td class="text-center"><?= $r['total_present'] ?></td>
            <td class="text-center" style="color:<?= $r['total_absent'] > 2 ? '#DC3545' : 'inherit' ?>">
              <?= $r['total_absent'] ?>
            </td>
            <td class="text-center">
              <span class="status-badge <?= $rate >= 80 ? 'badge-success' : ($rate >= 60 ? 'badge-warning' : 'badge-danger') ?>">
                <?= $rate ?>%
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php elseif ($type === 'nutritional'): ?>
      <table class="table" id="reportNutTable">
        <thead>
          <tr>
            <th>Name</th>
            <th class="text-center">Date</th>
            <th>School</th>
            <th class="text-center">Weight</th>
            <th class="text-center">Height</th>
            <th class="text-center">BMI</th>
            <th class="text-center">Classification</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportData as $r):
            require_once __DIR__ . '/../classes/NutritionalRecord.php';
            $cls = NutritionalRecord::classifyBMI((float)$r['bmi']);
            $bdg = NutritionalRecord::badgeClass($cls);
          ?>
          <tr>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="text-center"><?= date('M j, Y', strtotime($r['record_date'])) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($r['school_name']) ?></td>
            <td class="text-center"><?= number_format($r['weight_kg'], 1) ?> kg</td>
            <td class="text-center"><?= number_format($r['height_cm'], 1) ?> cm</td>
            <td class="text-center"><strong><?= number_format($r['bmi'], 1) ?></strong></td>
            <td class="text-center"><span class="status-badge <?= $bdg ?>"><?= $cls ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php elseif ($type === 'inventory'): ?>
      <table class="table" id="reportInvTable">
        <thead>
          <tr>
            <th>Item</th>
            <th>School</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Unit</th>
            <th class="text-center">Min. Stock</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportData as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['item_name']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($it['school_name']) ?></td>
            <td class="text-center"><?= number_format($it['quantity'], 0) ?></td>
            <td class="text-center"><?= htmlspecialchars($it['unit']) ?></td>
            <td class="text-center"><?= number_format($it['low_stock_threshold'], 0) ?></td>
            <td class="text-center">
              <?php if ($it['is_low']): ?>
                <span class="status-badge badge-low"><i class="bi bi-exclamation-circle me-1"></i>LOW STOCK</span>
              <?php else: ?>
                <span class="status-badge badge-ok">OK</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

    </div><!-- /table-responsive -->
  </div><!-- /table-wrapper -->

  <p class="text-muted mt-2 no-print" style="font-size:.78rem;">
    <?= count($reportData) ?> record<?= count($reportData) !== 1 ? 's' : '' ?> &mdash;
    Generated on <?= date('F j, Y g:i A') ?>
  </p>
</div>

<?php elseif ($type && empty($reportData)): ?>
<div class="alert-block warning mt-3">
  <i class="bi bi-info-circle-fill"></i>
  No data found for the selected filters.
</div>
<?php endif; ?>

<script src="../assets/js/reports.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
