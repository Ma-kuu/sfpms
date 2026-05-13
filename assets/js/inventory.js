// Inventory — modal form logic
(function(){
  const P = window.pageData;
  if (!P) return;

  const schools = P.schools;
  const isSA    = P.isSA;
  const schId = P.viewSchoolId || P.mySchoolId;

  function invFields(d = {}) {
    return `
      <input type="hidden" name="school_id" value="${d.school_id || schId}">
      <div class="mb-3"><label class="form-label">Item Name</label>
        <input type="text" name="item_name" class="form-control" value="${d.item_name||''}" required></div>
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label">Quantity</label>
          <input type="number" name="quantity" step="0.01" min="0" class="form-control"
                 value="${d.quantity||0}" required></div>
        <div class="col-6"><label class="form-label">Unit</label>
          <input type="text" name="unit" class="form-control" value="${d.unit||'pcs'}" required></div>
      </div>
      <div class="mb-1"><label class="form-label">Low Stock Threshold</label>
        <input type="number" name="low_stock_threshold" step="0.01" min="0"
               class="form-control" value="${d.low_stock_threshold||10}">
        <div class="form-text">Alert shown when quantity falls below this value.</div></div>
    `;
  }

  window.openEditInv = function(row) {
    document.getElementById('editId').value = row.id;
    document.getElementById('editModule').value = 'inventory';
    document.getElementById('editBody').innerHTML = invFields(row);
    new bootstrap.Modal(document.getElementById('editModal')).show();
  };

  window.openDeleteInv = function(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModule').value = 'inventory';
    document.getElementById('deleteName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
  };

  document.getElementById('addModal')?.addEventListener('show.bs.modal', function() {
    if (window.location.pathname.includes('inventory.php')) {
        const b = document.getElementById('addBody');
        document.getElementById('addModule').value = 'inventory';
        if (!b.querySelector('input[name="item_name"]')) b.innerHTML = invFields({});
    }
  });
})();
