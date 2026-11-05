WikiKit.add({
  id: 'fahrzeug',
  group: 'Mein Fahrzeug',
  title: 'Übersicht',
  keywords: 'mein fahrzeug heartbeat reichweite kofferraum fenster sonnenblende hupe standort fahrzeugzustand reifendruck nachrichten',
  html: ({ fig, path, info, tip }) => `
<p>Nach dem Öffnen der App zeigt „Mein Fahrzeug“ die Hauptfunktionen. Hersteller und Community empfehlen, die App ständig geöffnet zu lassen.</p>
${fig('meinfahrzeug', 'Übersicht „Mein Fahrzeug“')}
<ol>
<li><b>Kopfzeile:</b> Fahrzeugname, Zeit der letzten Aktualisierung (Statusupdate), Verriegelungsstatus und rechts das Herzsymbol für den <a href="#/fahrzeug">Fahrzeugzustand</a> (Heartbeat).</li>
<li><b>Reichweite</b> und Restladedauer, darunter individuell konfigurierbare Funktionstasten z.B. für <b>Kofferraum, Fenster, Sonnenblende</b> und <b>Fahrzeug per Hupe orten</b> (je nach Ausstattung).</li>
<li><b>Ladekarte:</b> Ladestand, Ladelimit, Ladeart und Restzeit, siehe <a href="#/laden">Laden</a>.</li>
<li><b>Innentemperatur:</b> siehe <a href="#/klima">Klima</a>.</li>
<li><b>Bluetooth-Schlüssel</b> mit Status, siehe <a href="#/schluessel">Schlüssel</a>.</li>
<li><b>Fahrzeugstandort</b> (GPS, Kartenausschnitt) mit Hupen-Taste.</li>
<li><b>Fahrzeugvorbereitung per Fingerdruck,</b> siehe <a href="#/klima">Klima</a>.</li>
<li><b>Energieverbrauch anzeigen:</b> siehe <a href="#/energie">Energieverbrauch</a>.</li>
</ol>
${info('Auf der unteren Leiste kann zwischen „Mein Fahrzeug“ und dem <a href="#/zentrum">Persönlichen Zentrum</a> gewechselt werden.')}
<h2>Fahrzeugzustand</h2>
<p>Das Herzsymbol oben rechts öffnet den Fahrzeugzustand, momentan nur mit dem <b>Reifendruck</b> aller vier Räder (kPa und bar).</p>
${fig('meinfahrzeug-fahrzeugzustand', 'Fahrzeugzustand mit Reifendruck')}
<h2>Fahrzeugstandort</h2>
<p>Die Karte zeigt die Position des Fahrzeugs, die Entfernung dorthin und eine Navigationstaste. Mit „Hupen“ lässt sich das Fahrzeug akustisch orten. Die Standortfreigabe der App muss dafür aktiv sein.</p>
${fig('meinfahrzeug-standort', 'Fahrzeugstandort mit Suche und Hupen-Taste')}
<h2>Nachrichtenzentrum</h2>
<p>Das Sprechblasen-Symbol oben rechts im <a href="#/zentrum">Persönlichen Zentrum</a> öffnet das Nachrichtenzentrum, z. B. mit „Ladevorgang gestartet“ und „Aufladen abgeschlossen“. „Alle als gelesen markieren“ setzt die roten Markierungen zurück.</p>
${fig('nachrichtenzentrum', 'Nachrichtenzentrum')}
${tip('Welche Meldungen per E-Mail oder App-Benachrichtigung kommen, legen Sie unter <a href="#/einstellungen">Einstellungen → Benachrichtigung</a> fest.')}`
});
