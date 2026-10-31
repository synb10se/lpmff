WikiKit.add({
  id: 'klima',
  group: 'Fahren',
  title: 'Klimaanlage',
  keywords: 'temperatur heizung sitzheizung lüftung entfrosten umluft',
  html: ({ tip }) => `
<p>Die Klimaanlage bedienen Sie über die untere Leiste: Temperatur mit den Pfeilen (18–32 °C), Zusatzfunktionen über die Symbole für Klimamenü, Lenkrad-, Sitzheizung bzw. -belüftung (je nach Ausstattung).</p>
<ul><li><b>Modi laut Handbuch:</b> Auto, Kühlen, Heizen, Lüften, Max-AC, Entfrosten, Öko, Schimmelschutz.</li><li><b>Luftverteilung:</b> Oberkörper, Fußraum, Windschutzscheibe.</li><li><b>Umluft/Frischluft</b> und Gebläsestufen 1–7.</li></ul>
<p>Über die Leapmotor-App (siehe <a href="#/schluessel">Schlüssel &amp; Zugang</a>) lässt sich die Klimaanlage vor Fahrtantritt planen. Die Einstellungen werden pro Profil gespeichert (siehe <a href="#/profile">Benutzerprofile</a>).</p>
${tip('Vorklimatisieren während des Ladens schont die Reichweite.')}`
});
