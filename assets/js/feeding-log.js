// Feeding log — session modal logic
(function(){
  const P = window.pageData;
  if (!P) return;

  const schools = P.schools;
  const isSA    = P.isSA;
  const schId   = P.mySchoolId;

  function sessionFields(d = {}) {
    const schOpts = isSA
      ? schools.map(s => `<option value="${s.id}" ${d.school_id==s.id?'selected':''}>${s.name}</option>`).join('')
      : `<option value="${schId}" selected></option>`;

    const mealOpts = ['Breakfast','Lunch','Snack']
      .map(m => `<option value="${m}" ${d.meal_type===m?'selected':''}>${m}</option>`).join('');

    return `
      ${isSA ? `<div class="mb-3"><label class="form-label">School</label>
        <select name="school_id" class="form-select" required>${schOpts}</select></div>`
        : `<input type="hidden" name="school_id" value="${schId}">`}
      <div class="mb-3"><label class="form-label">Session Date</label>
        <input type="date" name="session_date" class="form-control" value="${d.session_date||''}" required></div>
      <div class="mb-3"><label class="form-label">Meal Type</label>
        <select name="meal_type" class="form-select">${mealOpts}</select></div>
      <div class="mb-1"><label class="form-label">Remarks (optional)</label>
        <textarea name="remarks" class="form-control" rows="2">${d.remarks||''}</textarea></div>
    `;
  }

  window.openEditSession = function(row) {
    document.getElementById('editId').value = row.id;
    document.getElementById('editModule').value = 'feeding_session';
    document.getElementById('editBody').innerHTML = sessionFields(row);
    new bootstrap.Modal(document.getElementById('editModal')).show();
  };

  window.openDeleteSession = function(id, label) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModule').value = 'feeding_session';
    document.getElementById('deleteName').textContent = label;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
  };

  document.getElementById('addModal')?.addEventListener('show.bs.modal', function() {
    if (window.location.pathname.includes('feeding_log.php') && !document.querySelector('form[action="feeding_log.php"]')) {
        const b = document.getElementById('addBody');
        document.getElementById('addModule').value = 'feeding_session';
        if (!b.querySelector('select[name="meal_type"]')) b.innerHTML = sessionFields({});
    }
  });

  // Auto-dismiss success banner
  const banner = document.getElementById('msgBanner');
  if (banner) setTimeout(() => banner.remove(), 4000);
})();
