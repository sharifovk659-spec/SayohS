(() => {
  'use strict';

  const btn = document.querySelector('[data-admin-menu]');
  const sidebar = document.querySelector('.admin-sidebar');
  const overlay = document.querySelector('[data-admin-overlay]');

  const close = () => {
    sidebar?.classList.remove('is-open');
    overlay?.classList.remove('is-visible');
    overlay?.setAttribute('hidden', '');
  };

  const open = () => {
    sidebar?.classList.add('is-open');
    overlay?.classList.add('is-visible');
    overlay?.removeAttribute('hidden');
  };

  if (btn && sidebar) {
    btn.addEventListener('click', () => {
      if (sidebar.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    });
  }

  overlay?.addEventListener('click', close);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      close();
    }
  });
})();
