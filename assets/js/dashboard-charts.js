// Dashboard charts — reads from window.pageData
(function(){
  const D = window.pageData;
  if (!D) return;

  // Beneficiaries per School (paginated bar chart)
  const allLabels  = D.chartLabels;
  const allData    = D.chartValues;
  const PAGE_SIZE  = 10;
  let   page       = 0;
  const totalPages = Math.ceil(allLabels.length / PAGE_SIZE) || 1;
  const colors     = ['#2D6A4F','#40916C','#52B788','#74C69D','#95D5B2',
                      '#b7e4c7','#3a7d5f','#4a9070','#5aa881','#6dbf92'];

  const chart = new Chart(document.getElementById('beneficiaryChart'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Beneficiaries', data: [],
      backgroundColor: colors, borderRadius: 6, borderSkipped: false }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' pupils' } }
      },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 2, font: { size: 11 } }, grid: { color: '#f3f4f6' } },
        x: { ticks: { font: { size: 11 }, maxRotation: 30 }, grid: { display: false } }
      }
    }
  });

  function render() {
    const s = page * PAGE_SIZE;
    chart.data.labels = allLabels.slice(s, s + PAGE_SIZE);
    chart.data.datasets[0].data = allData.slice(s, s + PAGE_SIZE);
    chart.update();
    document.getElementById('chart-page-label').textContent = (page+1) + ' / ' + totalPages;
    document.getElementById('chart-prev').disabled = page === 0;
    document.getElementById('chart-next').disabled = page >= totalPages - 1;
  }

  document.getElementById('chart-prev').addEventListener('click', () => { page--; render(); });
  document.getElementById('chart-next').addEventListener('click', () => { page++; render(); });
  render();

  // Attendance Today Doughnut
  new Chart(document.getElementById('todayChart'), {
    type: 'doughnut',
    data: {
      labels: D.todayLabels,
      datasets: [{ data: D.todayValues, backgroundColor: ['#2D6A4F', '#DC3545'],
        borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: {
        legend: {
          position: 'right',
          labels: { font: { size: 11 }, padding: 12, usePointStyle: true }
        },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} pupils` } }
      }
    }
  });

  // Attendance Trend Line
  new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: {
      labels: D.attLabels,
      datasets: [{
        label: 'Attendance %',
        data: D.attValues,
        borderColor: '#2D6A4F',
        backgroundColor: 'rgba(45,106,79,.08)',
        borderWidth: 2,
        pointBackgroundColor: '#2D6A4F',
        pointRadius: 4,
        fill: true,
        tension: 0.35,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y}%` } }
      },
      scales: {
        y: {
          beginAtZero: true, max: 100,
          ticks: { callback: v => v + '%', font: { size: 11 } },
          grid: { color: '#f3f4f6' }
        },
        x: { ticks: { font: { size: 11 } }, grid: { display: false } }
      }
    }
  });
})();
