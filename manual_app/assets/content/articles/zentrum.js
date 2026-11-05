WikiKit.add({
  id: 'zentrum',
  group: 'Persönliches Zentrum',
  title: 'Persönliches Zentrum',
  keywords: 'persönliches zentrum fahrzeugname höchstgeschwindigkeit fahrzeug hinzufügen kontakt hilfe pannenhilfe benutzerhandbuch',
  html: ({ fig, path, info }) => `
<p>Im Persönlichen Zentrum (rechter Reiter der unteren Leiste) liegen die Fahrzeugverwaltung und alle App-Einstellungen. Oben rechts finden Sie den <b>QR-Scanner</b> und das <a href="#/fahrzeug">Nachrichtenzentrum</a>.</p>
${fig('persoenliches-zentrum', 'Persönliches Zentrum')}
<h2>Einträge</h2>
<ul>
<li><b>Mein Fahrzeug:</b> Fahrzeugname, FIN, Verknüpfung aufheben und <a href="#/schluessel">Gemeinsame Mitglieder</a>.</li>
<li><b>Höchstgeschwindigkeit:</b> Begrenzung per App.</li>
<li><b>Fahrzeug hinzufügen:</b> <a href="#/einrichtung">Verknüpfung per QR-Code</a>.</li>
<li><b>Kontakt:</b> Link zur offiziellen Leapmotor-Webseite.</li>
<li><b>Hilfe:</b> Kontaktdaten der Pannenhilfe nach Ländern.</li>
<li><b>Einstellungen:</b> siehe <a href="#/einstellungen">App-Einstellungen</a>.</li>
<li><b>Benutzerhandbuch:</b> Das Handbuch des Fahrzeugs.</li>
</ul>
${info('Solange noch kein Fahrzeug verknüpft ist, fehlen „Mein Fahrzeug“, „Höchstgeschwindigkeit“ und „Benutzerhandbuch“.')}
<h2>Mein Fahrzeug</h2>
${fig('persoenliches-zentrum-mein-fahrzeug', 'Fahrzeugliste')}
${fig('persoenliches-zentrum-mein-fahrzeug-fahrzeugname', 'Fahrzeugnamen ändern')}
<h2>Höchstgeschwindigkeit</h2>
<p>Die Begrenzung lässt sich zwischen 80 und 145 km/h festlegen und nicht während der Fahrt ändern. Sinnvoll z. B. für Fahranfänger oder Werkstatt-/Verleihfahrten.</p>
${fig('persoenliches-zentrum-hoechstgeschwindigkeit', 'Höchstgeschwindigkeit')}
<h2>Kontakt, Hilfe, Benutzerhandbuch</h2>
${fig('persoenliches-zentrum-kontakt', 'Kontakt')}
${fig('persoenliches-zentrum-hilfe', 'Hilfe mit Pannenhilfe-Kontakten')}
${fig('persoenliches-zentrum-benutzerhandbuch', 'Benutzerhandbuch')}`
});
