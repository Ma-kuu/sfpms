<?php
require_once __DIR__ . '/../controllers/schools.php';
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
      <input type="text" name="search" class="form-control" placeholder="Search school…"
             value="<?= htmlspecialchars($search) ?>">
      <button class="btn-primary-custom" type="submit" style="border-top-left-radius: 0; border-bottom-left-radius: 0; margin-left: -1px; position: relative; z-index: 2;">
        <i class="bi bi-search"></i>
      </button>
    </div>
    <?php if ($search): ?>
      <a href="schools.php" class="btn-outline-custom text-nowrap"><i class="bi bi-x"></i> Clear</a>
    <?php endif; ?>
  </form>
  <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-lg"></i> Add School
  </button>
</div>

<!-- Table -->
<div class="table-wrapper">
  <div class="table-header">
    <span style="font-weight:600;font-size:.9rem;">
      All Schools &nbsp;<span class="status-badge badge-active"><?= $totalCount ?></span>
    </span>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>#</th>
              <th>
          <a href="<?= sortUrl('name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            School Name <?= sortIcon('name', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th>
          <a href="<?= sortUrl('address', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center">
            Address <?= sortIcon('address', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th class="text-center">
          <a href="<?= sortUrl('beneficiary_count', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit d-inline-flex align-items-center justify-content-center w-100">
            Beneficiaries <?= sortIcon('beneficiary_count', $sortBy, $sortDir) ?>
          </a>
        </th>
        <th class="text-center">Status</th>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($schools)): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">No schools found.</td></tr>
      <?php else: ?>
      <?php $i = ($pag['page'] - 1) * $pag['perPage'] + 1; foreach ($schools as $s): ?>
      <tr>
        <td style="color:#9ca3af;font-size:.8rem;"><?= $i++ ?></td>
        <td style="font-weight:500;"><?= htmlspecialchars($s['name']) ?></td>
        <td style="font-size:.82rem;color:#6b7280;"><?= htmlspecialchars($s['address'] ?: '—') ?></td>
        <td class="text-center">
          <?php $cnt = (int)$s['beneficiary_count']; ?>
          <span class="status-badge <?= $cnt > 0 ? 'badge-success' : 'badge-inactive' ?>">
            <?= $cnt ?> pupil<?= $cnt !== 1 ? 's' : '' ?>
          </span>
        </td>
        <td class="text-center">
          <span class="badge <?= ($s['status'] ?? 'Active') === 'Active' ? 'bg-success' : 'bg-danger' ?>">
            <?= htmlspecialchars($s['status'] ?? 'Active') ?>
          </span>
        </td>
        <td class="text-center">
          <div class="dropdown">
            <button class="btn-dots" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="nutritional.php?school_id=<?= $s['id'] ?>">
                  <i class="bi bi-eye me-1"></i> View Nutritional
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#"
                   onclick="openEdit(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>', '<?= addslashes($s['address']) ?>')">
                  <i class="bi bi-pencil me-1"></i> Edit
                </a>
              </li>
              <li>
                <form method="post" action="router.php" style="display:inline;">
                  <input type="hidden" name="module" value="school">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <input type="hidden" name="status" value="<?= ($s['status'] ?? 'Active') === 'Active' ? 'Inactive' : 'Active' ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="dropdown-item">
                    <i class="bi bi-power me-1"></i> <?= ($s['status'] ?? 'Active') === 'Active' ? 'Disable' : 'Enable' ?>
                  </button>
                </form>
              </li>
              <li>
                <a class="dropdown-item text-danger" href="#"
                   onclick="confirmDelete(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>')">
                  <i class="bi bi-trash me-1"></i> Delete
                </a>
              </li>
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
        <input type="hidden" name="module" value="school">
        <input type="hidden" name="action" value="add">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-building me-1"></i> Add School</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">School Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Panabo Central ES">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" placeholder="Panabo City, Davao del Norte">
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
        <input type="hidden" name="module" value="school">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Edit School</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">School Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit-name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" id="edit-address" class="form-control">
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
        <input type="hidden" name="module" value="school">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="del-id">
        <?= csrf_field() ?>
        <div class="modal-header" style="background:#DC3545;">
          <h5 class="modal-title">Delete School</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:.875rem;">Delete <strong id="del-name"></strong>? This will also remove all its beneficiaries and records.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-custom" style="background:#DC3545;">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/schools.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
