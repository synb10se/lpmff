WikiKit.add({
  id: 'einrichtung',
  group: 'Einstieg',
  title: 'Fahrzeug einrichten',
  keywords: 'qr code scannen hinzufügen verknüpfung betriebspasswort pin verbindung datenschutzberechtigungen',
  html: ({ fig, path, info, tip, warn }) => `
<h2>Fahrzeug per QR-Code verknüpfen</h2>
<ol>
<li>Im Fahrzeug im Bildschirmmenü ${path('Einstellungen', 'System')} die <b>Authentifizierung des Fahrzeughalters</b> öffnen. Es wird ein QR-Code angezeigt.</li>
<li>In der App ${path('Persönliches Zentrum')} öffnen und das <b>QR-Code-Symbol</b> oben rechts antippen oder <b>Fahrzeug hinzufügen</b> wählen.</li>
<li>QR-Code scannen. Bei Erfolg erscheinen die Fahrzeuginformationen. Nach der ersten Verbindung über das Internet werden alle aktuellen Einstellungen übernommen.</li>
</ol>
${fig('persoenliches-zentrum-ersteinrichtung', 'Persönliches Zentrum vor der Verknüpfung: Fahrzeug hinzufügen und QR-Symbol')}
${fig('qr-code-scannen', 'QR-Code scannen')}
${fig('persoenliches-zentrum-fahrzeug-hinzufuegen', 'Fahrzeug hinzufügen')}
${info('Pro Fahrzeug ist immer nur <b>eine</b> Verknüpfung zu <b>einem</b> Gerät möglich. Mehrere Fahrzeuge in einem Konto sind möglich. Weitere Fahrer nutzen <a href="#/schluessel">„Fahrzeugschlüssel teilen“</a> (bis zu acht Freunde).')}
${tip('Bei mehreren Fahrzeugen (Familie/Firma) können mehrere Hauptbenutzer mit unterschiedlichen E-Mail-Adressen eingerichtet werden, die sich gegenseitig die Schlüssel teilen. Das Starten läuft dann jeweils über das Betriebspasswort (4-stelliger Code).')}
<h2>Fehlermeldung „Kein Betriebspasswort festgelegt“</h2>
<p>Das Passwort wird lokal im Fahrzeug gespeichert und muss zum Server übertragen werden. Schläft das Fahrzeug nie komplett ein, bleibt die Übertragung manchmal hängen.</p>
<ol>
<li><b>Passwort im Fahrzeug neu setzen:</b> ${path('Systemeinstellungen', 'Sicherheit', 'Passwort ändern')}, neue 4-stellige PIN vergeben und bestätigen. Der Speichervorgang muss einmal sauber durchlaufen.</li>
<li><b>Fahrzeug „schlafen legen“:</b> Wählhebel auf <b>P</b>, aussteigen und abschließen (am besten über die Karte am Spiegel). <b>5–10 Minuten warten</b>, das Fahrzeug nicht per App wecken und nicht einsteigen.</li>
<li><b>App neu starten:</b> App vollständig schließen (auch im Hintergrund), optional ab- und wieder anmelden.</li>
<li><b>Test:</b> In der App z. B. die Heizung starten. Bei der PIN-Abfrage die im Fahrzeug festgelegte PIN eingeben.</li>
</ol>
<h2>Verbindungsprobleme</h2>
<p>Im Fahrzeug unter ${path('System', 'Allgemein', 'Verwaltung der Datenschutzberechtigungen')} müssen alle notwendigen Schalter aktiviert sein.</p>
${warn('Auf dem Smartphone müssen außerdem Standort, Bluetooth und ggf. Kamera für die App erlaubt sein (siehe <a href="#/einstellungen">Einstellungen → Datenschutzzentrum</a>).')}`
});
