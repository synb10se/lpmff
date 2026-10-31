WikiKit.add({
  id: 'licht',
  group: 'Fahrzeug',
  title: 'Licht & Ambientebeleuchtung',
  keywords: 'scheinwerfer fernlicht nebel ambientelicht farbe',
  html: ({ fig, path }) => `
<p>${path('Einstellungen', 'Beleuchtung')} hat die Reiter „Beleucht.“ und „Ambienteleuchten“.</p>
${fig('beleuchtung', 'Scheinwerfer und Leseleuchten')}
<h2>Scheinwerfer</h2>
<p>Wählen Sie Aus, Standlicht, Abblendlicht oder Auto. Außerdem: Nebelscheinwerfer vorn, Nebelschlussleuchte hinten und Leuchtweitenregulierung („Scheinwerferhöhe“). Fernlicht, Lichthupe und Blinker bedienen Sie am Lenkstockhebel.</p>
<h2>Leseleuchten und Komfort</h2>
<ul><li><b>Autom. Fernlicht:</b> Blendet automatisch ab und auf.</li><li><b>Beleuchtungsverzögerung:</b> Scheinwerfer erlöschen 30 s nach dem Verriegeln.</li><li><b>Automatische Leseleuchten:</b> Gehen beim Öffnen der Tür an.</li></ul>
<h2>Ambienteleuchten</h2>
<p>Ein, Aus oder Auto; Farbe über Schnellauswahl oder Farbfeld; Effekt (Statisch, Einfarbiges Blinken, Blinken mit Farbwechsel); Anzeigebereich (gesamtes Fahrzeug oder Mitte); Helligkeit. „Ambientelichteffekte“ zeigen z. B. Parkwarnungen oder Ladevorgänge an, „Musikrhythmus“ lässt das Licht zur Musik pulsieren.</p>
${fig('ambiente', 'Ambienteleuchten')}`
});
