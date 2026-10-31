WikiKit.add({
  id: 'tueren',
  group: 'Fahrzeug',
  title: 'Türen, Fenster, Heckklappe',
  keywords: 'fenster heckklappe kofferraum sonnenblende panoramadach fenstersperre kindersicherung',
  html: ({ fig, path, warn, caution }) => `
<p>Unter ${path('Einstellungen', 'Türen und Fenster')} steuern Sie Fenster, Sonnenblende und Heckklappe.</p>
${fig('doorswindows', 'Türen und Fenster')}
<h2>Fenster</h2>
<ul><li><b>Fenstersperre:</b> Sperrt die hinteren Fensterheber (Kindersicherung, siehe <a href="#/sicherheit">Sicherheit &amp; Kinder</a>).</li><li><b>Fenster:</b> Öffnet die Detaileinstellung; Fahrer- oder Beifahrerseite wählen, Fenster durch Schieben verstellen oder „Vollständig geöffnet“, „Geschlossen“, „Leicht geöffnet“ antippen.</li><li><b>Fensterposition beim Verriegeln:</b> Geschlossen, Aktuelle Position oder Leicht geöffnet.</li></ul>
${fig('fenster-detaileinstellung', 'Fenster-Detaileinstellung')}
${warn('Beim Schließen der Fenster darauf achten, dass niemand eingeklemmt wird, besonders Kinder.')}
<h2>Sonnenblende</h2><p>Das Panoramadach ist mit einer elektrischen Sonnenblende ausgestattet. „Sonnenblende einst.“ erlaubt, den abgedeckten Bereich durch Schieben anzupassen.</p>
${fig('doorswindows-sonnenblende', 'Sonnenblende einstellen')}
<h2>Heckklappe</h2><p>„Heckklappe“ öffnet oder schließt sie elektrisch. Mit „Heckklappenhöhe“ legen Sie die Öffnungshöhe (20–100 %) fest, z. B. für niedrige Garagen.</p>
${fig('doorswindows-heckklappe', 'Heckklappenhöhe einstellen')}
${caution('Vor dem Öffnen die Deckenhöhe prüfen und eine passende Höhe einstellen.')}`
});
