/**
 * Grade pública de números: paginação, seleção (persistida na sessão do navegador),
 * atualização periódica dos status e atalhos de quantidade.
 */

'use strict';

(function () {
  // Atalhos do campo "números aleatórios"
  const qty = document.getElementById('quantidade');
  document.querySelectorAll('[data-qty]').forEach(btn =>
    btn.addEventListener('click', () => {
      qty.value = Math.max(parseInt(qty.min || '1', 10), (parseInt(qty.value, 10) || 0) + parseInt(btn.dataset.qty, 10));
    })
  );
  document.querySelectorAll('[data-qty-set]').forEach(btn =>
    btn.addEventListener('click', () => (qty.value = (parseInt(qty.value, 10) || 0) + parseInt(btn.dataset.qtySet, 10)))
  );

  // Copiar link
  document.querySelectorAll('[data-copy]').forEach(btn =>
    btn.addEventListener('click', () => {
      navigator.clipboard?.writeText(btn.dataset.copy);
      const old = btn.innerHTML;
      btn.innerHTML = '<i class="ti ti-check me-1"></i>Copiado';
      setTimeout(() => (btn.innerHTML = old), 2000);
    })
  );

  const card = document.getElementById('raffleGridCard');
  if (!card) return;

  const grid = card.querySelector('[data-grid]');
  const pagesEl = card.querySelector('[data-grid-pages]');
  const cart = card.querySelector('[data-cart]');
  const onSale = card.dataset.onSale === '1';
  const price = parseInt(card.dataset.price, 10);
  const max = parseInt(card.dataset.max, 10);
  const storageKey = card.dataset.key;
  const start = parseInt(card.dataset.start, 10);
  const last = parseInt(card.dataset.last, 10);
  const size = parseInt(card.dataset.size, 10);
  const money = v => (v / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

  let selected = new Set(JSON.parse(sessionStorage.getItem(storageKey) || '[]'));
  let state = { page: 1, pages: 1, from: 0, digits: 1, statuses: '' };
  let timer = null;

  const pad = n => String(n).padStart(state.digits, '0');
  const save = () => sessionStorage.setItem(storageKey, JSON.stringify([...selected]));

  function renderCart() {
    if (!cart) return;
    const list = [...selected].sort((a, b) => a - b);
    cart.classList.toggle('d-none', list.length === 0);
    cart.querySelector('[data-cart-count]').textContent = list.length;
    cart.querySelector('[data-cart-total]').textContent = money(list.length * price);
    cart.querySelector('[data-cart-numbers]').textContent = list.map(pad).join(', ');
  }

  function renderPages() {
    pagesEl.innerHTML = '';
    if (state.pages <= 1) return;
    for (let p = 1; p <= state.pages; p++) {
      const first = start + (p - 1) * size;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn ' + (p === state.page ? 'btn-primary' : 'btn-outline-primary');
      btn.textContent = pad(first) + '–' + pad(Math.min(first + size - 1, last));
      btn.addEventListener('click', () => load(p));
      pagesEl.appendChild(btn);
    }
  }

  function renderGrid() {
    const frag = document.createDocumentFragment();
    for (let i = 0; i < state.statuses.length; i++) {
      const n = state.from + i;
      const status = state.statuses[i];
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = pad(n);
      btn.dataset.number = n;
      if (status === '2') {
        btn.className = 'btn btn-success';
        btn.disabled = true;
        btn.title = 'Pago';
      } else if (status === '1') {
        btn.className = 'btn btn-warning';
        btn.disabled = true;
        btn.title = 'Reservado';
        selected.delete(n);
      } else {
        btn.className = 'btn ' + (selected.has(n) ? 'btn-primary' : 'btn-label-secondary');
        btn.disabled = !onSale;
      }
      frag.appendChild(btn);
    }
    grid.replaceChildren(frag);
    save();
    renderCart();
  }

  async function load(page) {
    try {
      const response = await fetch(card.dataset.gridUrl + '?page=' + page, { headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      state = await response.json();
      renderPages();
      renderGrid();
    } catch (e) {
      /* rede instável: tenta no próximo ciclo */
    }
  }

  grid.addEventListener('click', e => {
    const btn = e.target.closest('button[data-number]');
    if (!btn || btn.disabled) return;
    const n = parseInt(btn.dataset.number, 10);
    if (selected.has(n)) {
      selected.delete(n);
      btn.className = 'btn btn-label-secondary';
    } else {
      if (selected.size >= max) return;
      selected.add(n);
      btn.className = 'btn btn-primary';
    }
    save();
    renderCart();
  });

  // Ir para um número específico
  const search = card.querySelector('[data-grid-search]');
  card.querySelector('[data-grid-search-btn]')?.addEventListener('click', () => {
    const n = parseInt(search.value, 10);
    if (isNaN(n) || n < start || n > last) return;
    load(Math.floor((n - start) / size) + 1);
  });

  cart?.querySelector('[data-cart-clear]').addEventListener('click', () => {
    selected.clear();
    renderGrid();
  });
  cart?.querySelector('[data-cart-go]').addEventListener('click', () => {
    const list = [...selected].sort((a, b) => a - b);
    if (list.length) window.location = card.dataset.checkoutUrl + '?numeros=' + list.join(',');
  });

  load(1);
  // Atualização periódica (somente com a aba visível)
  timer = setInterval(() => document.visibilityState === 'visible' && load(state.page), 5000);
  window.addEventListener('beforeunload', () => clearInterval(timer));
})();
