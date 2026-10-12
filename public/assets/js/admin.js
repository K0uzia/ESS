// Tableau de bord : confirmation avant suppression (dialog natif), compteurs de caractères, menu mobile.
document.addEventListener('DOMContentLoaded', () => {
  initConfirm();
  initCounters();
  initMenu();
});

function initConfirm() {
  const dialog = document.getElementById('confirm-dialog');
  if (!dialog || dialog.dataset.ready || typeof dialog.showModal !== 'function') return;
  dialog.dataset.ready = '1';
  let pending = null;
  let opener = null;
  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form || form.dataset.confirmed) return;
    event.preventDefault();
    pending = form;
    opener = document.activeElement;
    dialog.querySelector('#confirm-text').textContent = form.dataset.confirm;
    dialog.showModal();
    dialog.querySelector('[data-confirm-cancel]').focus();
  });
  dialog.addEventListener('click', (event) => {
    if (event.target.closest('[data-confirm-ok]') && pending) {
      pending.dataset.confirmed = '1';
      dialog.close();
      pending.requestSubmit();
    } else if (event.target.closest('[data-confirm-cancel]')) {
      dialog.close();
    }
  });
  dialog.addEventListener('close', () => {
    if (pending && !pending.dataset.confirmed && opener) opener.focus();
    pending = null;
  });
}

function initCounters() {
  document.querySelectorAll('[data-count]').forEach((field) => {
    if (field.dataset.ready) return;
    field.dataset.ready = '1';
    const target = Number(field.dataset.count);
    const out = document.createElement('p');
    out.className = 'field__hint';
    out.id = `${field.id}-count`;
    out.setAttribute('aria-live', 'polite');
    field.after(out);
    field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), out.id].filter(Boolean).join(' '));
    const update = () => {
      const n = [...field.value].length;
      out.textContent = `${n} caractères (objectif : ${target === 60 ? 'moins de 60' : '140 à 160'})`;
    };
    field.addEventListener('input', update);
    update();
  });
}

function initMenu() {
  const toggle = document.querySelector('.shell__toggle');
  const nav = document.getElementById('admin-nav');
  if (!toggle || !nav || toggle.dataset.ready) return;
  toggle.dataset.ready = '1';
  toggle.hidden = false;
  document.documentElement.classList.add('js');
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    nav.toggleAttribute('data-open', open);
  });
  nav.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      toggle.setAttribute('aria-expanded', 'false');
      nav.removeAttribute('data-open');
      toggle.focus();
    }
  });
}
