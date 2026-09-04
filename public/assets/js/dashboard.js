(() => {
  'use strict';

  const page = document.querySelector('.dashboard-page');
  const openButton = document.querySelector('[data-sidebar-open]');
  const closeButton = document.querySelector('[data-sidebar-close]');

  if (!page || !openButton || !closeButton) return;

  const setSidebar = (open) => {
    page.classList.toggle('sidebar-is-open', open);
    openButton.setAttribute('aria-expanded', String(open));
  };

  openButton.addEventListener('click', () => setSidebar(true));
  closeButton.addEventListener('click', () => setSidebar(false));

  document.querySelectorAll('.sidebar-nav a').forEach((link) => {
    link.addEventListener('click', () => setSidebar(false));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebar(false);
  });
})();
