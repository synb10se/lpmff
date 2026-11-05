WikiKit.add({
  id: 'klima',
  group: 'Mein Fahrzeug',
  title: 'Klima & Vorbereitung',
  keywords: 'temperatur klimaautomatik sitzheizung lenkradheizung spiegelheizung schnelles kühlen beheizen geruch auftauen fahrzeugvorbereitung zeitplan navigation',
  html: ({ fig, info, tip }) => `
<h2>Temperaturregelung</h2>
<p>Die Temperaturkarte auf „Mein Fahrzeug“ öffnet die <b>Regelung der Innentemperatur</b> mit Innenraumansicht (überarbeitet in Version 1.14).</p>
${fig('meinfahrzeug-regelung-der-innentemperatur', 'Regelung der Innentemperatur')}
<ul>
<li>Heizung der Außenspiegel (einzeln)</li>
<li>Lenkradheizung</li>
<li>Sitzheizung oder -belüftung (einzeln)</li>
<li>Solltemperatur mit „−“ und „+“</li>
<li><b>A/C-Schalter</b> (Klimaautomatik ein/aus)</li>
<li><b>Schnelles Kühlen</b> (niedrigste Stufe „LO“ = 18 °C)</li>
<li><b>Schnelles Beheizen</b> (höchste Stufe „HI“ = 32 °C)</li>
<li><b>Schnelle Geruchsentfernung</b></li>
<li><b>Auftauen der Windschutzscheibe</b></li>
</ul>
${info('Alle über die App gestarteten Temperaturfunktionen laufen höchstens <b>20 Minuten</b>, um die Fahrzeugbatterie zu schonen.')}
<p>Unter den Tasten steht die Zeit der nächsten geplanten Klimatisierung. Antippen öffnet den „Zeitplan für A/C“. Ist kein Wochentag gewählt, ist der Zeitplan abgeschaltet.</p>
<h2>Fahrzeugvorbereitung per Fingerdruck</h2>
<p>Hier lässt sich die Klimatisierung differenzierter planen oder sofort starten („Sofortige Vorbereitung“), optional mit <b>Navigationsziel</b> für die fahrzeugeigene Navigation.</p>
${fig('meinfahrzeug-fahrzeugvorbereitung', 'Übersicht Fahrzeugvorbereitung')}
${fig('meinfahrzeug-fahrzeugvorbereitung-plan', 'Vorbereitung bearbeiten')}
<ul>
<li><b>Zeit:</b> Beginn der Vorbereitung, nur beim Planen. Es gilt die Zeitzone des Fahrzeugs.</li>
<li><b>Wiederholen:</b> Wochentage, nur beim Planen. Mehrere Pläne sind möglich und einzeln ein-/ausschaltbar.</li>
<li><b>Ziel:</b> optionales Navigationsziel.</li>
<li><b>A/C:</b> Temperatur, Schnelles Kühlen/Beheizen, Geruchsentfernung, Scheibe auftauen.</li>
<li><b>Sitz:</b> Heizen/Belüften von Fahrer- und Beifahrersitz.</li>
<li><b>Lenkradheizung</b> und <b>Spiegelheizung</b>.</li>
</ul>
${tip('Mit „Fertig“ speichern; das Papierkorb-Symbol oben rechts löscht den Plan.')}`
});
