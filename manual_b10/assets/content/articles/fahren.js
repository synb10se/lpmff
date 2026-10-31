WikiKit.add({
  id: 'fahren',
  group: 'Fahren',
  title: 'Fahreinstellungen',
  keywords: 'beschleunigung rekuperation one-pedal auto hold esc geschwindigkeit hdc lenkmodus wischer',
  html: ({ fig, path, info, tip }) => `
<p>Alle Optionen unter ${path('Einstellungen', 'Fahren')}.</p>
${fig('fahren', 'Fahreinstellungen (mehrere Bildschirmabschnitte zusammengesetzt)')}
<table><tr><th>Einstellung</th><th>Wirkung</th></tr>
<tr><td>Beschl.modus</td><td>Komfort, Standard, Sport: Gaspedalreaktion und Leistungsabgabe.</td></tr>
<tr><td>Regeneratives Bremsen</td><td>Schwach, Mittel, Stark: Stärke der Energierückgewinnung beim Loslassen des Gaspedals.</td></tr>
<tr><td>Lenkmodus</td><td>Komfort, Standard, Sport: Lenkunterstützung.</td></tr>
<tr><td>Rückspiegel</td><td>„Außenspiegel einstellen“: Spiegel über das Lenkradrad verstellen.</td></tr>
<tr><td>Benutzerdefiniert</td><td>Belegung der benutzerdefinierten Lenkradtaste.</td></tr>
<tr><td>Höchstgeschwindigkeit</td><td>Begrenzung zwischen 80 und 145 km/h.</td></tr>
<tr><td>One-Pedal-Modus</td><td>Beschleunigen und Verzögern nur mit dem Gaspedal; nur in Parkstellung einstellbar.</td></tr>
<tr><td>Kriechmodus, Auto Hold</td><td>Anfahrverhalten und automatisches Halten an Steigungen und Ampeln.</td></tr>
<tr><td>ESC</td><td>Elektronisches Stabilitätsprogramm gegen Schleudern.</td></tr>
<tr><td>Super-Energiesparmodus</td><td>Reduziert Klima und Infotainment zugunsten der Reichweite.</td></tr>
<tr><td>Bergabfahrhilfe (HDC)</td><td>Hält bei Gefälle die Geschwindigkeit konstant.</td></tr>
<tr><td>Komfortstopp</td><td>Sanfteres Anhalten beim Bremsen.</td></tr>
<tr><td>Wischer</td><td>Empfindlichkeit des Regensensors und Wischintervall.</td></tr></table>
${info('Einige Einstellungen sind nur im Stand oder bei aktivierter Fahrbereitschaft verfügbar (ausgegraut dargestellt).')}
<h2>Fahrzeug starten und Gänge</h2>
<p>Das Fahrzeug startet mit Bremse und erkanntem Schlüssel. Wählen Sie R, N oder D am Schalthebel am Lenkrad; P aktivieren Sie per Taste. Mit dem Passwortstart (siehe <a href="#/system">System</a>) wird vor dem Start ein Passwort abgefragt.</p>
${tip('Ruhige Fahrweise und starke Rekuperation im Stadtverkehr erhöhen die Reichweite. Einfahrzeit laut Handbuch: 1000–2500 km, abrupte Beschleunigung vermeiden.')}`
});
