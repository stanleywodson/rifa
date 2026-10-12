/**
 * Formulários do SaaS: wizard (bs-stepper), máscaras (Cleave), slug automático.
 * Validação é feita no servidor; o wizard abre direto na etapa com erro.
 */

'use strict';

(function () {
  // Wizard bs-stepper (mesma marcação de wizard-ex-property-listing / auth-register-multisteps)
  document.querySelectorAll('.saas-stepper').forEach(function (el) {
    if (typeof Stepper === 'undefined') return;
    const stepper = new Stepper(el, { linear: false });
    el.querySelectorAll('.btn-next').forEach(btn => btn.addEventListener('click', () => stepper.next()));
    el.querySelectorAll('.btn-prev').forEach(btn => btn.addEventListener('click', () => stepper.previous()));

    const errorStep = parseInt(el.dataset.errorStep || '0', 10);
    if (errorStep > 0) stepper.to(errorStep + 1);
  });

  // Máscaras pt-BR
  if (typeof Cleave !== 'undefined') {
    document.querySelectorAll('[data-mask]').forEach(function (input) {
      const type = input.dataset.mask;
      if (type === 'cpf') {
        new Cleave(input, { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2], numericOnly: true });
      } else if (type === 'phone') {
        new Cleave(input, { delimiters: ['(', ') ', '-'], blocks: [0, 2, 5, 4], numericOnly: true });
      } else if (type === 'cep') {
        new Cleave(input, { delimiters: ['-'], blocks: [5, 3], numericOnly: true });
      } else if (type === 'money') {
        new Cleave(input, { numeral: true, numeralDecimalMark: ',', delimiter: '.', numeralDecimalScale: 2, numeralPositiveOnly: true });
      } else if (type === 'document') {
        // CPF ou CNPJ conforme a quantidade de dígitos
        input.addEventListener('input', function () {
          const d = input.value.replace(/\D/g, '').slice(0, 14);
          input.value =
            d.length <= 11
              ? d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
              : d.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        });
      }
    });
  }

  // Slug automático a partir do nome (enquanto o usuário não editar o slug)
  document.querySelectorAll('[data-slug-source]').forEach(function (source) {
    const target = document.querySelector(source.dataset.slugSource);
    if (!target) return;
    let touched = target.value !== '';
    target.addEventListener('input', () => (touched = true));
    source.addEventListener('input', function () {
      if (touched) return;
      target.value = source.value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60);
    });
  });

  // Confirmação antes de enviar formulários destrutivos: <form data-confirm="...">
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirmed) return;
      e.preventDefault();
      const done = () => {
        form.dataset.confirmed = '1';
        form.submit();
      };
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: form.dataset.confirm,
          text: form.dataset.confirmText || '',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: form.dataset.confirmButton || 'Confirmar',
          cancelButtonText: 'Cancelar',
          customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
          buttonsStyling: false
        }).then(result => result.isConfirmed && done());
      } else if (window.confirm(form.dataset.confirm)) {
        done();
      }
    });
  });

  // Escolha do gateway de recebimento: <input data-gateway-option> mostra o <div data-gateway-panel>
  document.querySelectorAll('[data-gateway-option]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      document.querySelectorAll('[data-gateway-option]').forEach(r => {
        r.closest('.custom-option')?.classList.toggle('checked', r.checked);
      });
      document.querySelectorAll('[data-gateway-panel]').forEach(panel => {
        panel.classList.toggle('d-none', panel.dataset.gatewayPanel !== radio.value);
      });
    });
  });
})();
