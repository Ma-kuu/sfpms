// Live clock (topbar)
(function(){
  function tick(){
    const el = document.getElementById('live-clock');
    if (el) {
      el.textContent = new Date().toLocaleTimeString('en-PH', {
        hour:'2-digit', minute:'2-digit', second:'2-digit'
      });
    }
  }
  tick();
  setInterval(tick, 1000);
})();
