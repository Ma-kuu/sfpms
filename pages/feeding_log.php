<?php
require_once __DIR__ . '/../controllers/feeding_log.php';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
<div class="alert-block" style="background:#d1fae5;color:#065f46;" id="msgBanner">
  <i class="bi bi-check-circle-fill"></i>
  <strong>Attendance saved successfully.</strong>
</div>
<?php endif; ?>

<!-- Attendance Checklist View-->
<?php if ($attendanceView): ?>
<div style="margin-bottom:1.25rem;">
  <a href="feeding_log.php" class="btn-outline-custom" style="margin-bottom:1rem;display:inline-flex;">
    <i class="bi bi-arrow-left"></i> Back to Sessions
  </a>
  <div class="table-wrapper">
    <div class="table-header">
      <div>
        <div style="font-weight:700;font-size:1rem;">Attendance Checklist</div>
        <div style="font-size:.8rem;color:#6c757d;margin-top:.2rem;">
          <?= htmlspecialchars($attendanceView['school_name']) ?> &mdash;
          <?= date('F j, Y', strtotime($attendanceView['session_date'])) ?> &mdash;
          <?= htmlspecialchars($attendanceView['meal_type']) ?>
        </div>
      </div>
    </div>
    <form method="post" action="router.php">
      <input type="hidden" name="module" value="attendance">
      <input type="hidden" name="action" value="save_attendance">
      <input type="hidden" name="session_id" value="<?= $attendanceView['id'] ?>">
      <?= csrf_field() ?>
      <div style="padding:.75rem 1rem;">
        <div class="row g-2">
          <?php foreach ($attendance as $pupil): ?>
          <input type="hidden" name="target_ids[]" value="<?= $pupil['beneficiary_id'] ?>">
          <div class="col-12 col-sm-6 col-md-4">
            <label class="d-flex align-items-center gap-2 p-2"
                   style="border:1px solid #eee;border-radius:7px;cursor:pointer;
                          background:<?= $pupil['present'] ? '#f0fdf4' : '#fff' ?>;">
              <input type="checkbox" name="present[]"
                     value="<?= $pupil['beneficiary_id'] ?>"
                     <?= $pupil['present'] ? 'checked' : '' ?>
                     class="form-check-input" style="width:1.1rem;height:1.1rem;">
              <div>
                <div style="font-size:.855rem;font-weight:500;"><?= htmlspecialchars($pupil['full_name']) ?></div>
                <div style="font-size:.72rem;color:#6c757d;"><?= htmlspecialchars($pupil['grade_level']) ?> - <?= htmlspecialchars($pupil['lrn']) ?></div>
              </div>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="padding:.75rem 1rem 1rem;border-top:1px solid #eee;display:flex;gap:.5rem;">
        <button type="submit" id="btn-save-attendance" class="btn-primary-custom">
          <i class="bi bi-check-circle"></i> Save Attendance
        </button>
        <a href="feeding_log.php" class="btn-outline-custom">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
<!-- Sessions Table-->
<div class="table-wrapper">
  <div class="table-header">
    <div style="font-weight:600;font-size:.95rem;">All Feeding Sessions</div>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSessionModal">
      <i class="bi bi-plus-lg"></i> Add Session
    </button>
  </div>
  <div class="table-responsive">
    <table class="table" id="sessionsTable">
      <thead>
        <tr>
          <th>
            <a href="<?= sortUrl('session_date', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Date <?= sortIcon('session_date', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th>
            <a href="<?= sortUrl('school_name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              School <?= sortIcon('school_name', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('meal_type', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Meal <?= sortIcon('meal_type', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('present_count', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Present <?= sortIcon('present_count', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">Attendance</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sessions)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No sessions found.</td></tr>
        <?php else: ?>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><?= date('M j, Y', strtotime($s['session_date'])) ?></td>
          <td style="font-size:.82rem;"><?= htmlspecialchars($s['school_name']) ?></td>
          <td class="text-center">
            <span class="status-badge badge-active"><?= htmlspecialchars($s['meal_type']) ?></span>
          </td>
          <td class="text-center">
            <strong><?= (int)$s['present_count'] ?></strong>
            <span class="text-muted" style="font-size:.78rem;">/ <?= (int)$s['total_enrolled'] ?></span>
          </td>
          <td class="text-center">
            <a href="feeding_log.php?session_id=<?= $s['id'] ?>" class="btn-outline-custom" style="font-size:.78rem;padding:.3rem .7rem;">
              <i class="bi bi-clipboard-check"></i> View
            </a>
          </td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn-dots" data-bs-toggle="dropdown" id="ddSes<?= $s['id'] ?>">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" style="font-size:.855rem;min-width:130px;">
                <li>
                  <a class="dropdown-item" href="#"
                     onclick="openEditSession(<?= htmlspecialchars(json_encode($s)) ?>); return false;">
                    <i class="bi bi-pencil me-2 text-primary"></i>Edit
                  </a>
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="#"
                     onclick="openDeleteSession(<?= $s['id'] ?>, '<?= date('M j, Y', strtotime($s['session_date'])) ?>'); return false;">
                    <i class="bi bi-trash me-2"></i>Delete
                  </a>
                </li>
              </ul>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="px-3 pb-3">
    <?= renderPagination($pag) ?>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/modals.php'; ?>

<script>
window.pageData = {
  schools:    <?= json_encode($schools) ?>,
  isSA:       <?= $isSA ? 'true' : 'false' ?>,
  mySchoolId: <?= $schoolId ?? 'null' ?>
};
</script>
<script src="../assets/js/feeding-log.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
