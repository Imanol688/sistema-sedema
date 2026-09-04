(() => {
  'use strict';

  document.querySelectorAll('[data-auto-submit]').forEach((field) => {
    field.addEventListener('change', () => field.form?.submit());
  });

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || '¿Confirmar esta operación?')) {
        event.preventDefault();
      }
    });
  });

  const source = document.querySelector('#sourceWarehouse');
  const target = document.querySelector('#targetWarehouse');
  if (source && target) {
    const updateTargets = () => {
      Array.from(target.options).forEach((option) => {
        option.disabled = option.value !== '' && option.value === source.value;
      });
      if (target.value === source.value) target.value = '';
    };
    source.addEventListener('change', updateTargets);
    updateTargets();
  }
})();
