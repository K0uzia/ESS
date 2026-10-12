// Amélioration progressive : sans ce script, menu déplié, listes complètes, carte en simple lien.
document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  document.querySelectorAll('[data-filter]').forEach(initFilter);
  document.querySelectorAll('[data-map-src]').forEach(initMap);
  document.querySelectorAll('[data-tabs]').forEach(initTabs);
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
  nav.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
}

function initFilter(filter) {
  if (filter.dataset.ready) return;
  filter.dataset.ready = '1';
  const groups = [...document.querySelectorAll('[data-filter-group]')];
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
  apply('tout');
}

function initMap(facade) {
  const button = facade.querySelector('[data-map-load]');
  if (!button || facade.dataset.ready) return;
  facade.dataset.ready = '1';
  button.addEventListener('click', () => {
    const frame = document.createElement('iframe');
    frame.className = 'map-frame';
    frame.src = facade.dataset.mapSrc;
    frame.title = 'Carte des adhérents (GoGoCarto)';
    frame.loading = 'lazy';
    button.replaceWith(frame);
  }, { once: true });
}
