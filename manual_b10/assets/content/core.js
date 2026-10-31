// Gemeinsame Hilfen für alle Artikel. Muss vor menu.js und den Artikeln geladen werden.
(function () {
  const kit = {
    fig: (n, c) => `<figure><img src="img/${n}.jpg" alt="${c}" loading="lazy"><figcaption>${c}</figcaption></figure>`,
    path: (...p) => `<span class="menu-path">${p.join(' → ')}</span>`,
    box: (type, title, text) => `<div class="callout ${type}"><b>${title}</b>${text}</div>`,
    card: (id, t, s) => `<a class="card" href="#/${id}"><strong>${t}</strong><span>${s}</span></a>`
  };
  kit.warn = (t) => kit.box('warn', 'Warnung', t);
  kit.caution = (t) => kit.box('caution', 'Vorsicht', t);
  kit.info = (t) => kit.box('info', 'Hinweis', t);
  kit.tip = (t) => kit.box('tip', 'Tipp', t);

  const wiki = {
    groups: [],
    pages: {},
    disclaimer: 'Inoffizielle Kurzanleitung auf Basis von LeapOS 3.41.30 und dem Benutzerhandbuch. Verbindlich ist das Original-Benutzerhandbuch.'
  };

  // Artikel registrieren: html erhält die Hilfsfunktionen (fig, path, warn, caution, info, tip, card).
  window.WikiKit = {
    add({ id, group, title, keywords = '', html }) {
      wiki.pages[id] = { group, title, keywords, html: html(kit) };
    }
  };
  window.WIKI = wiki;
})();
