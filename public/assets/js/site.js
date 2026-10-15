// Amélioration progressive : sans ce script, menu déplié, listes complètes, carte en simple lien.
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  document.querySelectorAll('[data-filter]').forEach(initFilter);
  document.querySelectorAll('[data-tabs]').forEach(initTabs);
  document.querySelectorAll('[data-subnav]').forEach(initSubnav);
  document.querySelectorAll('[data-map]').forEach(initMembersMap);
  document.querySelectorAll('[data-view-switch]').forEach(initViewSwitch);
  initReader();
  initQuotes();
});

// Onglets des pôles (motif ARIA « tabs ») ; l'ancre #pays-de-… ouvre l'onglet correspondant.
function initTabs(root) {
  if (root.dataset.ready) return;
  root.dataset.ready = '1';
  const list = root.querySelector('[data-tabs-list]');
  const tabs = [...root.querySelectorAll('[data-tab]')];
  const panels = [...root.querySelectorAll('[data-tab-panel]')];
  if (!list || tabs.length === 0) return;

  list.hidden = false;
  list.setAttribute('role', 'tablist');
  list.setAttribute('aria-label', 'Choisir un pôle');
  tabs.forEach((tab) => tab.setAttribute('role', 'tab'));
  panels.forEach((panel) => {
    panel.setAttribute('role', 'tabpanel');
    panel.setAttribute('aria-labelledby', `onglet-${panel.dataset.tabPanel}`);
    panel.tabIndex = 0;
  });

  const select = (slug, focus = false) => {
    tabs.forEach((tab) => {
      const on = tab.dataset.tab === slug;
      tab.setAttribute('aria-selected', String(on));
      tab.tabIndex = on ? 0 : -1;
      if (on && focus) tab.focus();
    });
    panels.forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== slug; });
  };
  const fromHash = () => {
    const slug = decodeURIComponent(location.hash.slice(1));
    if (!tabs.some((tab) => tab.dataset.tab === slug)) return false;
    select(slug);
    root.scrollIntoView({ block: 'start' });
    return true;
  };

  list.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-tab]');
    if (!tab) return;
    select(tab.dataset.tab);
    history.replaceState(null, '', `#${tab.dataset.tab}`);
  });
  list.addEventListener('keydown', (event) => {
    const index = tabs.indexOf(document.activeElement);
    if (index < 0) return;
    const moves = { ArrowRight: 1, ArrowLeft: -1, Home: -index, End: tabs.length - 1 - index };
    if (!(event.key in moves)) return;
    event.preventDefault();
    const next = tabs[(index + moves[event.key] + tabs.length) % tabs.length];
    select(next.dataset.tab, true);
  });
  window.addEventListener('hashchange', fromHash);
  if (!fromHash()) select(tabs[0].dataset.tab);
}

function initNav() {
  const nav = document.querySelector('[data-nav]');
  const toggle = nav?.querySelector('.site-nav__toggle');
  if (!nav || !toggle || nav.dataset.ready) return;
  nav.dataset.ready = '1';
  toggle.hidden = false;

  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    nav.toggleAttribute('data-open', open);
  };
  toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  // Échap ferme le menu où que soit le focus, et rend le focus au bouton.
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
}

function initFilter(filter) {
  if (filter.dataset.ready) return;
  filter.dataset.ready = '1';
  const groups = [...(filter.closest('section') ?? document).querySelectorAll('[data-filter-group]')];
  const options = [...filter.querySelectorAll('[data-filter-value]')];
  const status = filter.querySelector('[data-filter-status]');

  const apply = (value) => {
    groups.forEach((group) => { group.hidden = value !== 'tout' && group.dataset.filterGroup !== value; });
    options.forEach((option) => option.setAttribute('aria-pressed', String(option.dataset.filterValue === value)));
    const label = options.find((option) => option.dataset.filterValue === value)?.textContent.trim();
    if (status && label) status.textContent = `Affichage : ${label}`;
  };

  options.forEach((option) => option.setAttribute('role', 'button'));
  filter.addEventListener('click', (event) => {
    const option = event.target.closest('[data-filter-value]');
    if (!option) return;
    event.preventDefault();
    apply(option.dataset.filterValue);
  });
  filter.addEventListener('keydown', (event) => {
    const option = event.target.closest('[data-filter-value]');
    if (option && event.key === ' ') {
      event.preventDefault();
      apply(option.dataset.filterValue);
    }
  });
  // Lien entrant vers un groupe (ex. /equipe/#equipe-pays-de-brest) : filtre déjà posé sur ce pôle.
  const linked = options.find((option) => option.getAttribute('href') === location.hash);
  // Sans option « tout » (documents), la première rubrique est affichée d'emblée.
  apply(linked ? linked.dataset.filterValue : filter.dataset.filterDefault ?? 'tout');
}

// Barre « Sur cette page » : le lien de la section lue est marqué, et reste visible dans la barre.
function initSubnav(nav) {
  if (nav.dataset.ready || !('IntersectionObserver' in window)) return;
  nav.dataset.ready = '1';
  const links = [...nav.querySelectorAll('a[href^="#"]')];
  const list = nav.querySelector('ul');
  const sections = links.map((link) => document.getElementById(link.hash.slice(1))).filter(Boolean);
  const visible = new Set();
  const mark = () => {
    const current = sections.find((section) => visible.has(section));
    links.forEach((link) => {
      const on = current !== undefined && link.hash === `#${current.id}`;
      if (on) {
        link.setAttribute('aria-current', 'true');
        const left = link.offsetLeft - list.clientWidth / 2 + link.offsetWidth / 2;
        list.scrollTo({ left, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      } else {
        link.removeAttribute('aria-current');
      }
    });
  };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
    mark();
  }, { rootMargin: '-30% 0px -60% 0px' });
  sections.forEach((section) => observer.observe(section));
}

// « Lire la suite » d'une carte : le texte complet s'ouvre dans une fenêtre, la grille garde ses hauteurs.
// Sans script (ou sans <dialog>), le <details> se déplie sur place.
function initReader() {
  if (typeof HTMLDialogElement !== 'function' || document.body.dataset.reader) return;
  document.body.dataset.reader = '1';
  let dialog;
  let trigger;
  const build = () => {
    dialog = document.createElement('dialog');
    dialog.className = 'reader';
    dialog.setAttribute('aria-labelledby', 'reader-title');
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'reader__close button button--secondary';
    close.textContent = 'Fermer';
    close.addEventListener('click', () => dialog.close());
    const title = document.createElement('h2');
    title.id = 'reader-title';
    title.className = 'reader__title';
    const body = document.createElement('div');
    body.className = 'reader__body prose';
    dialog.append(close, title, body);
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => trigger?.focus());
    document.body.append(dialog);
  };
  document.addEventListener('click', (event) => {
    const summary = event.target.closest('.bloc details.more > summary');
    if (!summary) return;
    event.preventDefault();
    if (!dialog) build();
    trigger = summary;
    const card = summary.closest('.bloc');
    const content = card.querySelector('.prose').cloneNode(true);
    content.querySelectorAll('details.more').forEach((details) => {
      details.querySelector('summary').remove();
      details.replaceWith(...details.childNodes);
    });
    dialog.querySelector('.reader__title').textContent = card.querySelector('.bloc__title').textContent;
    dialog.querySelector('.reader__body').replaceChildren(...content.childNodes);
    dialog.showModal();
  });
}

// Carte des adhérents : la carte remplit son cadre à tous les niveaux de zoom.
// Molette au-dessus de la carte : zoom ; hors de la carte, la page défile normalement.
// Aussi : boutons, double-clic, glisser pour se déplacer, fiche au clic ou au clavier (flèches, Entrée, Échap).
function initMembersMap(map) {
  if (map.dataset.ready) return;
  map.dataset.ready = '1';
  const viewport = map.querySelector('.members-map__viewport');
  const svg = map.querySelector('[data-map-svg]');
  const card = map.querySelector('[data-map-card]');
  const points = [...svg.querySelectorAll('.members-map__point')];
  const towns = [...svg.querySelectorAll('.members-map__town')];
  const [, , width, height] = svg.getAttribute('viewBox').split(' ').map(Number);
  const view = { cx: width / 2, cy: height / 2, k: 1, w: width, h: height };
  let active;

  map.classList.add('is-interactive');
  svg.setAttribute('role', 'group');
  svg.setAttribute('aria-roledescription', 'carte');
  svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
  map.querySelector('[data-map-controls]').hidden = false;
  map.querySelector('[data-map-help]').hidden = false;
  points.forEach((point) => {
    point.setAttribute('role', 'button');
    point.setAttribute('aria-label', `${point.dataset.name}, ${point.dataset.meta}`);
    point.tabIndex = -1;
    point.querySelector('title')?.remove();
  });
  const visiblePoints = () => points.filter((point) => !point.hidden);
  const rove = (point) => {
    points.forEach((p) => { p.tabIndex = -1; });
    if (point) point.tabIndex = 0;
  };
  rove(visiblePoints()[0]);

  // Fenêtre visible : même proportion que le cadre, pour que la carte le remplisse toujours.
  const apply = () => {
    const box = viewport.getBoundingClientRect();
    if (box.width === 0 || box.height === 0) return;
    const ratio = box.width / box.height;
    const baseW = ratio > width / height ? height * ratio : width;
    const baseH = baseW / ratio;
    view.k = Math.min(10, Math.max(1, view.k));
    view.w = baseW / view.k;
    view.h = baseH / view.k;
    view.cx = view.w >= width ? width / 2 : Math.min(width - view.w / 2, Math.max(view.w / 2, view.cx));
    view.cy = view.h >= height ? height / 2 : Math.min(height - view.h / 2, Math.max(view.h / 2, view.cy));
    svg.setAttribute('viewBox', `${view.cx - view.w / 2} ${view.cy - view.h / 2} ${view.w} ${view.h}`);
    svg.classList.toggle('is-zoomed', view.k > 1);
    // Taille à l'écran presque constante des points et des noms de villes.
    const unit = view.w / box.width;
    points.forEach((point) => point.querySelector('circle').setAttribute('r', String(7 * unit * view.k ** 0.2)));
    towns.forEach((town) => {
      town.setAttribute('font-size', String(15 * unit));
      town.setAttribute('y', String(Number(town.dataset.y) + 22 * unit));
    });
    if (active) place(active);
  };
  const toSvg = (event) => {
    const box = viewport.getBoundingClientRect();
    return [view.cx - view.w / 2 + (event.clientX - box.left) / box.width * view.w, view.cy - view.h / 2 + (event.clientY - box.top) / box.height * view.h];
  };
  const zoom = (factor, ax = view.cx, ay = view.cy) => {
    const k = Math.min(10, Math.max(1, view.k * factor));
    view.cx = ax - (ax - view.cx) * (view.k / k);
    view.cy = ay - (ay - view.cy) * (view.k / k);
    view.k = k;
    apply();
  };

  const place = (point) => {
    const circle = point.querySelector('circle').getBoundingClientRect();
    const box = viewport.getBoundingClientRect();
    const left = Math.min(box.width - card.offsetWidth - 8, Math.max(8, circle.left - box.left + circle.width / 2 - card.offsetWidth / 2));
    const above = circle.top - box.top - card.offsetHeight - 12;
    card.style.translate = `${left}px ${above > 8 ? above : circle.bottom - box.top + 12}px`;
  };
  const open = (point) => {
    active = point;
    points.forEach((p) => p.classList.toggle('is-active', p === point));
    card.querySelector('[data-map-name]').textContent = point.dataset.name;
    card.querySelector('[data-map-meta]').textContent = point.dataset.meta;
    const url = card.querySelector('[data-map-url]');
    url.hidden = point.dataset.url === '';
    if (point.dataset.url) url.href = point.dataset.url;
    card.querySelector('[data-map-list]').href = `#${point.dataset.id}`;
    card.hidden = false;
    place(point);
  };
  const close = () => {
    card.hidden = true;
    active?.classList.remove('is-active');
    active?.focus();
    active = undefined;
  };

  map.addEventListener('click', (event) => {
    const button = event.target.closest('[data-map-zoom]');
    if (button) {
      const action = button.dataset.mapZoom;
      if (action === 'reset') { view.k = 1; apply(); } else zoom(action === 'in' ? 1.6 : 1 / 1.6);
      return;
    }
    if (event.target.closest('[data-map-close]')) { close(); return; }
    const point = event.target.closest('.members-map__point');
    if (point && !moved) { rove(point); open(point); }
  });
  viewport.addEventListener('wheel', (event) => {
    event.preventDefault();
    zoom(Math.exp(-event.deltaY * (event.deltaMode === 1 ? 0.05 : 0.0015)), ...toSvg(event));
  }, { passive: false });
  svg.addEventListener('dblclick', (event) => { event.preventDefault(); zoom(2, ...toSvg(event)); });
  svg.addEventListener('keydown', (event) => {
    const point = event.target.closest('.members-map__point');
    if (!point) return;
    const list = visiblePoints().sort((a, b) => a.getBBox().x - b.getBBox().x);
    const index = list.indexOf(point);
    const moves = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
    if (event.key in moves) {
      event.preventDefault();
      const next = list[(index + moves[event.key] + list.length) % list.length];
      rove(next);
      next.focus();
      if (active) open(next);
    } else if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      open(point);
    }
  });
  map.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !card.hidden) { event.preventDefault(); close(); }
  });

  // Glisser pour se déplacer (souris, doigt, stylet) ; un petit mouvement reste un clic.
  let drag;
  let moved = false;
  svg.addEventListener('pointerdown', (event) => {
    drag = { x: event.clientX, y: event.clientY, cx: view.cx, cy: view.cy };
    moved = false;
  });
  svg.addEventListener('pointermove', (event) => {
    if (!drag || view.k === 1) return;
    const dx = event.clientX - drag.x;
    const dy = event.clientY - drag.y;
    if (!moved && Math.hypot(dx, dy) < 4) return;
    if (!moved) svg.setPointerCapture(event.pointerId);
    moved = true;
    const box = viewport.getBoundingClientRect();
    view.cx = drag.cx - dx / box.width * view.w;
    view.cy = drag.cy - dy / box.height * view.h;
    apply();
  });
  svg.addEventListener('pointerup', () => { drag = undefined; });
  svg.addEventListener('pointercancel', () => { drag = undefined; });

  // Le filtre par pôle masque des points : la fiche ouverte se ferme si son point disparaît.
  new MutationObserver(() => {
    if (active?.hidden) close();
    if (!visiblePoints().some((p) => p.tabIndex === 0)) rove(visiblePoints()[0]);
  }).observe(svg, { subtree: true, attributes: true, attributeFilter: ['hidden'] });
  new ResizeObserver(apply).observe(viewport);
  apply();
}

// Adhérents : vue Carte ou Liste (sans script, les deux restent affichées l'une sous l'autre).
function initViewSwitch(control) {
  if (control.dataset.ready) return;
  control.dataset.ready = '1';
  const scope = control.closest('section') ?? document;
  const buttons = [...control.querySelectorAll('[data-view]')];
  const panels = [...scope.querySelectorAll('[data-view-panel]')];
  const show = (view) => {
    buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
    panels.forEach((panel) => { panel.hidden = panel.dataset.viewPanel !== view; });
  };
  control.hidden = false;
  control.addEventListener('click', (event) => {
    const button = event.target.closest('[data-view]');
    if (button) show(button.dataset.view);
  });
  // « Voir dans la liste » depuis la fiche de la carte, ou lien entrant vers un adhérent : vue Liste.
  scope.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="#adherent-"]');
    if (!link) return;
    show('liste');
    document.getElementById(link.hash.slice(1))?.scrollIntoView({ block: 'center' });
    event.preventDefault();
    history.replaceState(null, '', link.hash);
  });
  show(location.hash.startsWith('#adherent-') ? 'liste' : 'carte');
}

// Témoignages : un bouton n'apparaît que si la citation dépasse ses quatre lignes.
function initQuotes() {
  document.querySelectorAll('.section--temoignages .quote').forEach((quote) => {
    const text = quote.querySelector('blockquote p');
    if (!text || quote.dataset.ready || text.scrollHeight <= text.clientHeight + 2) return;
    quote.dataset.ready = '1';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'quote__more';
    button.setAttribute('aria-expanded', 'false');
    button.textContent = 'Lire la suite';
    button.addEventListener('click', () => {
      const open = quote.classList.toggle('is-open');
      button.setAttribute('aria-expanded', String(open));
      button.textContent = open ? 'Réduire' : 'Lire la suite';
    });
    quote.querySelector('blockquote').after(button);
  });
}
