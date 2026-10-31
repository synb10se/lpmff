WikiKit.add({
  id: 'anzeige',
  group: 'Infotainment',
  title: 'Anzeige',
  keywords: 'dunkel hell helligkeit bildschirm reinigen untere leiste',
  html: ({ fig, path }) => `
<p>${path('Einstellungen', 'Anzeige')}</p>
${fig('anzeige', 'Anzeigeeinstellungen')}
<ul><li><b>Anzeigemodus:</b> Hell oder Dunkel, optional automatisch nach Umgebungshelligkeit.</li><li><b>Bildschirmhelligkeit</b> mit automatischer Anpassung.</li><li><b>Entfernung:</b> km oder mi.</li><li><b>Bildschirmreinigung:</b> Schaltet den Touchscreen vorübergehend ab, damit beim Wischen nichts ausgelöst wird.</li><li><b>Benutzerdefinierte untere Leiste:</b> Reihenfolge der Apps und Funktionen anpassen.</li></ul>`
});
