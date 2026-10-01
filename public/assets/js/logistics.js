(() => {
  'use strict';

  const mode = document.querySelector('[data-logistics-mode]');
  const vehicleField = document.querySelector('[data-vehicle-field]');
  const vehicle = vehicleField?.querySelector('select');
  const syncMode = () => {
    if (!mode || !vehicleField || !vehicle) return;
    const delivery = mode.value === 'ENTREGA_DOMICILIO';
    vehicleField.hidden = !delivery;
    vehicle.required = delivery;
    if (!delivery) vehicle.value = '';
  };
  mode?.addEventListener('change', syncMode);
  syncMode();

  document.querySelectorAll('[data-confirm-state]').forEach((button) => {
    button.form?.addEventListener('submit', (event) => {
      const state = button.dataset.confirmState?.replaceAll('_', ' ').toLowerCase();
      if (!window.confirm(`¿Confirmar el cambio a ${state}?`)) event.preventDefault();
    });
  });

  document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
})();
