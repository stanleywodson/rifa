/**
 * Página do pedido: contagem regressiva da reserva, copiar Pix e
 * consulta periódica do status (recarrega quando o pagamento é confirmado).
 */

'use strict';

(function () {
  document.querySelectorAll('[data-copy-target]').forEach(btn =>
    btn.addEventListener('click', () => {
      const input = document.querySelector(btn.dataset.copyTarget);
      input.select();
      navigator.clipboard?.writeText(input.value);
      btn.innerHTML = '<i class="ti ti-check me-1"></i>Copiado';
    })
  );

  const page = document.getElementById('orderPage');
  if (!page || page.dataset.status !== 'awaiting_payment') return;

  const expires = new Date(page.dataset.expires).getTime();
  const countdown = page.querySelector('[data-countdown]');
  const tick = () => {
    const left = Math.max(0, Math.floor((expires - Date.now()) / 1000));
    if (countdown) countdown.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
  };
  tick();
  setInterval(tick, 1000);

  setInterval(async () => {
    try {
      const response = await fetch(page.dataset.statusUrl, { headers: { Accept: 'application/json' } });
      const data = await response.json();
      if (data.status !== 'awaiting_payment') window.location.reload();
    } catch (e) {}
  }, 4000);
})();
