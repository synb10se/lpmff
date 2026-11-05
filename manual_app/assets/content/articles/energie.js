WikiKit.add({
  id: 'energie',
  group: 'Mein Fahrzeug',
  title: 'Energieverbrauch',
  keywords: 'energieverbrauch fahrleistung statistik woche monat jahr kilometer kwh fahrtdetails',
  html: ({ fig, info }) => `
<p>Die Karte „Energieverbrauch anzeigen“ am Ende von „Mein Fahrzeug“ öffnet die Statistik <b>Energieverbrauch Fahrleistung</b>. Sie wurde mit Version 1.16 komplett neu gestaltet. Oben steht die Gesamtfahrleistung.</p>
<h2>Zeiträume</h2>
<p>Die Reiter <b>Woche</b>, <b>Monat</b> und <b>Jahr</b> schalten den Betrachtungszeitraum um.</p>
${fig('meinfahrzeug-energieverbrauch-fahrleistung-woche', 'Woche (letzte 7 Tage)')}
${fig('meinfahrzeug-energieverbrauch-fahrleistung-monat', 'Monat')}
${fig('meinfahrzeug-energieverbrauch-fahrleistung-jahr', 'Jahr')}
<h2>Angezeigte Werte</h2>
<ul>
<li><b>Km-Zähler:</b> gefahrene Kilometer im Zeitraum</li>
<li><b>Gesamtstromverbrauch</b> in kWh</li>
<li><b>Elektrizitätsverbrauch</b> in kWh pro 100 km</li>
<li><b>Aufteilung</b> in Fahren, A/C (Klima) und Sonstige</li>
<li><b>Fahrtdetails:</b> einzelne Fahrten mit Zeitraum, per Antippen aufklappbar</li>
</ul>
${info('Die frühere Version zeigte die letzten 6 Wochen als Balkendiagramm mit Details je Woche.')}`
});
