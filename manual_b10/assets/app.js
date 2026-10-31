(function () {
  const W = window.WIKI;
  const $ = (s) => document.querySelector(s);
  const nav = $('#nav'), content = $('#content'), input = $('#search'), results = $('#results');
  const order = W.groups.flatMap((g) => g.pages);

  const strip = (html) => {
    const d = document.createElement('div');
    d.innerHTML = html;
    return d.textContent.replace(/\s+/g, ' ').trim();
  };
  const norm = (s) => s.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/ß/g, 'ss');
  const index = order.map((id) => {
    const p = W.pages[id];
    const text = strip(p.html);
    return { id, title: p.title, group: p.group, text, nt: norm(p.title), nx: norm(text + ' ' + (p.keywords || '')) };
  });

  function buildNav() {
    nav.innerHTML = W.groups.map((g) =>
      `<h3>${g.title}</h3>` + g.pages.map((id) => `<a href="#/${id}" data-id="${id}">${W.pages[id].title}</a>`).join('')
    ).join('');
  }

  function route() {
    const id = (location.hash.match(/^#\/([\w-]+)/) || [])[1];
    const page = W.pages[id] ? id : 'start';
    const p = W.pages[page];
    const i = order.indexOf(page);
    const prev = order[i - 1], next = order[i + 1];
    content.innerHTML =
      `<div class="crumb">${p.group}</div><h1>${p.title}</h1>${p.html}` +
      `<div class="pager"><span>${prev ? `<a href="#/${prev}">← ${W.pages[prev].title}</a>` : ''}</span>` +
      `<span>${next ? `<a href="#/${next}">${W.pages[next].title} →</a>` : ''}</span></div>` +
      `<p class="footer-note">${W.disclaimer} <a href="#/impressum">Impressum</a></p>`;
    document.title = `${p.title} – Leapmotor B10 Kurzanleitung`;
    nav.querySelectorAll('a').forEach((a) => a.classList.toggle('current', a.dataset.id === page));
    document.body.classList.remove('menu-open');
    $('#menu-toggle').setAttribute('aria-expanded', 'false');
    window.scrollTo(0, 0);
  }

  // Suche
  let active = -1;
  function search(q) {
    const terms = norm(q).split(/\s+/).filter(Boolean);
    if (!terms.length) return [];
    return index.map((e) => {
      let score = 0;
      for (const t of terms) {
        if (e.nt.includes(t)) score += 10;
        else if (e.nx.includes(t)) score += 1;
        else return null;
      }
      return { e, score };
    }).filter(Boolean).sort((a, b) => b.score - a.score).map((r) => r.e);
  }
  function snippet(e, q) {
    const t = norm(q).split(/\s+/).filter(Boolean)[0];
    const pos = norm(e.text).indexOf(t);
    if (pos < 0) return e.text.slice(0, 110) + '…';
    const s = Math.max(0, pos - 40);
    const raw = e.text.slice(s, s + 120).replace(/[&<>]/g, '');
    const re = new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'i');
    return (s > 0 ? '…' : '') + raw.replace(re, '<mark>$1</mark>') + '…';
  }
  function renderResults() {
    const q = input.value.trim();
    if (!q) { results.hidden = true; return; }
    const r = search(q);
    active = r.length ? 0 : -1;
    results.innerHTML = r.length
      ? r.map((e, i) => `<li class="${i === 0 ? 'active' : ''}"><a href="#/${e.id}"><strong>${e.title}</strong><small>${e.group} · ${snippet(e, q)}</small></a></li>`).join('')
      : '<li class="none">Keine Treffer.</li>';
    results.hidden = false;
  }
  input.addEventListener('input', renderResults);
  input.addEventListener('keydown', (ev) => {
    const items = [...results.querySelectorAll('li:not(.none)')];
    if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
      ev.preventDefault();
      if (!items.length) return;
      active = (active + (ev.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
      items.forEach((li, i) => li.classList.toggle('active', i === active));
      items[active].scrollIntoView({ block: 'nearest' });
    } else if (ev.key === 'Enter' && items[active]) {
      location.hash = items[active].querySelector('a').getAttribute('href');
      input.blur();
    } else if (ev.key === 'Escape') { results.hidden = true; input.blur(); }
  });
  results.addEventListener('click', () => { results.hidden = true; input.value = ''; });
  document.addEventListener('click', (ev) => { if (!ev.target.closest('.search')) results.hidden = true; });
  document.addEventListener('keydown', (ev) => {
    if (ev.key === '/' && !/input|textarea/i.test(document.activeElement.tagName)) { ev.preventDefault(); input.focus(); }
  });

  // Menü, Theme, Lightbox
  $('#menu-toggle').addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-open');
    $('#menu-toggle').setAttribute('aria-expanded', open);
  });
  const root = document.documentElement;
  const saved = localStorage.getItem('b10-theme');
  if (saved) root.dataset.theme = saved;
  $('#theme-toggle').addEventListener('click', () => {
    const dark = root.dataset.theme === 'dark' || (!root.dataset.theme && matchMedia('(prefers-color-scheme: dark)').matches);
    root.dataset.theme = dark ? 'light' : 'dark';
    localStorage.setItem('b10-theme', root.dataset.theme);
  });
  content.addEventListener('click', (ev) => {
    if (ev.target.tagName !== 'IMG') return;
    const lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.innerHTML = `<img src="${ev.target.src}" alt="">`;
    lb.addEventListener('click', () => lb.remove());
    document.body.appendChild(lb);
  });

  buildNav();
  window.addEventListener('hashchange', route);
  route();
})();
