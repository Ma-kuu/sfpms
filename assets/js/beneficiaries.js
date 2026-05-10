// Beneficiaries — modal form logic
(function(){
  const P = window.pageData;
  if (!P) return;

  const schools    = P.schools;
  const isSA       = P.isSA;
  const mySchoolId = P.mySchoolId;

  function renderFields(data = {}) {
    const gradeOpts = ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6']
      .map(g => `<option value="${g}" ${data.grade_level===g?'selected':''}>${g}</option>`).join('');

    const schoolOpts = isSA
      ? schools.map(s => `<option value="${s.id}" ${data.school_id==s.id?'selected':''}>${s.name}</option>`).join('')
      : `<option value="${mySchoolId}" selected></option>`;

    return `
      <div class="mb-3"><div class="form-section-label">Basic Information</div></div>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">LRN</label>
          <input type="text" name="lrn" class="form-control" maxlength="12"
                 value="${data.lrn||''}" placeholder="12-digit LRN" required></div>
        <div class="col-md-4"><label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-control"
                 value="${data.first_name||''}" required></div>
        <div class="col-md-4"><label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-control"
                 value="${data.last_name||''}" required></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Birthdate</label>
          <input type="date" name="birthdate" class="form-control" value="${data.birthdate||''}"></div>
        <div class="col-md-4"><label class="form-label">Sex</label>
          <select name="sex" class="form-select">
            <option value="Male"   ${data.sex==='Male'?'selected':''}>Male</option>
            <option value="Female" ${data.sex==='Female'?'selected':''}>Female</option>
          </select></div>
        <div class="col-md-4"><label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="Active"   ${data.status==='Active'?'selected':''}>Active</option>
            <option value="Inactive" ${data.status==='Inactive'?'selected':''}>Inactive</option>
          </select></div>
      </div>
      <div class="mb-3"><div class="form-section-label">School & Grade</div></div>
      <div class="row g-3">
        ${isSA ? `
        <div class="col-md-4"><label class="form-label">School</label>
          <select name="school_id" class="form-select" required>${schoolOpts}</select></div>` : `<input type="hidden" name="school_id" value="${mySchoolId}">`}
        <div class="col-md-4"><label class="form-label">Grade Level</label>
          <select name="grade_level" class="form-select">${gradeOpts}</select></div>
        <div class="col-md-4"><label class="form-label">Section</label>
          <input type="text" name="section" class="form-control" value="${data.section||''}"></div>
      </div>
    `;
  }

  window.openEditModal = function(row) {
    document.getElementById('edit_id').value = row.id;
    document.getElementById('editBody').innerHTML = renderFields(row);
    new bootstrap.Modal(document.getElementById('editModal')).show();
  };

  window.openDeleteModal = function(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
  };

  document.getElementById('addModal').addEventListener('show.bs.modal', function() {
    const body = this.querySelector('.modal-body');
    if (!body.querySelector('input[name="lrn"]')) {
      body.innerHTML = renderFields({});
    }
  });
})();
