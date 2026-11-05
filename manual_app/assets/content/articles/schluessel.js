WikiKit.add({
  id: 'schluessel',
  group: 'Mein Fahrzeug',
  title: 'Schlüssel & Teilen',
  keywords: 'bluetooth schlüssel annäherung entriegeln verriegeln erfassungsbereich fehlerdiagnose fahrzeugschlüssel teilen freunde gemeinsames mitglied home assistant abrp',
  html: ({ fig, path, info, warn, tip }) => `
<h2>Bluetooth-Schlüssel</h2>
<p>Die Karte „Bluetooth-Schlüssel“ auf „Mein Fahrzeug“ aktiviert und konfiguriert die Annäherungsfunktion.</p>
${fig('meinfahrzeug-bluetooth-schluessel', 'Bluetooth-Schlüssel')}
<h3>Entriegelungs- und Verriegelungsmethoden</h3>
<ul>
<li>Automatisches Entriegeln, sobald das Handy im Annäherungsbereich erkannt wird</li>
<li>Automatisches Verriegeln, sobald das Handy den Bereich verlässt</li>
<li>Ent-/Verriegeln über den Türgriff, wenn das Handy im Annäherungsbereich ist</li>
</ul>
${fig('meinfahrzeug-bluetooth-schluessel-entriegelung-und-verriegelung', 'Methoden wählen')}
<h3>Erfassungsbereich</h3>
${fig('meinfahrzeug-bluetooth-schluessel-erfassungsbereich', 'Erfassungsbereich einstellen')}
<ul><li><b>Nah:</b> bis ca. 3 m</li><li><b>Standard:</b> ca. 2–6 m</li><li><b>Fern:</b> ca. 5–15 m</li></ul>
${info('Die Meter-Angaben sind empirische Schätzwerte der Community. Der tatsächliche Bereich hängt vom Handy und der Annäherungsrichtung ab.')}
<h3>Fehlerdiagnose</h3>
<p>Die Fehlerdiagnose prüft nur die Funktion und liefert keine weiteren Informationen. Sie funktioniert nur, wenn sich das Handy im Annäherungsbereich befindet.</p>
${fig('meinfahrzeug-bluetooth-schluessel-fehlerdiagnose', 'Fehlerdiagnose')}
${fig('meinfahrzeug-bluetooth-schluessel-fehlerdiagnose-ergebnis', 'Ergebnis der Fehlerdiagnose')}
${warn('Die Standortberechtigung der App muss auf „Immer“ stehen. Sonst funktionieren Bluetooth-Hintergrundverbindung und automatisches Ver-/Entriegeln nicht zuverlässig.')}
<h2>Fahrzeugschlüssel teilen</h2>
<p>Bis zu <b>acht Freunde</b> können das Fahrzeug über die App mitnutzen.</p>
<h3>Voraussetzung</h3>
<p>Die andere Person hat die App mit einer <b>anderen E-Mail-Adresse</b> als der Halter eingerichtet und ist angemeldet.</p>
<h3>Einrichtung</h3>
<ol>
<li>${path('Persönliches Zentrum', 'Mein Fahrzeug')} öffnen und das Fahrzeug wählen.</li>
<li><b>Gemeinsames Mitglied</b> antippen, dann unten <b>Gemeinsames Mitglied hinzufügen</b>.</li>
<li>E-Mail-Adresse des Mitglieds eingeben.</li>
<li>Die andere Person bestätigt in ihrer App. Das Fahrzeug erscheint dort als „Fahrzeug eines Freunds“.</li>
</ol>
${fig('persoenliches-zentrum-mein-fahrzeug-fahrzeugverknuepfung', 'Fahrzeugverknüpfung mit „Gemeinsames Mitglied“')}
${fig('persoenliches-zentrum-mein-fahrzeug-gemeinsame-mitglieder', 'Gemeinsame Mitglieder (Freigegeben x/8) mit „Entfernen“ und „Gemeinsames Mitglied hinzufügen“')}
<p>Über <b>Entfernen</b> neben einem Mitglied wird die Freigabe wieder aufgehoben. Angezeigt wird, wie viele der acht möglichen Plätze belegt sind.</p>
${tip('Der Hauptnutzer sollte sich danach nicht mehr aus der App abmelden.')}
${warn('Pro Benutzer ist nur <b>eine</b> Verbindung zu einem Gerät oder Dienst gleichzeitig möglich (Identifizierung über die E-Mail-Adresse). Für Home Assistant, ABRP u. Ä. wird dringend ein zweiter „technischer“ Benutzer mit eigener E-Mail-Adresse empfohlen, damit der Hauptbenutzer nicht abgemeldet wird.')}`
});
