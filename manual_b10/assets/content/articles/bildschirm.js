WikiKit.add({
  id: 'bildschirm',
  group: 'Bedienung',
  title: 'Hauptbildschirm & Statusleiste',
  keywords: 'wlan bluetooth gps usb nachrichten statusleiste widgets esc',
  html: ({ fig, warn }) => `
<p>Der Hauptbildschirm zeigt das Fahrzeug und vier Widgets: Navigationssuche (Heim, Arbeit, Ladesäule), Medienwiedergabe, letzte Fahrt/Ladung und Reifendruck/-temperatur (Sollwerte: <a href="#/wartung">Wartung</a>). Unten befindet sich die untere Leiste mit Apps, <a href="#/klima">Klimabedienung</a> und Sitzfunktionen. Ihre Anordnung passen Sie unter <a href="#/anzeige">Anzeige</a> an.</p>
${fig('benutzerwillkommen', 'Hauptbildschirm mit Widgets und unterer Leiste')}
<h2>Statusleiste oben</h2>
<table><tr><th>Symbol</th><th>Funktion</th></tr>
<tr><td>Profilbild</td><td>Öffnet die <a href="#/profile">Benutzerprofile</a>.</td></tr>
<tr><td>Uhrzeit</td><td>Antippen zeigt das aktuelle Datum mit Wochentag.</td></tr>
<tr><td>Batterie mit %</td><td>Ladezustand (SoC). Antippen zeigt auch die Restreichweite und führt zu den Lade-/Entladeeinstellungen (siehe <a href="#/laden">Laden &amp; Entladen</a>).</td></tr>
<tr><td>Schloss, Ladeanzeige, Aufnahme</td><td>Verriegelungs-, Lade- und <a href="#/dashcam">Dashcam</a>-Status (Aufnahmesymbol).</td></tr>
<tr><td>USB, Bluetooth, WLAN/Mobilfunk, Glocke, GPS</td><td>Antippen öffnet das jeweilige Schnellmenü.</td></tr></table>
${fig('header-datum', 'Antippen der Uhrzeit: Datum und Wochentag')}
${fig('header-soc', 'Antippen der Batterieanzeige: Ladezustand und Reichweite')}
${fig('header-esc', 'Sicherheitsabfrage beim Deaktivieren des ESC')}
${warn('Ein deaktiviertes ESC erhöht die Gefahr, die Kontrolle über das Fahrzeug zu verlieren. Nur in Ausnahmefällen deaktivieren (ESC-Einstellung: <a href="#/fahren">Fahreinstellungen</a>).')}
<h2>Meldungen</h2>
<p>Die Glocke öffnet „Nachrichten“ (z. B. aus der Leapmotor-App geteilte Adressen, die Sie mit „Navigation“ übernehmen können) und das „Servicecenter“. Bei „GPS“ und „USB“ erscheinen Kurzmeldungen wie „GPS-Position erfasst“ oder „USB-Laufwerk angeschlossen“.</p>
${fig('header-nachrichten', 'Glocke: Nachrichten und Servicecenter')}
${fig('header-gps', 'Statusmeldung zur GPS-Position')}${fig('header-usb', 'Meldung beim Anschließen eines USB-Laufwerks')}
<h2>Bluetooth und WLAN</h2>
<p>Im Schnellmenü koppeln Sie Telefone (Bluetooth sichtbar schalten, Gerät auswählen) und verbinden das Fahrzeug mit WLAN, z. B. für <a href="#/system">Software-Updates</a>. Das Mobilfunknetz hat einen eigenen Reiter.</p>
${fig('header-netzwerk', 'WLAN-Schnellmenü mit Reiter Mobilfunknetz')}`
});
