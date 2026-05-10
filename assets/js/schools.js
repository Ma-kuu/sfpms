// Schools — edit/delete modal helpers
function openEdit(id, name, address) {
  document.getElementById('edit-id').value      = id;
  document.getElementById('edit-name').value    = name;
  document.getElementById('edit-address').value = address;
  new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, name) {
  document.getElementById('del-id').value   = id;
  document.getElementById('del-name').textContent = name;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
