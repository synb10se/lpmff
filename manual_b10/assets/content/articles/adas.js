WikiKit.add({
  id: 'adas',
  group: 'Fahren',
  title: 'Fahrerassistenz (ADAS)',
  keywords: 'acc spurhalte notbremse kollisionswarnung toter winkel kamera 360 tempolimit querverkehr',
  html: ({ fig, path, warn, info }) => `
<p>${path('Einstellungen', 'ADAS')} bündelt Assistenzsysteme in drei Reitern.</p>
${warn('Assistenzsysteme unterstützen nur. Der Fahrer bleibt jederzeit verantwortlich und muss die Hände am Lenkrad halten. Systemgrenzen bei schlechter Sicht, Verschmutzung oder Witterung beachten.')}
<h2>Fahrerassist.</h2>
<ul><li><b>Spurzentrierassistent:</b> Hält das Fahrzeug in der Spur und regelt Geschwindigkeit und Abstand zum Vordermann (ACC/LCC).</li><li><b>Intelligenter Geschwindigkeitsassistent:</b> Erkennt Tempolimits und warnt bei Überschreitung (Warnsymbol, optional Alarm).</li></ul>
${fig('adas-fahrerassistenz', 'Reiter Fahrerassist.')}
<h2>Akt. Sicherh.</h2>
<ul><li>Frontalkollisionswarnung (Sensitivität niedrig/mittel/hoch) und Automatische Notbremsung</li><li>Spurverlassenswarnung und Spurhalteassistent (Warnen, Spur halten, Beides; Empfindlichkeit; Warnton; Lenkradvibration)</li><li>Notspurhalteassistent</li><li>Toter-Winkel-Erkennung und Türöffnungswarnung</li><li>Heckkollisionswarnung und Warnung Querverkehr hinten (Warnen oder Warnen + Bremsen)</li><li>Ablenkungswarnung bei erkannter Fahrerunaufmerksamkeit</li></ul>
${fig('adas-akt-sicherheit', 'Reiter Akt. Sicherh.')}
<h2>Bildassistent</h2>
<ul><li><b>Lenkassistenzbild:</b> Beim Blinken Heck- und Seitenansicht.</li><li><b>Fahrzeugstartassistent:</b> 360°-Ansicht beim Einlegen der Fahrstufe.</li><li><b>Ansicht Berghilfe:</b> 360°-Ansicht an Steigungen.</li><li><b>Bild halten:</b> Behält die Ansicht beim Wechsel von R auf D.</li></ul>
${fig('adas-bildassistent', 'Reiter Bildassistent')}
${info('Die Lenkradtasten bedienen ACC (Abstandsregeltempomat) und Spurführung; Betriebsbereich laut Handbuch etwa 30–150 km/h.')}`
});
