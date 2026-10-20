// Tableau de bord : amélioration progressive. Sans JavaScript, chaque écran reste utilisable
// (listes complètes, formulaires sur leur propre page, texte modifiable dans un champ simple).
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  initMenu();
  initConfirm();
  initModal();
  initPreview();
  initPasswordToggle();
  initRoleToggle();
  enhance(document);
});

/** Comportements d'un fragment de page ; rejoué sur le contenu chargé dans la fenêtre ou remplacé après enregistrement. */
function enhance(root) {
  initCounters(root);
  initFilters(root);
  initRichEditors(root);
  initPhotoPick(root);
  initLinkFields(root);
  initTabsets(root);
  initAddresses(root);
  root.querySelectorAll('[data-password-toggle]').forEach((button) => { button.hidden = false; });
}

// Adresse d'un adhérent : suggestions de la Géoplateforme (via /admin/adresses), liste déroulante accessible
// (combobox ARIA) et phrase d'état sous le champ. Sans JavaScript : champ texte simple, position cherchée à l'enregistrement.
function initAddresses(root) {
  root.querySelectorAll('[data-address]').forEach((box) => {
    if (box.dataset.ready) return;
    box.dataset.ready = '1';
    const input = box.querySelector('[data-address-input]');
    const list = box.querySelector('[data-address-list]');
    const status = box.parentElement.querySelector('[data-address-status]');
    let timer = 0;
    let active = -1;
    let request = 0;
    const setStatus = (text, kind = '') => {
      status.textContent = text;
      status.className = `geo-status${kind ? ` geo-status--${kind}` : ''}`;
    };
    const options = () => [...list.querySelectorAll('[role="option"]')];
    const close = () => {
      list.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      active = -1;
    };
    const highlight = (index) => {
      const opts = options();
      active = (index + opts.length) % opts.length;
      opts.forEach((o, i) => o.setAttribute('aria-selected', String(i === active)));
      input.setAttribute('aria-activedescendant', opts[active].id);
      opts[active].scrollIntoView({ block: 'nearest' });
    };
    const choose = (option) => {
      input.value = option.dataset.value;
      close();
      setStatus('Adresse reconnue : elle sera placée sur la carte à l\'enregistrement.', 'ok');
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const search = async () => {
      const q = input.value.trim();
      if (q.length < 4) {
        close();
        return;
      }
      const id = ++request;
      try {
        const response = await fetch(`/admin/adresses?q=${encodeURIComponent(q)}`, { credentials: 'same-origin' });
        const items = response.ok ? await response.json() : [];
        if (id !== request) return;
        list.replaceChildren(...items.map((item, i) => {
          const li = document.createElement('li');
          li.id = `${list.id}-${i}`;
          li.setAttribute('role', 'option');
          li.setAttribute('aria-selected', 'false');
          li.dataset.value = item.label;
          const strong = document.createElement('strong');
          strong.textContent = item.label;
          const small = document.createElement('small');
          small.textContent = item.context;
          li.append(strong, small);
          return li;
        }));
        list.hidden = items.length === 0;
        input.setAttribute('aria-expanded', String(items.length > 0));
        active = -1;
        if (items.length === 0) setStatus('Aucune adresse trouvée : vérifiez le numéro, la rue et la ville.', 'off');
        else setStatus(`${items.length} suggestion${items.length > 1 ? 's' : ''} : choisissez l'adresse dans la liste.`);
      } catch {
        setStatus('Suggestions indisponibles : l\'adresse sera cherchée à l\'enregistrement.');
      }
    };
    input.addEventListener('input', () => {
      clearTimeout(timer);
      setStatus(input.value.trim() === '' ? 'Sans adresse, l\'adhérent figure dans la liste mais pas sur la carte.' : 'Recherche de l\'adresse…');
      timer = setTimeout(search, 300);
    });
    input.addEventListener('keydown', (event) => {
      const opts = options();
      if (event.key === 'ArrowDown' && opts.length) {
        event.preventDefault();
        if (list.hidden) {
          list.hidden = false;
          input.setAttribute('aria-expanded', 'true');
        }
        highlight(active + 1);
      } else if (event.key === 'ArrowUp' && opts.length && !list.hidden) {
        event.preventDefault();
        highlight(active - 1);
      } else if (event.key === 'Enter' && !list.hidden && active >= 0) {
        event.preventDefault();
        choose(opts[active]);
      } else if (event.key === 'Escape' && !list.hidden) {
        // Ferme la liste sans fermer la fenêtre de modification.
        event.preventDefault();
        event.stopPropagation();
        close();
      }
    });
    list.addEventListener('mousedown', (event) => {
      const option = event.target.closest('[role="option"]');
      if (option) {
        event.preventDefault();
        choose(option);
      }
    });
    input.addEventListener('blur', () => setTimeout(close, 100));
  });
}

// Œil des champs mot de passe : affiche ou masque la saisie. Sans JavaScript, le bouton reste caché.
function initPasswordToggle() {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    const input = button && document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
  });
  // Saisie remasquée à l'envoi : le navigateur ne mémorise pas un champ texte en clair.
  document.addEventListener('submit', (event) => {
    event.target.querySelectorAll?.('[data-password-toggle][aria-pressed="true"]').forEach((button) => button.click());
  }, true);
}

// Compte : l'organisme ne concerne qu'un éditeur ; grisé et vidé quand le rôle est Administrateur.
function initRoleToggle() {
  document.addEventListener('change', (event) => {
    const role = event.target.closest('[data-role-select]');
    const org = role && role.form && role.form.querySelector('[data-role-org]');
    if (!org) return;
    org.disabled = role.value !== 'editor';
    if (org.disabled) org.value = '';
  });
}

// Onglets de liste (un par pôle) ; l'onglet ouvert est gardé dans l'adresse (?onglet=) pour revenir au même endroit.
function initTabsets(root) {
  root.querySelectorAll('[data-tabset]').forEach((set) => {
    if (set.dataset.ready) return;
    set.dataset.ready = '1';
    const list = set.querySelector('[data-tabset-list]');
    if (!list) return;
    list.hidden = false;
    const tabs = [...list.querySelectorAll('[data-tab]')];
    const panels = [...set.querySelectorAll('[data-tab-panel]')];
    const select = (slug, focus = false) => {
      tabs.forEach((tab) => {
        const on = tab.dataset.tab === slug;
        tab.setAttribute('aria-selected', String(on));
        tab.tabIndex = on ? 0 : -1;
        if (on && focus) tab.focus();
      });
      panels.forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== slug; });
      const url = new URL(location.href);
      url.searchParams.set('onglet', slug);
      history.replaceState(null, '', url);
    };
    list.addEventListener('click', (event) => {
      const tab = event.target.closest('[data-tab]');
      if (tab) select(tab.dataset.tab);
    });
    list.addEventListener('keydown', (event) => {
      const index = tabs.indexOf(document.activeElement);
      const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -index, End: tabs.length - 1 - index };
      if (index < 0 || !(event.key in moves)) return;
      event.preventDefault();
      select(tabs[(index + moves[event.key] + tabs.length) % tabs.length].dataset.tab, true);
    });
    select((tabs.find((t) => t.getAttribute('aria-selected') === 'true') || tabs[0]).dataset.tab);
  });
}

// Destination d'un lien : la saisie libre n'apparaît que pour « Un autre site » ou « Une adresse e-mail ».
function initLinkFields(root) {
  root.querySelectorAll('[data-link-field]').forEach((field) => {
    if (field.dataset.ready) return;
    field.dataset.ready = '1';
    const select = field.querySelector('[data-link-target]');
    const update = () => syncLinkField(field);
    select.addEventListener('change', () => {
      update();
      if (!field.querySelector('[data-link-free]').hasAttribute('data-off')) field.querySelector('[data-link-free-input]').focus();
    });
    update();
  });
}

function syncLinkField(field) {
  const value = field.querySelector('[data-link-target]').value;
  const free = field.querySelector('[data-link-free]');
  const isMail = value === '__mail';
  free.toggleAttribute('data-off', value !== '__web' && !isMail);
  free.querySelector('[data-link-free-label]').textContent = isMail ? 'Adresse e-mail' : 'Adresse du site';
  const input = free.querySelector('[data-link-free-input]');
  input.placeholder = isMail ? 'nom@exemple.fr' : 'www.exemple.fr';
  input.type = isMail ? 'email' : 'text';
}

function initMenu() {
  const toggle = document.querySelector('.app__toggle');
  const side = document.getElementById('admin-side');
  if (!toggle || !side || toggle.dataset.ready) return;
  toggle.dataset.ready = '1';
  toggle.hidden = false;
  const set = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    side.toggleAttribute('data-open', open);
  };
  toggle.addEventListener('click', () => set(toggle.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      set(false);
      toggle.focus();
    }
  });
}

/** Fenêtre de confirmation ; résout true si la personne confirme. */
function askConfirm(text, okLabel = 'Confirmer') {
  const dialog = document.getElementById('confirm-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return Promise.resolve(true);
  dialog.querySelector('#confirm-text').textContent = text;
  dialog.querySelector('[data-confirm-ok]').textContent = okLabel;
  const opener = document.activeElement;
  dialog.showModal();
  dialog.querySelector('[data-confirm-cancel]').focus();
  return new Promise((resolve) => {
    const done = (value) => {
      dialog.removeEventListener('click', onClick);
      dialog.removeEventListener('close', onClose);
      if (dialog.open) dialog.close();
      if (!value && opener && opener.isConnected) opener.focus();
      resolve(value);
    };
    const onClick = (event) => {
      if (event.target.closest('[data-confirm-ok]')) done(true);
      else if (event.target.closest('[data-confirm-cancel]')) done(false);
    };
    const onClose = () => done(false);
    dialog.addEventListener('click', onClick);
    dialog.addEventListener('close', onClose);
  });
}

function initConfirm() {
  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form || form.dataset.confirmed) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (await askConfirm(form.dataset.confirm)) {
      form.dataset.confirmed = '1';
      form.requestSubmit();
    }
  }, true);
}

// Fenêtre de modification : un lien [data-modal] charge la page visée et en affiche [data-modal-content].
// L'envoi du formulaire reste celui du serveur : après redirection, la page de fond est remplacée par la nouvelle version.
function initModal() {
  const dialog = document.getElementById('modal');
  if (!dialog || typeof dialog.showModal !== 'function' || !window.fetch || !window.DOMParser) return;
  const body = dialog.querySelector('[data-modal-body]');
  const foot = dialog.querySelector('[data-modal-foot-slot]');
  const heading = dialog.querySelector('#modal-title');
  const live = document.getElementById('admin-live');
  let opener = null;
  let url = '';
  let dirty = false;
  let busy = false;

  const parse = (html) => new DOMParser().parseFromString(html, 'text/html');

  const show = (doc, at) => {
    const content = doc.querySelector('[data-modal-content]');
    if (!content) return false;
    url = at;
    dirty = false;
    heading.textContent = content.dataset.modalTitle || doc.querySelector('h1')?.textContent.trim() || '';
    const notices = [...doc.querySelectorAll('#contenu > .notice--error')];
    const footer = content.querySelector('[data-modal-foot]');
    body.replaceChildren(...notices, content);
    foot.replaceChildren(...(footer ? [footer] : []));
    foot.hidden = !footer;
    enhance(dialog);
    if (!dialog.open) dialog.showModal();
    body.scrollTop = 0;
    const first = body.querySelector('[aria-invalid="true"], input:not([type="hidden"]):not([disabled]), textarea:not([hidden]), [contenteditable="true"], select');
    (first || body).focus({ preventScroll: true });
    return true;
  };

  const open = async (href, trigger) => {
    opener = trigger;
    try {
      const response = await fetch(href, { credentials: 'same-origin' });
      if (!response.ok) throw new Error(String(response.status));
      if (!show(parse(await response.text()), response.url)) location.assign(href);
    } catch {
      location.assign(href);
    }
  };

  const close = () => {
    dirty = false;
    if (dialog.open) dialog.close();
  };

  const requestClose = async () => {
    if (!dirty || await askConfirm('Fermer sans enregistrer ? Vos modifications seront perdues.', 'Fermer sans enregistrer')) close();
  };

  // Page de fond remplacée par sa version à jour (message de confirmation compris), sans perdre la position.
  const swapMain = (doc, at) => {
    const main = document.getElementById('contenu');
    const fresh = doc.getElementById('contenu');
    if (!main || !fresh) {
      location.assign(at);
      return;
    }
    const y = window.scrollY;
    const href = opener?.getAttribute('href');
    main.replaceChildren(...fresh.childNodes);
    const target = new URL(at);
    history.replaceState(null, '', target.pathname + target.search);
    enhance(main);
    window.scrollTo(0, y);
    const notice = main.querySelector('.notice');
    if (live && notice) live.textContent = notice.textContent.trim();
    opener = (href && main.querySelector(`[href="${CSS.escape(href)}"]`)) || main;
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-modal]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    event.preventDefault();
    open(link.href, link);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target.closest('[data-modal-close]')) {
      event.preventDefault();
      requestClose();
    } else if (event.target === dialog) {
      requestClose();
    }
  });
  dialog.addEventListener('cancel', (event) => {
    event.preventDefault();
    requestClose();
  });
  dialog.addEventListener('close', () => {
    body.replaceChildren();
    foot.replaceChildren();
    if (opener && opener.isConnected) opener.focus();
  });
  dialog.addEventListener('input', () => { dirty = true; });
  dialog.addEventListener('change', () => { dirty = true; });

  dialog.addEventListener('submit', async (event) => {
    const form = event.target;
    if ((form.method || '').toLowerCase() !== 'post' || busy) return;
    event.preventDefault();
    busy = true;
    const button = event.submitter;
    if (button) button.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form, button), credentials: 'same-origin' });
      const raw = await response.text();
      const doc = parse(raw);
      const content = doc.querySelector('[data-modal-content]');
      const samePage = new URL(response.url).pathname === new URL(url).pathname;
      if (response.redirected && !(content && !samePage)) {
        // Fenêtre ouverte depuis un autre écran (ex. tableau de bord) : on reste sur cet écran, mis à jour.
        let fresh = doc;
        let at = response.url;
        if (new URL(response.url).pathname !== location.pathname) {
          fresh = parse(await (await fetch(location.href, { credentials: 'same-origin' })).text());
          at = location.href;
          const notice = doc.querySelector('#contenu > .notice');
          if (notice) fresh.getElementById('contenu')?.prepend(notice);
        }
        swapMain(fresh, at);
        close();
      } else if (show(doc, response.url)) {
        dirty = true; // formulaire renvoyé avec erreurs : la saisie n'est toujours pas enregistrée
      } else {
        // Page vide : PHP s'est arrêté en cours de route (LiteSpeed répond alors 200 sans contenu).
        throw new Error(raw.trim() === '' ? 'vide' : String(response.status));
      }
    } catch (failure) {
      if (live) live.textContent = '';
      const error = document.createElement('div');
      error.className = 'notice notice--error';
      error.setAttribute('role', 'alert');
      // Réponse inattendue (code HTTP affiché pour le diagnostic) ou coupure réseau, sans réponse du serveur.
      const status = failure instanceof Error && /^\d+$/.test(failure.message) ? Number(failure.message) : 0;
      error.textContent = failure instanceof Error && failure.message === 'vide'
        ? 'Le serveur s\'est arrêté en cours de route (page vide) : vérifiez dans la liste ce qui a été enregistré avant de réessayer. Prévenez La Capsule (détail dans data/php-errors.log).'
        : status >= 500
        ? `Le serveur a rencontré une erreur (code ${status}) : vérifiez dans la liste si l'enregistrement a eu lieu avant de réessayer. Prévenez La Capsule (détail dans data/php-errors.log).`
        : status > 0
          ? `Le serveur a refusé la demande (code ${status}) : vérifiez dans la liste si l'enregistrement a eu lieu avant de réessayer. Prévenez La Capsule en indiquant ce code.`
          : 'Pas de réponse du serveur : vérifiez dans la liste si l\'enregistrement a eu lieu, puis votre connexion, avant de réessayer.';
      body.prepend(error);
    } finally {
      busy = false;
      if (button && button.isConnected) button.disabled = false;
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (dirty && dialog.open) event.preventDefault();
  });
}

// Aperçu d'une newsletter dans une fenêtre, à la largeur d'un ordinateur ou d'un mobile.
function initPreview() {
  const dialog = document.getElementById('preview-dialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;
  const frame = dialog.querySelector('[data-preview-frame]');
  let opener = null;
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-preview]');
    if (!link || event.ctrlKey || event.metaKey) return;
    event.preventDefault();
    opener = link;
    frame.src = link.href;
    dialog.showModal();
    dialog.querySelector('[data-preview-width][aria-pressed="true"]').focus();
  });
  dialog.addEventListener('click', (event) => {
    const width = event.target.closest('[data-preview-width]');
    if (width) {
      dialog.querySelectorAll('[data-preview-width]').forEach((b) => b.setAttribute('aria-pressed', String(b === width)));
      frame.classList.toggle('preview-frame--mobile', width.dataset.previewWidth === 'mobile');
    }
    if (event.target.closest('[data-preview-close]') || event.target === dialog) dialog.close();
  });
  dialog.addEventListener('close', () => {
    frame.removeAttribute('src');
    if (opener && opener.isConnected) opener.focus();
  });
}

function initCounters(root) {
  root.querySelectorAll('[data-count]').forEach((field) => {
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
      out.textContent = `${n} caractères (idéal : ${target === 60 ? 'moins de 60' : '140 à 160'})`;
    };
    field.addEventListener('input', update);
    update();
  });
}

// Filtres instantanés : <div data-list> contient la barre [data-filters], des lignes (tbody tr ou [data-filter-row])
// et [data-filter-empty]. Chaque ligne porte data-f-<clé> ; la recherche porte sur son texte. L'état est reflété dans l'URL.
function initFilters(root) {
  root.querySelectorAll('[data-list]').forEach((list) => {
    if (list.dataset.ready) return;
    list.dataset.ready = '1';
    const bar = list.querySelector('[data-filters]');
    if (!bar) return;
    bar.hidden = false;
    const q = bar.querySelector('[data-filter-q]');
    const selects = [...bar.querySelectorAll('[data-filter]')];
    const rows = [...list.querySelectorAll('tbody tr, [data-filter-row]')];
    const count = bar.querySelector('[data-filter-count]');
    const empty = list.querySelector('[data-filter-empty]');
    const grids = [...list.querySelectorAll('.table-wrap, [data-filter-grid]')];
    const local = list.hasAttribute('data-list-local');
    const resets = [...list.querySelectorAll('[data-filter-reset]')];
    const words = (n) => `${n} ${n > 1 ? count.dataset.many : count.dataset.one}`;
    const norm = (s) => s.toLocaleLowerCase('fr').normalize('NFD').replace(/[̀-ͯ]/g, '');
    rows.forEach((row) => { row.dataset.text = norm(row.textContent); });

    const params = new URLSearchParams(local ? '' : location.search);
    if (q && params.get('recherche')) q.value = params.get('recherche');
    selects.forEach((s) => {
      const v = params.get(s.dataset.filter);
      if (v && [...s.options].some((o) => o.value === v)) s.value = v;
    });

    const apply = () => {
      const term = q ? norm(q.value.trim()) : '';
      let shown = 0;
      rows.forEach((row) => {
        const hit = (term === '' || row.dataset.text.includes(term))
          && selects.every((s) => s.value === '' || row.dataset[`f${s.dataset.filter[0].toUpperCase()}${s.dataset.filter.slice(1)}`] === s.value);
        row.hidden = !hit;
        if (hit) shown += 1;
      });
      const active = term !== '' || selects.some((s) => s.value !== '');
      count.textContent = active ? `${shown} sur ${words(rows.length)}` : words(rows.length);
      if (empty) empty.hidden = shown !== 0;
      // Un bloc sans ligne visible disparaît pendant une recherche (tableau ou groupe de la liste).
      grids.forEach((grid) => {
        grid.hidden = active && !grid.querySelector('tbody tr:not([hidden]), [data-filter-row]:not([hidden])');
      });
      bar.querySelector('[data-filter-reset]').hidden = !active;
      if (local) return;
      const url = new URL(location.href);
      ['recherche', ...selects.map((s) => s.dataset.filter)].forEach((k) => url.searchParams.delete(k));
      if (q && q.value.trim() !== '') url.searchParams.set('recherche', q.value.trim());
      selects.forEach((s) => { if (s.value !== '') url.searchParams.set(s.dataset.filter, s.value); });
      history.replaceState(null, '', url);
    };
    q?.addEventListener('input', apply);
    selects.forEach((s) => s.addEventListener('change', apply));
    resets.forEach((b) => b.addEventListener('click', () => {
      if (q) q.value = '';
      selects.forEach((s) => { s.value = ''; });
      apply();
      (q || selects[0])?.focus();
    }));
    apply();
  });
}

// Choix de la photo d'une section : l'aperçu suit la photo cochée.
function initPhotoPick(root) {
  root.querySelectorAll('[data-photo-pick]').forEach((pick) => {
    if (pick.dataset.ready) return;
    pick.dataset.ready = '1';
    const img = pick.querySelector('[data-photo-preview]');
    const label = pick.querySelector('[data-photo-label]');
    pick.addEventListener('change', (event) => {
      const file = event.target.closest('input[type="file"]');
      if (file && file.files.length) {
        img.hidden = true;
        label.textContent = `Nouveau fichier : ${file.files[0].name}`;
        pick.querySelectorAll('input[type="radio"]').forEach((r) => { r.checked = false; });
        return;
      }
      const input = event.target.closest('input[type="radio"]');
      if (!input) return;
      img.hidden = input.dataset.src === '';
      if (input.dataset.src) img.src = input.dataset.src;
      label.textContent = input.dataset.label;
    });
  });
}

/* Éditeur de texte : gras, italique, lien, listes (et intertitre, bouton pour les textes longs).
   Le champ d'origine (Markdown) reste la valeur envoyée ; il n'est réécrit que si le texte est modifié. */

const RTE_TOOLS = {
  inline: ['bold', 'italic', 'link'],
  block: ['bold', 'italic', 'link', 'list', 'olist'],
  full: ['bold', 'italic', 'link', 'list', 'olist', 'heading', 'button'],
};
const SAFE_URL = /^(https?:\/\/|mailto:|tel:|\/|#)/i;

function initRichEditors(root) {
  const toolbarTemplate = document.getElementById('rte-toolbar');
  if (!toolbarTemplate || typeof document.execCommand !== 'function') return;
  root.querySelectorAll('textarea[data-rte]').forEach((field) => {
    if (field.dataset.ready) return;
    field.dataset.ready = '1';
    const mode = field.dataset.rte === 'inline' ? 'inline' : (field.dataset.rte === 'full' ? 'full' : 'block');
    const wrap = document.createElement('div');
    wrap.className = `rte rte--${mode}`;
    const toolbar = toolbarTemplate.content.firstElementChild.cloneNode(true);
    toolbar.querySelectorAll('[data-rte-tool]').forEach((b) => {
      if (!RTE_TOOLS[mode].includes(b.dataset.rteTool)) b.remove();
    });
    const area = document.createElement('div');
    area.className = 'rte__area';
    area.contentEditable = 'true';
    area.setAttribute('role', 'textbox');
    if (mode !== 'inline') area.setAttribute('aria-multiline', 'true');
    const label = document.querySelector(`label[for="${field.id}"]`);
    if (label) {
      label.id ||= `${field.id}-label`;
      area.setAttribute('aria-labelledby', label.id);
      label.addEventListener('click', (event) => {
        event.preventDefault();
        area.focus();
      });
    }
    if (field.getAttribute('aria-describedby')) area.setAttribute('aria-describedby', field.getAttribute('aria-describedby'));
    if (field.required) area.setAttribute('aria-required', 'true');
    area.innerHTML = field.dataset.rteHtml || '';
    wrap.append(toolbar, area);
    field.before(wrap);
    field.hidden = true;

    const sync = () => {
      field.value = toMarkdown(area, mode);
      field.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const refresh = () => {
      toolbar.querySelectorAll('[aria-pressed]').forEach((b) => {
        const tool = b.dataset.rteTool;
        let on = false;
        try {
          on = tool === 'bold' || tool === 'italic' ? document.queryCommandState(tool)
            : tool === 'list' ? document.queryCommandState('insertUnorderedList')
              : tool === 'olist' ? document.queryCommandState('insertOrderedList')
                : tool === 'heading' ? Boolean(closestIn(area, 'h3')) : false;
        } catch { on = false; }
        b.setAttribute('aria-pressed', String(on));
      });
    };

    area.addEventListener('focus', () => document.execCommand('defaultParagraphSeparator', false, 'p'));
    area.addEventListener('input', sync);
    area.addEventListener('keyup', refresh);
    area.addEventListener('mouseup', refresh);
    area.addEventListener('keydown', (event) => {
      if (mode === 'inline' && event.key === 'Enter') event.preventDefault();
      if ((event.ctrlKey || event.metaKey) && ['b', 'i'].includes(event.key.toLowerCase())) {
        event.preventDefault();
        document.execCommand(event.key.toLowerCase() === 'b' ? 'bold' : 'italic');
        sync();
        refresh();
      }
    });
    area.addEventListener('paste', (event) => {
      event.preventDefault();
      const text = (event.clipboardData || window.clipboardData).getData('text/plain');
      if (mode === 'inline') {
        document.execCommand('insertText', false, text.replace(/\s+/g, ' '));
      } else {
        document.execCommand('insertHTML', false, text.replace(/\r/g, '').split(/\n{2,}/).map((p) => p.trim()).filter(Boolean)
          .map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`).join(''));
      }
    });
    toolbar.addEventListener('mousedown', (event) => {
      if (event.target.closest('button')) event.preventDefault(); // garde la sélection dans le texte
    });
    toolbar.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-rte-tool]');
      if (!button) return;
      const tool = button.dataset.rteTool;
      area.focus();
      if (tool === 'bold' || tool === 'italic') document.execCommand(tool);
      else if (tool === 'list') document.execCommand('insertUnorderedList');
      else if (tool === 'olist') document.execCommand('insertOrderedList');
      else if (tool === 'heading') document.execCommand('formatBlock', false, closestIn(area, 'h3') ? 'p' : 'h3');
      else if (tool === 'link' || tool === 'button') await editLink(area, tool === 'button');
      sync();
      refresh();
    });
  });
}

const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** Élément `selector` qui contient la sélection, dans la zone de texte seulement. */
function closestIn(area, selector) {
  const selection = window.getSelection();
  if (!selection || selection.rangeCount === 0) return null;
  let node = selection.getRangeAt(0).commonAncestorContainer;
  if (node.nodeType !== 1) node = node.parentElement;
  const found = node?.closest(selector);
  return found && area.contains(found) ? found : null;
}

/** Fenêtre « Lien » : destination choisie dans la liste des pages, ou un autre site / un e-mail. */
async function editLink(area, asButton) {
  const dialog = document.getElementById('link-dialog');
  if (!dialog) return;
  const selection = window.getSelection();
  const range = selection.rangeCount ? selection.getRangeAt(0).cloneRange() : null;
  const current = closestIn(area, 'a');
  const field = dialog.querySelector('[data-link-field]');
  const select = field.querySelector('[data-link-target]');
  const free = field.querySelector('[data-link-free-input]');
  const text = dialog.querySelector('#link-text');
  const remove = dialog.querySelector('[data-link-remove]');
  const error = dialog.querySelector('#link-error');
  // Valeur de départ : lien existant reconnu dans la liste, sinon e-mail ou autre site.
  const href = current ? current.getAttribute('href') : '';
  const known = [...select.options].some((o) => o.value === href && href !== '');
  if (!select.querySelector('option[value=""]')) select.prepend(new Option('Choisir une page…', ''));
  select.value = known ? href : (href.startsWith('mailto:') ? '__mail' : (href ? '__web' : ''));
  free.value = known ? '' : href.replace(/^mailto:/, '');
  syncLinkField(field);
  text.value = range ? range.toString() : '';
  dialog.querySelector('[data-link-text]').hidden = Boolean(current) || (range && !range.collapsed);
  remove.hidden = !current;
  error.hidden = true;
  dialog.querySelector('#link-dialog-title').textContent = asButton ? 'Bouton' : 'Lien';
  dialog.showModal();
  select.focus();
  const choice = await new Promise((resolve) => {
    const onSubmit = (event) => {
      event.preventDefault();
      const kind = select.value;
      const value = kind === '__mail' ? (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(free.value.trim()) ? `mailto:${free.value.trim()}` : '')
        : kind === '__web' ? normalizeUrl(free.value) : kind;
      if (!value) {
        error.hidden = false;
        (kind === '__web' || kind === '__mail' ? free : select).focus();
        return;
      }
      finish({ href: value, label: kind.startsWith('__') ? free.value.trim() : select.selectedOptions[0].textContent.replace(/ \(haut de la page\)$/, '').replace(/^.* › /, '') });
    };
    const onClick = (event) => {
      if (event.target.closest('[data-link-cancel]')) finish(null);
      if (event.target.closest('[data-link-remove]')) finish({ remove: true });
    };
    const finish = (value) => {
      dialog.removeEventListener('submit', onSubmit);
      dialog.removeEventListener('click', onClick);
      dialog.removeEventListener('close', onClose);
      if (dialog.open) dialog.close();
      resolve(value);
    };
    const onClose = () => finish(null);
    dialog.addEventListener('submit', onSubmit);
    dialog.addEventListener('click', onClick);
    dialog.addEventListener('close', onClose);
  });
  area.focus();
  if (range) {
    selection.removeAllRanges();
    selection.addRange(range);
  }
  if (!choice) return;
  if (choice.remove) {
    if (current) {
      const block = current.closest('p.cta');
      current.replaceWith(...current.childNodes);
      block?.classList.remove('cta');
    }
    return;
  }
  let link = current;
  if (link) {
    link.setAttribute('href', choice.href);
  } else if (range && !range.collapsed) {
    document.execCommand('createLink', false, choice.href);
    link = closestIn(area, 'a');
  } else {
    const label = text.value.trim() || choice.label || choice.href;
    document.execCommand('insertHTML', false, `<a href="${escapeHtml(choice.href)}">${escapeHtml(label)}</a>`);
    link = closestIn(area, 'a') || [...area.querySelectorAll('a')].find((a) => a.getAttribute('href') === choice.href);
  }
  if (asButton && link) {
    const block = link.closest('p, div:not(.rte__area)') || link;
    if (block !== link && area.contains(block)) block.classList.add('cta');
  }
}

function normalizeUrl(raw) {
  const value = raw.trim();
  if (value === '') return '';
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return `mailto:${value}`;
  if (/^www\./i.test(value)) return `https://${value}`;
  if (/^[a-z0-9-]+(\.[a-z0-9-]+)+(\/\S*)?$/i.test(value)) return `https://${value}`;
  if (!SAFE_URL.test(value) || /[\s()]/.test(value)) return '';
  return value;
}

/** Contenu de l'éditeur → Markdown du site (FORMAT.md). Tout autre balisage est réduit à son texte. */
function toMarkdown(area, mode) {
  const blocks = [];
  let loose = '';
  const cleanLines = (text) => text.split('\n').map((l) => l.replace(/[ \t ]+/g, ' ').trim()).filter(Boolean).join('\n');
  const flushLoose = () => {
    const text = cleanLines(loose);
    if (text) blocks.push(text);
    loose = '';
  };
  area.childNodes.forEach((node) => {
    const tag = node.nodeType === 1 ? node.tagName.toLowerCase() : '';
    if (tag === 'ul' || tag === 'ol') {
      flushLoose();
      const items = [...node.querySelectorAll(':scope > li')]
        .map((li) => inlineMd(li).replace(/\s+/g, ' ').trim())
        .filter(Boolean)
        .map((t, i) => (tag === 'ul' ? `- ${t}` : `${i + 1}. ${t}`));
      if (items.length) blocks.push(items.join('\n'));
    } else if (/^h[1-6]$/.test(tag)) {
      flushLoose();
      const text = inlineMd(node).replace(/\s+/g, ' ').trim();
      if (text) blocks.push(mode === 'full' ? `### ${text.replace(/\*+/g, '')}` : text);
    } else if (tag === 'p' || tag === 'div' || tag === 'blockquote') {
      flushLoose();
      const links = node.querySelectorAll('a');
      if (mode === 'full' && node.classList.contains('cta') && links.length === 1) {
        const md = inlineMd(links[0]).trim();
        if (md.startsWith('[')) {
          blocks.push(`→ ${md}`);
          return;
        }
      }
      const text = cleanLines(inlineMd(node));
      if (text) blocks.push(text);
    } else {
      loose += inlineMd(node);
    }
  });
  flushLoose();
  if (mode === 'inline') {
    return blocks.join(' ').replace(/^(?:- |\d+\. )/gm, '').replace(/\s+/g, ' ').trim();
  }
  return blocks.join('\n\n');
}

function inlineMd(node) {
  if (node.nodeType === 3) return node.nodeValue.replace(/[\r\n\t]+/g, ' ');
  if (node.nodeType !== 1) return '';
  const tag = node.tagName.toLowerCase();
  if (tag === 'br') return '\n';
  const inner = [...node.childNodes].map(inlineMd).join('');
  const style = node.getAttribute('style') || '';
  const wrap = (mark, text) => {
    if (text.trim() === '') return text;
    const [, before, core, after] = text.match(/^(\s*)([\s\S]*?)(\s*)$/);
    return `${before}${mark}${core.split(mark).join('')}${mark}${after}`;
  };
  if (tag === 'strong' || tag === 'b' || /font-weight:\s*(bold|[6-9]00)/.test(style)) return wrap('**', inner);
  if (tag === 'em' || tag === 'i' || /font-style:\s*italic/.test(style)) return wrap('*', inner);
  if (tag === 'a') {
    const href = (node.getAttribute('href') || '').trim();
    const text = inner.replace(/[[\]]/g, '');
    if (text.trim() === '' || !SAFE_URL.test(href) || /[\s()]/.test(href)) return inner;
    const [, before, core, after] = text.match(/^(\s*)([\s\S]*?)(\s*)$/);
    return `${before}[${core}](${href})${after}`;
  }
  if (tag === 'li' || tag === 'p' || tag === 'div') return `${inner}\n`;
  return inner;
}
