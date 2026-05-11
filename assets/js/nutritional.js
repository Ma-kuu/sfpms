// Nutritional records — modal form logic
(function(){
  const P = window.pageData;
  if (!P) return;

  const bens = P.beneficiaries;

  function nutFields(d = {}) {
    const benOpts = bens.map(b =>
      `<option value="${b.id}" ${d.beneficiary_id==b.id?'selected':''}>${b.full_name} (${b.lrn})</option>`
    ).join('');

    return `
      <div class="mb-3"><label class="form-label">Beneficiary</label>
        <select name="beneficiary_id" class="form-select" required>${benOpts}</select></div>
      <div class="mb-3"><label class="form-label">Record Date</label>
        <input type="date" name="record_date" class="form-control" value="${d.record_date||''}" required></div>
      <div class="row g-3">
        <div class="col-6"><label class="form-label">Weight (kg)</label>
          <input type="number" name="weight_kg" step="0.01" min="5" max="200"
                 class="form-control" value="${d.weight_kg||''}" required></div>
        <div class="col-6"><label class="form-label">Height (cm)</label>
          <input type="number" name="height_cm" step="0.01" min="50" max="250"
                 class="form-control" value="${d.height_cm||''}" required></div>
      </div>
    `;
  }

  window.openEditNut = function(row) {
    document.getElementById('editId').value = row.id;
    document.getElementById('editModule').value = 'nutritional';
    document.getElementById('editBody').innerHTML = nutFields(row);
    new bootstrap.Modal(document.getElementById('editModal')).show();
  };

  window.openDeleteNut = function(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModule').value = 'nutritional';
    document.getElementById('deleteName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
  };

  document.getElementById('addModal')?.addEventListener('show.bs.modal', function() {
    if (window.location.pathname.includes('nutritional.php')) {
        const b = document.getElementById('addBody');
        document.getElementById('addModule').value = 'nutritional';
        if (!b.querySelector('select')) b.innerHTML = nutFields({});
    }
  });
})();
