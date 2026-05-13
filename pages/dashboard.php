<?php
require_once __DIR__ . '/../controllers/dashboard.php';
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
