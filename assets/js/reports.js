// Reports — toggle date fields based on report type
function toggleDates(val) {
  const show = val !== 'inventory';
  ['dateFromWrap','dateToWrap'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = show ? '' : 'none';
  });
}

const reportType = document.getElementById('reportType');
if (reportType) toggleDates(reportType.value || '');
