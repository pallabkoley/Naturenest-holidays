/* =========================================================
   NATURE NEST HOLIDAYS — DASHBOARD (Client-side clock)
   ========================================================= */
(function () {
    'use strict';

    const clockEl = document.getElementById('nnServerTime');
    if (!clockEl) return;

    // Server time is rendered initially by PHP; JS just ticks it forward
    let start = new Date(clockEl.getAttribute('data-server-time') || Date.now());

    function tick() {
        start = new Date(start.getTime() + 1000);
        clockEl.textContent = start.toLocaleString('en-IN', {
            year: 'numeric', month: 'short', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    setInterval(tick, 1000);
})();