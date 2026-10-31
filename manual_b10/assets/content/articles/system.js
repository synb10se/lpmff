WikiKit.add({
  id: 'system',
  group: 'Infotainment',
  title: 'System, Sicherheit & Updates',
  keywords: 'update ota version airbag passwort neustart werkseinstellungen sprache zeitzone datenschutz',
  html: ({ fig, path, warn, caution }) => `
<p>${path('Einstellungen', 'System')} hat die Reiter „Sicherheit“, „Wartung“ und „Allgemein“.</p>
<h2>Sicherheit</h2>
${fig('system-sicherheit', 'System: Sicherheit')}
<ul><li><b>Passwort zum Starten des Fahrzeugs</b> (Passwort festlegen/ändern; siehe <a href="#/fahren">Fahreinstellungen</a>).</li><li><b>Innenraumüberwachung</b> mit optionaler verzögerter Erkennung.</li><li><b>Zugmodus</b> beim Anhängerbetrieb (siehe <a href="#/wartung">Wartung &amp; Notfall</a>).</li><li><b>Beifahrerairbag:</b> Ein/Aus. Der Status wird im Kombiinstrument angezeigt.</li><li><b>Passw. Höchstg. festl.:</b> Passwort für die Höchstgeschwindigkeits-Begrenzung.</li><li><b>Authentifizierung widerrufen:</b> Hebt die Verknüpfung mit dem Fahrzeughalter (Leapmotor-App) auf.</li></ul>
${warn('Beifahrerairbag niemals bei Rückwärtsgerichteten Kindersitzen auf dem Beifahrersitz eingeschaltet lassen. Vor dem Fahren Status prüfen. Mehr unter <a href="#/sicherheit">Sicherheit &amp; Kinder</a>.')}
<h2>Wartung</h2>
${fig('system-wartung', 'System: Wartung')}
<ul><li><b>Stromversorgung des Fahrzeugs:</b> „Für Wartung ausschalten“ und „Einschalten“.</li><li><b>Elektronische Parkbremse (EPB).</b></li><li><b>Infotainmentsystem neu starten</b> bei Fehlfunktionen.</li><li><b>Schnellstart Fahrzeugsystem.</b></li><li><b>Wartung des Frontscheibenwischers:</b> Wischer in Wechselstellung, nur bei ausgeschaltetem Fahrzeug.</li></ul>
<h2>Allgemein und Updates</h2>
<p>Hier sehen Sie die LeapOS-Version und prüfen auf Updates. Updates können automatisch über WLAN geladen werden. Weiterhin: Cache leeren, Sprache, Zeitzone, Werkseinstellungen sowie Datenschutzerklärung, Open-Source-Lizenzen und Datenschutzberechtigungen.</p>
${fig('system-update-fertig', 'Meldung nach erfolgreichem Update')}
${caution('Für OTA-Updates sollte der Akku laut Handbuch ≥ 20 % haben, das Fahrzeug in P stehen und die Parkbremse aktiv sein. Das Fahrzeug währenddessen nicht bedienen.')}
${caution('„Zurücksetzen“ löscht persönliche Daten und Einstellungen.')}`
});
