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
$pdo      = getPDO();
$schools  = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Attendance form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_attendance') {
    $sessionId  = (int)$_POST['session_id'];
    $presentIds = array_map('intval', $_POST['present'] ?? []);
    $targetIds  = array_map('intval', $_POST['target_ids'] ?? []);
    FeedingLog::saveAttendance($sessionId, $presentIds, $targetIds);
    header('Location: feeding_log.php?msg=saved');
    exit;
}

// Session CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        FeedingLog::createSession([
            'school_id'    => $isSA ? (int)$_POST['school_id'] : $schoolId,
            'session_date' => $_POST['session_date'],
            'meal_type'    => $_POST['meal_type'],
            'remarks'      => trim($_POST['remarks'] ?? ''),
            'created_by'   => $user['id'],
        ]);
    } elseif ($action === 'edit') {
        FeedingLog::updateSession((int)$_POST['id'], [
            'school_id'    => $isSA ? (int)$_POST['school_id'] : $schoolId,
            'session_date' => $_POST['session_date'],
            'meal_type'    => $_POST['meal_type'],
            'remarks'      => trim($_POST['remarks'] ?? ''),
        ]);
    } elseif ($action === 'delete') {
        FeedingLog::deleteSession((int)$_POST['id']);
    }
    header('Location: feeding_log.php');
    exit;
}

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
    <form method="post" action="feeding_log.php">
      <input type="hidden" name="action" value="save_attendance">
      <input type="hidden" name="session_id" value="<?= $attendanceView['id'] ?>">
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

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-cup-hot-fill me-2"></i>Add Feeding Session</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="feeding_log.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-add-session" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Save Session
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Session</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="feeding_log.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editSesId">
        <div class="modal-body" id="editSesBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-save-session" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Session Modal -->
<div class="modal fade" id="deleteSesModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#DC3545;">
        <h5 class="modal-title" style="color:#fff;"><i class="bi bi-trash-fill me-2"></i>Delete Session</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="feeding_log.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteSesId">
        <div class="modal-body">
          <p style="margin:0;font-size:.9rem;">Delete session on <strong id="deleteSesDate"></strong>? All attendance records will also be removed.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-confirm-delete-session"
                  class="btn-primary-custom" style="background:#DC3545;">
            <i class="bi bi-trash"></i> Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
window.pageData = {
  schools:    <?= json_encode($schools) ?>,
  isSA:       <?= $isSA ? 'true' : 'false' ?>,
  mySchoolId: <?= $schoolId ?? 'null' ?>
};
</script>
<script src="../assets/js/feeding-log.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
