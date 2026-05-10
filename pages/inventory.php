<?php
// pages/inventory.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$user     = Auth::user();
$isSA     = Auth::isSuperAdmin();
$schoolId = $isSA ? null : (int)$user['school_id'];
$pdo      = getPDO();
$schools  = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();

$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        Inventory::create([
            'school_id'           => $isSA ? (int)$_POST['school_id'] : $schoolId,
            'item_name'           => trim($_POST['item_name']),
            'unit'                => trim($_POST['unit']),
            'quantity'            => (float)$_POST['quantity'],
            'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10),
        ]);
    } elseif ($action === 'edit') {
        Inventory::update((int)$_POST['id'], [
            'school_id'           => $isSA ? (int)$_POST['school_id'] : $schoolId,
            'item_name'           => trim($_POST['item_name']),
            'unit'                => trim($_POST['unit']),
            'quantity'            => (float)$_POST['quantity'],
            'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10),
        ]);
    } elseif ($action === 'delete') {
        Inventory::delete((int)$_POST['id']);
    }
    header('Location: inventory.php');
    exit;
}

$schFilter = $isSA ? (($_GET['school_id'] ?? '') ?: null) : $schoolId;
$items     = Inventory::getAll($schFilter);
$pageTitle = 'Inventory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="table-wrapper">
  <div class="table-header">
    <form method="get" action="inventory.php" class="filter-bar flex-grow-1">
      <?php if ($isSA): ?>
      <select name="school_id" class="form-select">
        <option value="">All Schools</option>
        <?php foreach ($schools as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($schFilter == $s['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary-custom"><i class="bi bi-search"></i> Filter</button>
      <a href="inventory.php" class="btn-outline-custom">Clear</a>
      <?php endif; ?>
    </form>
    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addInvModal">
      <i class="bi bi-plus-lg"></i> Add Item
    </button>
  </div>

  <div class="table-responsive">
    <table class="table" id="inventoryTable">
      <thead>
        <tr>
          <th>Item</th>
          <th>School</th>
          <th class="text-center">Stock</th>
          <th class="text-center">Unit</th>
          <th class="text-center">Status</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No inventory items.</td></tr>
        <?php else: ?>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['item_name']) ?></td>
          <td style="font-size:.82rem;"><?= htmlspecialchars($it['school_name']) ?></td>
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
</div>
<p class="text-muted mt-2" style="font-size:.78rem;"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></p>

<!-- Add Modal -->
<div class="modal fade" id="addInvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-box-seam-fill me-2"></i>Add Inventory Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="inventory.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body" id="addInvBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-add-inv" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Save Item
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editInvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Edit Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="inventory.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editInvId">
        <div class="modal-body" id="editInvBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-save-inv" class="btn-primary-custom">
            <i class="bi bi-check-lg"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteInvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
    <div class="modal-content">
      <div class="modal-header" style="background:#DC3545;">
        <h5 class="modal-title" style="color:#fff;"><i class="bi bi-trash-fill me-2"></i>Delete Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="inventory.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteInvId">
        <div class="modal-body">
          <p style="margin:0;font-size:.9rem;">Delete <strong id="deleteInvName"></strong> from inventory?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btn-confirm-delete-inv"
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
<script src="../assets/js/inventory.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
