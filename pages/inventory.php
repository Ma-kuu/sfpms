<?php
require_once __DIR__ . '/../controllers/inventory.php';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$viewSchoolId): ?>
<?php 
$summaries = Inventory::getSchoolSummaries($isSA ? null : $schoolId); 
$sortBy  = in_array($_GET['sort'] ?? '', ['school_name', 'total_items', 'low_stock_items']) ? $_GET['sort'] : 'school_name';
$sortDir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
sortArray($summaries, $sortBy, $sortDir);
?>
<div class="table-wrapper">
  <div class="table-header">
    <div style="font-weight:600;font-size:.95rem;">School Inventory Overview</div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>
            <a href="<?= sortUrl('school_name', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              School Name <?= sortIcon('school_name', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('total_items', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Total Distinct Items <?= sortIcon('total_items', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('low_stock_items', $sortBy, $sortDir) ?>" class="text-decoration-none text-inherit">
              Stock Status <?= sortIcon('low_stock_items', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($summaries)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No schools found.</td></tr>
        <?php else: ?>
        <?php foreach ($summaries as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['school_name']) ?></td>
          <td class="text-center"><strong><?= number_format($s['total_items']) ?></strong> items</td>
          <td class="text-center">
            <?php if ($s['low_stock_items'] > 0): ?>
              <span class="status-badge badge-low"><i class="bi bi-exclamation-circle me-1"></i><?= number_format($s['low_stock_items']) ?> Low Stock</span>
            <?php else: ?>
              <span class="status-badge badge-ok">All Good</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <a href="inventory.php?view_school=<?= $s['school_id'] ?>" class="btn-primary-custom text-decoration-none px-3 py-1" style="font-size:0.85rem;">
              View Inventory
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>

<div class="table-wrapper">
  <div class="table-header">
    <form method="get" action="inventory.php" class="filter-bar flex-grow-1">
      <input type="hidden" name="view_school" value="<?= htmlspecialchars($viewSchoolId) ?>">
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="low" <?= $statusFilter === 'low' ? 'selected' : '' ?>>Low Stock</option>
        <option value="adequate" <?= $statusFilter === 'adequate' ? 'selected' : '' ?>>Adequate Stock</option>
      </select>
      <button type="submit" class="btn-primary-custom"><i class="bi bi-search"></i> Filter</button>
      <a href="inventory.php?view_school=<?= htmlspecialchars($viewSchoolId) ?>" class="btn-outline-custom">Clear</a>
      <a href="inventory.php" class="btn-outline-custom"><i class="bi bi-arrow-left"></i> Back</a>
    </form>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addInvModal">
      <i class="bi bi-plus-lg"></i> Add Item
    </button>
  </div>

  <div class="table-responsive">
    <table class="table" id="inventoryTable">
      <thead>
        <tr>
          <th>
            <a href="<?= sortUrl('item_name', $sortBy, $sortDir) ?>&view_school=<?= $viewSchoolId ?>" class="text-decoration-none text-inherit">
              Item <?= sortIcon('item_name', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('quantity', $sortBy, $sortDir) ?>&view_school=<?= $viewSchoolId ?>" class="text-decoration-none text-inherit">
              Stock <?= sortIcon('quantity', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">
            <a href="<?= sortUrl('unit', $sortBy, $sortDir) ?>&view_school=<?= $viewSchoolId ?>" class="text-decoration-none text-inherit">
              Unit <?= sortIcon('unit', $sortBy, $sortDir) ?>
            </a>
          </th>
          <th class="text-center">Status</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No inventory items.</td></tr>
        <?php else: ?>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['item_name']) ?></td>
          <td class="text-center">
            <strong><?= number_format($it['quantity'], 0) ?></strong>
            <span class="text-muted" style="font-size:.75rem;">/ <?= number_format($it['low_stock_threshold'], 0) ?> min</span>
          </td>
          <td class="text-center"><?= htmlspecialchars($it['unit']) ?></td>
          <td class="text-center">
            <?php if ($it['is_low']): ?>
              <span class="status-badge badge-low"><i class="bi bi-exclamation-circle me-1"></i>LOW STOCK</span>
            <?php else: ?>
              <span class="status-badge badge-ok">OK</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn-dots" data-bs-toggle="dropdown" id="ddInv<?= $it['id'] ?>">
                <i class="bi bi-three-dots"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" style="font-size:.855rem;min-width:130px;">
                <li>
                  <a class="dropdown-item" href="#"
                     onclick="openEditInv(<?= htmlspecialchars(json_encode($it)) ?>); return false;">
                    <i class="bi bi-pencil me-2 text-primary"></i>Edit
                  </a>
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="#"
                     onclick="openDeleteInv(<?= $it['id'] ?>, '<?= htmlspecialchars(addslashes($it['item_name'])) ?>'); return false;">
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
  mySchoolId: <?= $schoolId ?? 'null' ?>,
  viewSchoolId: <?= $viewSchoolId ? json_encode($viewSchoolId) : 'null' ?>
};
</script>
<script src="../assets/js/inventory.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
