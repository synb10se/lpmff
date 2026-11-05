WikiKit.add({
  id: 'start',
  group: 'Einstieg',
  title: 'Willkommen',
  keywords: 'einführung start übersicht version versionsverlauf android ios',
  html: ({ card, info, warn }) => `
<p>Die Leapmotor App gibt es für <b>Android</b> (Google Play Store) und <b>iOS</b> (App Store). Sie steuert wichtige Fahrzeugfunktionen, zeigt Daten zu Laden und Fahrleistung an und kann das Smartphone als <a href="#/schluessel">Bluetooth-Schlüssel</a> nutzbar machen.</p>
${info('Diese Anleitung beschreibt die App-Version <b>1.16</b> (Stand Oktober 2026). Die Bildschirmfotos stammen von der iOS-Version. Die Android-Oberfläche kann abweichen.')}
${warn('Zur Aktivierung der App wird die E-Mail-Adresse verwendet, die der Händler beim Kaufabschluss abfragt und an Leapmotor weitergibt.')}
<h2>Die zwei Hauptbereiche</h2>
<div class="cards">
${card('fahrzeug', 'Mein Fahrzeug', 'Status, Reichweite, Laden, Klima, Standort')}
${card('zentrum', 'Persönliches Zentrum', 'Fahrzeuge, Konto und App-Einstellungen')}
</div>
<h2>Erste Schritte</h2>
<ul>
<li><a href="#/einrichtung">Fahrzeug einrichten</a> – QR-Code scannen, Betriebspasswort, Verbindungsprobleme</li>
<li><a href="#/schluessel">Bluetooth-Schlüssel &amp; Fahrzeug teilen</a></li>
</ul>
<h2>Versionsverlauf (Auszug)</h2>
<table>
<thead><tr><th>Version</th><th>seit (ca.)</th><th>Neuerungen</th></tr></thead>
<tbody>
<tr><td>1.16</td><td>10/2026</td><td>Menü „Energieverbrauch Fahrleistung“ komplett neu, Ladehistorie nach DC und AC</td></tr>
<tr><td>1.15</td><td>09/2026</td><td>Widget-Fehlerbehebung, Transparenz bei Open-Source-Komponenten, visuelle Optimierungen</td></tr>
<tr><td>1.14</td><td>07/2026</td><td>Überarbeitete Innenraumansicht bei den Temperatureinstellungen, neue Länder und zwölf neue Sprachen</td></tr>
<tr><td>1.13</td><td>06/2026</td><td>Positionsübertragung aus Google Maps ans Fahrzeug, Geofencing, Datenverbrauchsalarm, OTA-Unterstützung</td></tr>
<tr><td>1.12.3</td><td>05/2026</td><td>Neues Design „Mein Fahrzeug“, separate Ladevorgangs-Karte</td></tr>
<tr><td>1.11.2</td><td>04/2026</td><td>Bluetooth-Schlüssel mit iOS-Geräten korrigiert, neue Fahrzeugmodelle</td></tr>
<tr><td>1.10.6</td><td>02/2026</td><td>Erstes Release für Mitteleuropa</td></tr>
<tr><td>1.9.5</td><td>01/2026</td><td>Widgets auf „Mein Fahrzeug“</td></tr>
<tr><td>1.8.3</td><td>12/2025</td><td>Ladestartfunktion, Marktstart Südafrika</td></tr>
<tr><td>1.7.3</td><td>10/2025</td><td>Fahrzeug teilen für Familie und Freunde</td></tr>
<tr><td>1.6.0</td><td>08/2025</td><td>Höchstgeschwindigkeit per App, Einheiten km/mi, Erinnerung Batteriezustand</td></tr>
</tbody>
</table>
<p class="footer-note">Quelle: <a href="https://www.lpmff.de/wiki/entry/17-leapmotor-app-alle-modelle/" target="_blank" rel="noopener">LPMFF-Wiki „Leapmotor App [alle Modelle]“</a></p>`
});
