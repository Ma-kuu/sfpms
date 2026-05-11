<?php
// pages/users.php — Super Admin only
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/School.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::checkRole(['super_admin']);

// Handle messages from router
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// Pagination & Sort
$search     = trim($_GET['search'] ?? '');
$sortBy     = in_array($_GET['sort'] ?? '', ['name', 'email', 'role', 'school_name']) ? $_GET['sort'] : 'name';
$sortDir    = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$totalCount = User::countAll();
$pag        = paginate($totalCount, 20);
$users      = User::getAll($pag['page'], $pag['perPage'], $sortBy, $sortDir);

// Simple search filter
if ($search) {
    $users = array_filter($users, fn($u) =>
        stripos($u['name'], $search) !== false ||
        stripos($u['email'], $search) !== false ||
        stripos($u['school_name'] ?? '', $search) !== false
    );
}

// Fetch schools for the form
$schoolsList = School::getAll(1, 1000, 'name', 'asc');

$pageTitle = 'Users Management';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert-block" style="background:#d1fae5;color:#065f46;margin-bottom:1rem;">
  <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert-block danger" style="margin-bottom:1rem;">
  <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($err) ?>
</div>
<?php endif; ?>

<!-- Top bar -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex align-items-center gap-2 flex-nowrap">
    <div class="input-group" style="width: 280px; max-width: 100%;">
      <input type="text" name="search" class="form-control" placeholder="Search user…"
             value="<?= htmlspecialchars($search) ?>">
      <button class="btn-primary-custom" type="submit" style="border-top-left-radius: 0; border-bottom-left-radius: 0; margin-left: -1px; position: relative; z-index: 2;">
        <i class="bi bi-search"></i>
      </button>
    </div>
    <?php if ($search): ?>
      <a href="users.php" class="btn-outline-custom text-nowrap"><i class="bi bi-x"></i> Clear</a>
    <?php endif; ?>
  </form>
  <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-person-plus-fill"></i> Add User
  </button>
</div>

<!-- Table -->
<div class="table-wrapper">
  <div class="table-header">
    <span style="font-weight:600;font-size:.9rem;">
      System Users &nbsp;<span class="status-badge badge-active"><?= $totalCount ?></span>
    </span>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>
          <a href="<?= sortUrl('name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            Name <?= sortIcon('name', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th>
          <a href="<?= sortUrl('email', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            Email <?= sortIcon('email', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th>
          <a href="<?= sortUrl('role', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            Role <?= sortIcon('role', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th>
          <a href="<?= sortUrl('school_name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            School <?= sortIcon('school_name', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
      <?php else: ?>
      <?php $i = ($pag['page'] - 1) * $pag['perPage'] + 1; foreach ($users as $u): ?>
      <tr>
        <td style="color:#9ca3af;font-size:.8rem;"><?= $i++ ?></td>
        <td style="font-weight:500;">
            <?= htmlspecialchars($u['name']) ?>
            <?php if ($u['id'] === Auth::user()['id']): ?>
                <span class="badge bg-primary ms-1" style="font-size: 0.6rem;">You</span>
            <?php endif; ?>
        </td>
        <td style="font-size:.82rem;color:#6b7280;"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <span class="badge bg-secondary"><?= str_replace('_', ' ', $u['role']) ?></span>
        </td>
        <td style="font-size:.82rem;color:#6b7280;">
          <?php if ($u['school_name']): ?>
              <?= htmlspecialchars($u['school_name']) ?>
              <?php if ($u['role'] === 'teacher' && $u['grade_level']): ?>
                  <br><small class="text-muted"><?= htmlspecialchars($u['grade_level'] . ' - ' . $u['section']) ?></small>
              <?php endif; ?>
          <?php else: ?>
              <span class="text-muted">N/A</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <div class="dropdown">
            <button class="btn-dots" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="#"
                   onclick="openEdit(
                     <?= $u['id'] ?>, 
                     '<?= addslashes($u['name']) ?>', 
                     '<?= addslashes($u['email']) ?>', 
                     '<?= $u['role'] ?>', 
                     '<?= $u['school_id'] ?: '' ?>', 
                     '<?= addslashes($u['grade_level'] ?? '') ?>', 
                     '<?= addslashes($u['section'] ?? '') ?>'
                   )">
                  <i class="bi bi-pencil me-1"></i> Edit
                </a>
              </li>
              <?php if ($u['id'] !== Auth::user()['id']): ?>
              <li>
                <a class="dropdown-item text-danger" href="#"
                   onclick="confirmDelete(<?= $u['id'] ?>, '<?= addslashes($u['name']) ?>')">
                  <i class="bi bi-trash me-1"></i> Delete
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <div class="px-3 pb-3">
    <?= renderPagination($pag) ?>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="router.php">
        <input type="hidden" name="module" value="user">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus-fill me-1"></i> Add User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="text" name="password" class="form-control" placeholder="Default is 'password'">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role" class="form-select" required onchange="toggleAddFields(this.value)">
                <option value="teacher">Teacher</option>
                <option value="school_admin">School Admin</option>
                <option value="super_admin">Super Admin</option>
              </select>
            </div>
            <div class="col-md-6 mb-3" id="add-school-div">
              <label class="form-label">School <span class="text-danger">*</span></label>
              <select name="school_id" class="form-select">
                <option value="">-- Select School --</option>
                <?php foreach ($schoolsList as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row" id="add-teacher-fields">
            <div class="col-md-6 mb-3">
              <label class="form-label">Grade Level</label>
              <select name="grade_level" class="form-select">
                  <option value="">-- Select --</option>
                  <?php for($g=1; $g<=12; $g++) echo "<option value=\"Grade $g\">Grade $g</option>"; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Section</label>
              <input type="text" name="section" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-custom"><i class="bi bi-plus-lg me-1"></i> Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="router.php">
        <input type="hidden" name="module" value="user">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Edit User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit-name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="edit-email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (Leave blank to keep current)</label>
            <input type="text" name="password" class="form-control">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select name="role" id="edit-role" class="form-select" required onchange="toggleEditFields(this.value)">
                <option value="teacher">Teacher</option>
                <option value="school_admin">School Admin</option>
                <option value="super_admin">Super Admin</option>
              </select>
            </div>
            <div class="col-md-6 mb-3" id="edit-school-div">
              <label class="form-label">School <span class="text-danger">*</span></label>
              <select name="school_id" id="edit-school_id" class="form-select">
                <option value="">-- Select School --</option>
                <?php foreach ($schoolsList as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row" id="edit-teacher-fields">
            <div class="col-md-6 mb-3">
              <label class="form-label">Grade Level</label>
              <select name="grade_level" id="edit-grade_level" class="form-select">
                  <option value="">-- Select --</option>
                  <?php for($g=1; $g<=12; $g++) echo "<option value=\"Grade $g\">Grade $g</option>"; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Section</label>
              <input type="text" name="section" id="edit-section" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg me-1"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="post" action="router.php">
        <input type="hidden" name="module" value="user">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="del-id">
        <div class="modal-header" style="background:#DC3545;">
          <h5 class="modal-title">Delete User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:.875rem;">Delete <strong id="del-name"></strong>? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-custom" style="background:#DC3545;">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleAddFields(role) {
    document.getElementById('add-school-div').style.display = role === 'super_admin' ? 'none' : 'block';
    document.getElementById('add-teacher-fields').style.display = role === 'teacher' ? 'flex' : 'none';
}
function toggleEditFields(role) {
    document.getElementById('edit-school-div').style.display = role === 'super_admin' ? 'none' : 'block';
    document.getElementById('edit-teacher-fields').style.display = role === 'teacher' ? 'flex' : 'none';
}

function openEdit(id, name, email, role, school_id, grade, section) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-role').value = role;
    document.getElementById('edit-school_id').value = school_id;
    document.getElementById('edit-grade_level').value = grade;
    document.getElementById('edit-section').value = section;
    
    toggleEditFields(role);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, name) {
    document.getElementById('del-id').value = id;
    document.getElementById('del-name').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initial state on page load for the add modal
document.addEventListener('DOMContentLoaded', () => {
    toggleAddFields('teacher');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
