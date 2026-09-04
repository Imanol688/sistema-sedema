(() => {
  'use strict';

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = button.closest('.password-input')?.querySelector('input');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-pressed', String(show));
      button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });

  const loginForm = document.querySelector('.auth-shell .auth-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
      const username = loginForm.querySelector('#username');
      const password = loginForm.querySelector('#password');
      let valid = true;
      for (const input of [username, password]) {
        const error = document.querySelector(`#${input.id}-error`);
        input.removeAttribute('aria-invalid');
        error.textContent = '';
        if (!input.value.trim()) {
          input.setAttribute('aria-invalid', 'true');
          error.textContent = input.id === 'username' ? 'Ingresá tu usuario o correo.' : 'Ingresá tu contraseña.';
          valid = false;
        }
      }
      if (!valid) event.preventDefault();
    });
  }

  const resetForm = document.querySelector('[data-password-reset-form]');
  if (resetForm) {
    resetForm.addEventListener('submit', (event) => {
      const first = resetForm.querySelector('#password');
      const second = resetForm.querySelector('#password_confirmation');
      if (first.value.length < 12 || first.value !== second.value) {
        event.preventDefault();
        second.setCustomValidity(first.value !== second.value ? 'Las contraseñas no coinciden.' : '');
        first.reportValidity();
        second.reportValidity();
      } else {
        second.setCustomValidity('');
      }
    });
  }
})();

