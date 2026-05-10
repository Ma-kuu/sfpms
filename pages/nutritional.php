<?php
// pages/nutritional.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/NutritionalRecord.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];
$pdo      = getPDO();

// Schools and beneficiaries for dropdowns
$schools      = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();
$schFilter    = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;
$search       = trim($_GET['search'] ?? '');

// All active beneficiaries for add/edit dropdowns
$benQuery = $schoolId
    ? $pdo->prepare("SELECT id, CONCAT(first_name,' ',last_name) AS full_name, lrn FROM beneficiaries WHERE school_id = ? AND status='Active' ORDER BY last_name")
    : $pdo->prepare("SELECT id, CONCAT(first_name,' ',last_name) AS full_name, lrn FROM beneficiaries WHERE status='Active' ORDER BY last_name");
$schoolId ? $benQuery->execute([$schoolId]) : $benQuery->execute();
$beneficiaries = $benQuery->fetchAll();

// CRUD
$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        NutritionalRecord::create([
            'beneficiary_id' => (int)$_POST['beneficiary_id'],
            'record_date'    => $_POST['record_date'],
            'weight_kg'      => (float)$_POST['weight_kg'],
            'height_cm'      => (float)$_POST['height_cm'],
            'recorded_by'    => $user['id'],
        ]);
    } elseif ($action === 'edit') {
        NutritionalRecord::update((int)$_POST['id'], [
            'beneficiary_id' => (int)$_POST['beneficiary_id'],
            'record_date'    => $_POST['record_date'],
            'weight_kg'      => (float)$_POST['weight_kg'],
            'height_cm'      => (float)$_POST['height_cm'],
        ]);
    } elseif ($action === 'delete') {
        NutritionalRecord::delete((int)$_POST['id']);
    }
    header('Location: nutritional.php');
    exit;
}

$records   = NutritionalRecord::getAll($schFilter, $search ?: null);
$pageTitle = 'Nutritional Records';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-wrapper">
  <div class="table-header">
    <!-- Search + school filter -->
    <form method="get" action="nutritional.php" class="filter-bar flex-grow-1">
      <input type="text" name="search" class="form-control" placeholder="Search name or LRN…"
             value="<?= htmlspecialchars($search) ?>">
      <?php if ($isSA): ?>
      <select name="school_id" class="form-select">
        <option value="">All Schools</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($schFilter == $s['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <button type="submit" class="btn-primary-custom"><i class="bi bi-search"></i> Filter</button>
      <a href="nutritional.php" class="btn-outline-custom">Clear</a>
    </form>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addNutModal">
      <i class="bi bi-plus-lg"></i> Add Record
    </button>
  </div>

  <div class="table-responsive">
    <table class="table" id="nutTable">
      <thead>
        <tr>
          <th>Name</th>
          <th class="text-center">Date</th>
          <th class="text-center">Weight (kg)</th>
          <th class="text-center">Height (cm)</th>
          <th class="text-center">BMI</th>
          <th class="text-center">Classification</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($records)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
        <?php else: ?>
        <?php foreach ($records as $r):
            $bmi    = (float)$r['bmi'];
            $cls    = NutritionalRecord::classifyBMI($bmi);
            $bdg    = NutritionalRecord::badgeClass($cls);
        ?>
        <tr>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td class="text-center"><?= date('M j, Y', strtotime($r['record_date'])) ?></td>
          <td class="text-center"><?= number_format($r['weight_kg'], 1) ?></td>
          <td class="text-center"><?= number_format($r['height_cm'], 1) ?></td>
          <td class="text-center"><strong><?= number_format($bmi, 1) ?></strong></td>
          <td class="text-center">
            <span class="status-badge <?= $bdg ?>"><?= $cls ?></span>
          </td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn-dots" data-bs-toggle="dropdown" id="ddNut<?= $r['id'] ?>">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" style="font-size:.855rem;min-width:130px;">
                <li>
                  <a class="dropdown-item" href="#"
                     onclick="openEditNut(<?= htmlspecialchars(json_encode($r)) ?>); return false;">
                    <i class="bi bi-pencil me-2 text-primary"></i>Edit
                  </a>
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="#"
                     onclick="openDeleteNut(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['full_name'])) ?>'); return false;">
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
</div>
<p class="text-muted mt-2" style="font-size:.78rem;"><?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?></p>

<!-- Add Modal -->
<div class="modal fade" id="addNutModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-heart-pulse-fill me-2"></i>Add Nutritional Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="nutritional.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-add-nut" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Save Record
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editNutModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="nutritional.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editNutId">
        <div class="modal-body" id="editNutBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-save-nut" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteNutModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#DC3545;">
        <h5 class="modal-title" style="color:#fff;"><i class="bi bi-trash-fill me-2"></i>Delete Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="nutritional.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteNutId">
        <div class="modal-body">
          <p style="margin:0;font-size:.9rem;">Delete nutritional record for <strong id="deleteNutName"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-confirm-delete-nut"
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
  beneficiaries: <?= json_encode($beneficiaries) ?>
};
</script>
<script src="../assets/js/nutritional.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
