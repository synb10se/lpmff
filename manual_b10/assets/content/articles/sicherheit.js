WikiKit.add({
  id: 'sicherheit',
  group: 'Fahrzeug',
  title: 'Sicherheit & Kinder',
  keywords: 'gurt airbag kindersitz isofix',
  html: ({ warn }) => `
${warn('Immer anschnallen. Gurte nicht verdreht oder unter dem Arm tragen. Kinder sicher in einem geeigneten, zugelassenen Kindersitz befördern (ISOFIX vorhanden).')}
<ul><li>Beifahrerairbag abschaltbar: <a href="#/system">System → Sicherheit</a>.</li><li>Kindersitzeignung je Sitzplatz: Tabellen im Benutzerhandbuch beachten.</li><li>Hintere Fenster über die Fenstersperre blockieren, siehe <a href="#/tueren">Türen und Fenster</a>.</li></ul>`
});
