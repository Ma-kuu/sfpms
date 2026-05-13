<?php
require_once __DIR__ . '/../controllers/beneficiaries.php';
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
          <th>
            <a href="<?= sortUrl('lrn', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              LRN <?= sortIcon('lrn', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th>
            <a href="<?= sortUrl('last_name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Name <?= sortIcon('last_name', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th>
            <a href="<?= sortUrl('school_name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              School <?= sortIcon('school_name', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('grade_level', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Grade <?= sortIcon('grade_level', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">Section</th>
          <th class="text-center">
            <a href="<?= sortUrl('status', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Status <?= sortIcon('status', $sortBy, $sortDir) ?>
            </a>
          </th>
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
          <td class="text-center" style="font-size:.85rem;">
            <?= htmlspecialchars($r['section']) ?>
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
  <div class="px-3 pb-3">
    <?= renderPagination($pag) ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/modals.php'; ?>

<script>
window.pageData = {
  schools:    <?= json_encode($schools) ?>,
  isSA:       <?= $isSA ? 'true' : 'false' ?>,
  mySchoolId: <?= $schoolId ?? 'null' ?>
};
</script>
<script src="../assets/js/beneficiaries.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
