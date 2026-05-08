<?php
// ============================================================
// pages/beneficiaries.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];

// ── AJAX / POST actions ──────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'lrn'         => trim($_POST['lrn'] ?? ''),
            'first_name'  => trim($_POST['first_name'] ?? ''),
            'last_name'   => trim($_POST['last_name'] ?? ''),
            'birthdate'   => $_POST['birthdate'] ?? '',
            'sex'         => $_POST['sex'] ?? 'Male',
            'grade_level' => trim($_POST['grade_level'] ?? ''),
            'section'     => trim($_POST['section'] ?? ''),
            'school_id'   => $isSA ? (int)$_POST['school_id'] : $schoolId,
            'status'      => $_POST['status'] ?? 'Active',
        ];
        if ($action === 'add') {
            Beneficiary::create($data);
        } else {
            Beneficiary::update((int)$_POST['id'], $data);
        }
    }

    if ($action === 'delete') {
        Beneficiary::delete((int)$_POST['id']);
    }

    header('Location: beneficiaries.php');
    exit;
}

// ── Filters ─────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$grade    = trim($_GET['grade']  ?? '');
$filterSch= $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;

$rows   = Beneficiary::getAll($filterSch, $grade ?: null, $search ?: null);
$grades = Beneficiary::getGrades();

// Schools for dropdowns
$pdo     = getPDO();
$schools = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();

$pageTitle = 'Beneficiaries';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-wrapper">
  <div class="table-header">
    <!-- Filter bar -->
    <form method="get" action="beneficiaries.php" class="filter-bar flex-grow-1">
      <input type="text" name="search" class="form-control" placeholder="Search name or LRN…"
             value="<?= htmlspecialchars($search) ?>">
      <?php if ($isSA): ?>
      <select name="school_id" class="form-select">
        <option value="">All Schools</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($filterSch == $s['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <select name="grade" class="form-select">
        <option value="">All Grades</option>
        <?php foreach ($grades as $g): ?>
        <option value="<?= htmlspecialchars($g) ?>" <?= ($grade === $g) ? 'selected' : '' ?>>
          <?= htmlspecialchars($g) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary-custom"><i class="bi bi-search"></i> Filter</button>
      <a href="beneficiaries.php" class="btn-outline-custom">Clear</a>
    </form>
    <!-- Add button -->
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg"></i> Add Beneficiary
    </button>
  </div>

  <div class="table-responsive">
    <table class="table" id="beneficiariesTable">
      <thead>
        <tr>
          <th>LRN</th>
          <th>Name</th>
          <th>School</th>
          <th class="text-center">Grade</th>
          <th class="text-center">Status</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No beneficiaries found.</td></tr>
        <?php else: ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($r['lrn']) ?></td>
          <td><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></td>
          <td style="font-size:.82rem;"><?= htmlspecialchars($r['school_name']) ?></td>
          <td class="text-center">
            <span class="status-badge badge-active"><?= htmlspecialchars($r['grade_level']) ?></span>
          </td>
          <td class="text-center">
            <span class="status-badge <?= $r['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
              <?= $r['status'] ?>
            </span>
          </td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn-dots" data-bs-toggle="dropdown" aria-expanded="false"
                      id="ddBen<?= $r['id'] ?>">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" style="font-size:.855rem;min-width:130px;">
                <li>
                  <a class="dropdown-item" href="#"
                     onclick="openEditModal(<?= htmlspecialchars(json_encode($r)) ?>); return false;">
                    <i class="bi bi-pencil me-2 text-primary"></i>Edit
                  </a>
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="#"
                     onclick="openDeleteModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?>'); return false;">
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

<p class="text-muted mt-2" style="font-size:.78rem;">
  Showing <?= count($rows) ?> record<?= count($rows) !== 1 ? 's' : '' ?>
</p>

<!-- ── Add Modal ── -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Add Beneficiary</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="beneficiaries.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <?= beneficiaryFormFields($schools, null, $isSA, $schoolId) ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-add-beneficiary" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Save Beneficiary
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Edit Modal ── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-fill me-2"></i>Edit Beneficiary</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="beneficiaries.php" id="editForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body" id="editBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-save-beneficiary" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Update Beneficiary
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Delete Confirm Modal ── -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#DC3545;">
        <h5 class="modal-title" id="deleteModalLabel" style="color:#fff;">
          <i class="bi bi-trash-fill me-2"></i>Delete Beneficiary
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="beneficiaries.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
        <div class="modal-body" style="padding:1.25rem;">
          <p style="margin:0;font-size:.9rem;">
            Are you sure you want to delete <strong id="delete_name"></strong>?
            This action cannot be undone.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-confirm-delete-beneficiary"
                  class="btn-primary-custom" style="background:#DC3545;">
            <i class="bi bi-trash"></i> Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const schools = <?= json_encode($schools) ?>;
const isSA    = <?= $isSA ? 'true' : 'false' ?>;
const mySchoolId = <?= $schoolId ?? 'null' ?>;

function renderFields(data = {}) {
  const gradeOpts = ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6']
    .map(g => `<option value="${g}" ${data.grade_level===g?'selected':''}>${g}</option>`).join('');

  const schoolOpts = isSA
    ? schools.map(s => `<option value="${s.id}" ${data.school_id==s.id?'selected':''}>${s.name}</option>`).join('')
    : `<option value="${mySchoolId}" selected></option>`;

  return `
    <div class="mb-3">
      <div class="form-section-label">Basic Information</div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">LRN</label>
        <input type="text" name="lrn" class="form-control" maxlength="12"
               value="${data.lrn||''}" placeholder="12-digit LRN" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control"
               value="${data.first_name||''}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control"
               value="${data.last_name||''}" required>
      </div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Birthdate</label>
        <input type="date" name="birthdate" class="form-control" value="${data.birthdate||''}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Sex</label>
        <select name="sex" class="form-select">
          <option value="Male"   ${data.sex==='Male'?'selected':''}>Male</option>
          <option value="Female" ${data.sex==='Female'?'selected':''}>Female</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="Active"   ${data.status==='Active'?'selected':''}>Active</option>
          <option value="Inactive" ${data.status==='Inactive'?'selected':''}>Inactive</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <div class="form-section-label">School & Grade</div>
    </div>
    <div class="row g-3">
      ${isSA ? `
      <div class="col-md-4">
        <label class="form-label">School</label>
        <select name="school_id" class="form-select" required>${schoolOpts}</select>
      </div>` : `<input type="hidden" name="school_id" value="${mySchoolId}">`}
      <div class="col-md-4">
        <label class="form-label">Grade Level</label>
        <select name="grade_level" class="form-select">${gradeOpts}</select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Section</label>
        <input type="text" name="section" class="form-control" value="${data.section||''}">
      </div>
    </div>
  `;
}

function openEditModal(row) {
  document.getElementById('edit_id').value = row.id;
  document.getElementById('editBody').innerHTML = renderFields(row);
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function openDeleteModal(id, name) {
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_name').textContent = name;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Populate add modal fields
document.getElementById('addModal').addEventListener('show.bs.modal', function() {
  const body = this.querySelector('.modal-body');
  if (!body.querySelector('input[name="lrn"]')) {
    body.innerHTML = renderFields({});
  }
});
</script>

<?php
function beneficiaryFormFields($schools, $data, $isSA, $schoolId) {
    // Rendered server-side for the add modal initial load via JS
    return '';
}
require_once __DIR__ . '/../includes/footer.php';
?>
