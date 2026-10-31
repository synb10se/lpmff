WikiKit.add({
  id: 'schluessel',
  group: 'Fahrzeug',
  title: 'Schlüssel & Zugang',
  keywords: 'nfc key app bluetooth tür verriegeln kindersicherung',
  html: ({ path }) => `
<ul><li><b>NFC-Schlüsselkarte:</b> Für mindestens 1 s an den Fahrer-Außenspiegel halten. Zum Starten auf die kabellose Ladefläche legen und Bremse treten.</li><li><b>Mechanischer Schlüssel:</b> Notfallöffnung der Fahrertür.</li><li><b>Bluetooth-Schlüssel:</b> Digitaler Schlüssel in der Leapmotor-App.</li><li><b>Kindersicherung:</b> Hintere Türen und Fenstersperre (siehe <a href="#/tueren">Türen und Fenster</a> und <a href="#/sicherheit">Sicherheit &amp; Kinder</a>).</li><li><b>Automatische Verriegelung</b> ab etwa 15 km/h.</li></ul>
<h2>Leapmotor-App</h2>
<p>Registrieren, Fahrzeug per QR-Code verknüpfen (${path('Einstellungen', 'System', 'Sicherheit', 'Eigentümer-Authentifizierung')} laut Handbuch; siehe <a href="#/system">System</a>), danach Fernsteuerung, <a href="#/klima">Klimaplanung</a> und digitaler Schlüssel.</p>`
});
