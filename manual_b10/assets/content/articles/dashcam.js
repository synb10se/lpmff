WikiKit.add({
  id: 'dashcam',
  group: 'Infotainment',
  title: 'Dashcam',
  keywords: 'fahrtrekorder usb video kamera aufnahme',
  html: ({ fig, info }) => `
<p>Die Dashcam zeichnet die 360°-Kameras (Ansichten: <a href="#/adas">Fahrerassistenz</a>) auf einem USB-Stick auf. Laut Handbuch: FAT32, 64–256 GB, Anschluss in der unteren Mittelkonsole. Öffnen Sie die App „Fahrtrekorder“ über die App-Übersicht.</p>
<h2>Reiter</h2>
<ul><li><b>Echtzeitbild:</b> Live-Ansicht aller vier Kameras mit Aufnahmetaste.</li><li><b>Videoschleife:</b> Dauerhafte Schleifenaufnahmen, nach Stunden gruppiert.</li><li><b>Notfallvideo:</b> Gesicherte Aufnahmen bei Ereignissen, z. B. Kollisionen.</li><li><b>Einstellungen:</b> Fahrtrekorder ein/aus, Speicherbelegung, „Fahrtvideo löschen“.</li></ul>
${fig('dashcam-echtzeit', 'Echtzeitbild mit Aufnahmetaste')}${fig('dashcam-loop', 'Videoschleife')}${fig('dashcam-emergency', 'Notfallvideo')}${fig('dashcam-settings', 'Einstellungen und USB-Speicherplatz')}
${info('Aufnahmen erst nach Aktivierung der Funktion. Ist der Speicher voll, Videos löschen. Beachten Sie die Datenschutzregeln beim Veröffentlichen von Aufnahmen.')}`
});
