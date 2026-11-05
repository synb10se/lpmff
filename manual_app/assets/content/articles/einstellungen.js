WikiKit.add({
  id: 'einstellungen',
  group: 'Persönliches Zentrum',
  title: 'App-Einstellungen',
  keywords: 'einstellungen konto sicherheit benachrichtigung sprache datenschutz face id fingerabdruck siri einheit km meilen benutzervereinbarung open source version abmelden',
  html: ({ fig, path, tip, info }) => `
<p>${path('Persönliches Zentrum', 'Einstellungen')} enthält alle Einstellungen der App selbst. Unten befindet sich die Schaltfläche <b>Abmelden</b>.</p>
${fig('persoenliches-zentrum-einstellungen', 'Einstellungen')}
<h2>Konto und Sicherheit</h2>
<p>E-Mail-Adresse, Passwort und die Möglichkeit, das Konto zu löschen.</p>
${fig('persoenliches-zentrum-einstellungen-konto-und-sicherheit', 'Konto und Sicherheit')}
<h2>Benachrichtigung</h2>
<p>Meldungen per E-Mail an die Kontoadresse und/oder als App-Benachrichtigung auf dem Gerät.</p>
${fig('persoenliches-zentrum-einstellungen-benachrichtigung', 'Benachrichtigung')}
<h2>Sprache</h2>
${fig('persoenliches-zentrum-einstellungen-sprache', 'Sprache')}
<h2>Datenschutzzentrum</h2>
<p>Systemberechtigungen für <b>Standort, Kamera, Bluetooth</b> und <b>Fingerabdruck/Face ID</b>.</p>
${fig('persoenliches-zentrum-einstellungen-datenschutzzentrum', 'Datenschutzzentrum')}
${tip('Für den <a href="#/schluessel">Bluetooth-Schlüssel</a> den Standort auf „Immer erlauben“ stellen.')}
<h2>Fingerabdruck/Face ID</h2>
<p>Separat ein-/ausschaltbar. Die Aktivierung erfordert einmalig die Eingabe des Betriebspassworts.</p>
${fig('persoenliches-zentrum-einstellungen-datenschutzzentrum-fingerabdruck-faceid', 'Fingerabdruck/Face ID')}
<h2>Siri-Sprachsteuerung (iOS)</h2>
<p>Siri-Befehle werden als Kurzbefehle auf dem Gerät gespeichert und funktionieren aktuell nur bei entsperrtem Gerät.</p>
${fig('persoenliches-zentrum-einstellungen-siri-sprachsteuerung', 'Siri-Sprachsteuerung')}
${fig('persoenliches-zentrum-einstellungen-siri-kurzbefehl-bearbeiten', 'Kurzbefehl bearbeiten')}
<h2>Einheit wechseln</h2>
<p>Umschaltung der Entfernungseinheit zwischen Kilometer und Meilen.</p>
${fig('persoenliches-zentrum-einstellungen-einheit-wechseln', 'Einheit wechseln')}
<h2>Rechtliches und Version</h2>
<ul>
<li><b>Datenschutzerklärung:</b> Hinweise zu Kontakt, Werbung und Partnerzugriffen.</li>
<li><b>Benutzervereinbarung</b> zur App.</li>
<li><b>Über Leapmotor:</b> aktuelle Versionsnummer der App.</li>
<li><b>Open-Source-Komponentenliste:</b> verwendete Open-Source-Bausteine (seit Version 1.15).</li>
</ul>
${fig('persoenliches-zentrum-einstellungen-datenschutzerklaerung', 'Datenschutzerklärung')}
${fig('persoenliches-zentrum-einstellungen-benutzervereinbarung', 'Benutzervereinbarung')}
${fig('persoenliches-zentrum-einstellungen-ueber-leapmotor', 'Über Leapmotor mit Versionsnummer')}
${fig('persoenliches-zentrum-einstellungen-open-source-komponentenliste', 'Open-Source-Komponentenliste')}`
});
