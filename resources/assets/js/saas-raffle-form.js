/**
 * Formulário de rifa: editor Quill (descrição), Flatpickr (datas), Dropzone
 * com upload real (tela de edição), prévia de arrecadação e modelo de regulamento.
 */

'use strict';

import Dropzone from 'dropzone/dist/dropzone';

Dropzone.autoDiscover = false;

(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  // Quill — mesma configuração do product-add
  const editorEl = document.getElementById('descriptionEditor');
  if (editorEl && typeof Quill !== 'undefined') {
    const quill = new Quill(editorEl, { modules: { toolbar: '#descriptionToolbar' }, placeholder: 'Conte sobre o prêmio e a causa', theme: 'snow' });
    const hidden = document.getElementById('description');
    document.getElementById('raffleForm').addEventListener('submit', () => {
      hidden.value = quill.getText().trim() ? quill.root.innerHTML : '';
    });
  }

  // Datas
  if (typeof flatpickr !== 'undefined') {
    document.querySelectorAll('[data-datetime]').forEach(el =>
      flatpickr(el, { enableTime: true, time_24hr: true, dateFormat: 'Y-m-d H:i', altInput: true, altFormat: 'd/m/Y H:i', minDate: 'today' })
    );
  }

  // Prévia de arrecadação
  const total = document.getElementById('total_numbers');
  const price = document.getElementById('price');
  const preview = document.getElementById('revenuePreview');
  const parseMoney = v => parseFloat(String(v || '0').replace(/\./g, '').replace(',', '.')) || 0;
  const updatePreview = () => {
    const value = (parseInt(total.value, 10) || 0) * parseMoney(price.value);
    preview.textContent = 'Arrecadação máxima (todos os números vendidos): ' + value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  };
  if (total && price && preview) {
    total.addEventListener('input', updatePreview);
    price.addEventListener('input', updatePreview);
    updatePreview();
  }

  // Modelo de regulamento
  document.querySelector('[data-regulation-template]')?.addEventListener('click', () => {
    const textarea = document.getElementById('regulation');
    if (textarea.value.trim() && !confirm('Substituir o regulamento atual pelo modelo?')) return;
    const method = document.querySelector('input[name="draw_method"]:checked')?.value || 'federal';
    const rule = document.getElementById('unsold_rule');
    const start = document.querySelector('input[name="number_start"]:checked')?.value || '0';
    const qty = parseInt(total.value, 10) || 0;
    const methodText = {
      federal: 'O número contemplado será obtido a partir do 1º prêmio da Loteria Federal do concurso da data do sorteio, pela fórmula (1º prêmio mod ' + qty + ') + ' + start + '.',
      commit_reveal: 'Na aprovação da rifa, a plataforma gera uma semente secreta e publica o seu hash SHA-256. No sorteio, a semente é revelada e combinada com o 1º prêmio da Loteria Federal do concurso da data do sorteio; o número é (hash(semente|resultado) mod ' + qty + ') + ' + start + '.',
      manual: 'O sorteio será realizado ao vivo, com transmissão pública e gravação disponibilizada na prova do sorteio.'
    }[method];
    textarea.value = [
      'REGULAMENTO — ' + (document.getElementById('title').value || 'RIFA'),
      '',
      '1. PROMOTORA: ' + document.getElementById('tenantName').value + ', responsável pela rifa e pela entrega do prêmio.',
      '2. AUTORIZAÇÃO: ' + (document.getElementById('authorization_number').value || '[número da autorização]') + '.',
      '3. PRÊMIO: ' + (document.getElementById('prize_title').value || '[prêmio]') + '.',
      '4. NÚMEROS: serão comercializados ' + qty + ' números, ao valor de R$ ' + (price.value || '0,00') + ' cada, exclusivamente via Pix.',
      '5. PARTICIPAÇÃO: permitida apenas a maiores de 18 anos. O número só é considerado vendido após a confirmação do pagamento.',
      '6. SORTEIO: ' + methodText,
      '7. NÚMERO NÃO VENDIDO: ' + rule.options[rule.selectedIndex].text + '.',
      '8. ENTREGA: o prêmio será entregue em até 30 dias após o sorteio, sem ônus ao contemplado.',
      '9. CANCELAMENTO: em caso de cancelamento da rifa, todos os valores pagos serão reembolsados integralmente.',
      '10. As regras do sorteio não podem ser alteradas após a publicação.'
    ].join('\n');
  });

  // Dropzone (edição) — upload real para painel.rifas.media.store
  const dzEl = document.getElementById('raffleDropzone');
  if (dzEl) {
    const list = document.getElementById('mediaList');
    const dz = new Dropzone(dzEl, {
      url: dzEl.dataset.url,
      paramName: 'file',
      maxFilesize: 5,
      acceptedFiles: 'image/jpeg,image/png,image/webp',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      dictInvalidFileType: 'Formato não permitido',
      dictFileTooBig: 'Arquivo maior que {{maxFilesize}} MB'
    });
    dz.on('success', (file, media) => {
      dz.removeFile(file);
      const col = document.createElement('div');
      col.className = 'col-6 col-md-3';
      col.dataset.media = '';
      col.innerHTML =
        '<div class="card shadow-none border"><img src="' + media.url + '" class="card-img-top object-fit-cover" style="height:120px" alt="">' +
        '<div class="card-body p-2 text-center"><button type="button" class="btn btn-sm btn-text-danger" data-delete-media="' + media.delete_url + '"><i class="ti ti-trash me-1"></i>Remover</button></div></div>';
      list.appendChild(col);
    });
    dz.on('error', (file, message) => {
      const text = typeof message === 'string' ? message : message.message || 'Falha no upload';
      file.previewElement.querySelector('.dz-error-message span').textContent = text;
    });
  }

  document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-delete-media]');
    if (!btn) return;
    const response = await fetch(btn.dataset.deleteMedia, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
    if (response.ok) btn.closest('[data-media]').remove();
  });
})();
