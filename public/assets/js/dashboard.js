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
    if (event.key === 'Escape') { setSidebar(false); closeUserMenus(); }
  });

  const closeUserMenus = (except = null) => {
    document.querySelectorAll('[data-user-menu]').forEach((menu) => {
      if (menu === except) return;
      const trigger = menu.querySelector('[data-user-menu-trigger]');
      const panel = menu.querySelector('[data-user-menu-panel]');
      if (trigger && panel) {
        trigger.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
      }
    });
  };

  document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    const trigger = menu.querySelector('[data-user-menu-trigger]');
    const panel = menu.querySelector('[data-user-menu-panel]');
    if (!trigger || !panel) return;
    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      const open = trigger.getAttribute('aria-expanded') === 'true';
      closeUserMenus(menu);
      trigger.setAttribute('aria-expanded', String(!open));
      panel.hidden = open;
    });
    panel.addEventListener('click', (event) => event.stopPropagation());
  });

  document.addEventListener('click', () => closeUserMenus());

})();
