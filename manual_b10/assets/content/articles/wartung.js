WikiKit.add({
  id: 'wartung',
  group: 'Service',
  title: 'Wartung & Notfall',
  keywords: 'reifendruck pannen abschleppen anhänger notfall wartung intervall',
  html: ({ warn }) => `
<h2>Regelmäßig prüfen</h2>
<ul><li>Reifendruck (Anzeige auf dem <a href="#/bildschirm">Hauptbildschirm</a>): 230/230 kPa unbeladen, 250/250 kPa beladen.</li><li>Waschflüssigkeit, Kühlmittel, Bremsflüssigkeit (alle 2 Jahre oder 40.000 km).</li><li>Wartungsintervall: 12 Monate oder 20.000 km, je nachdem, was zuerst eintritt.</li></ul>
<h2>Notfall</h2>
<ul><li><b>Warnblinker</b> einschalten, Warnweste anziehen, Warndreieck aufstellen. Nothammer unter dem Fahrersitz.</li><li><b>Reifenpanne:</b> Dichtmittel und Luftpumpe im Bordwerkzeug.</li><li><b>Abschleppen:</b> Bevorzugt auf Plattformwagen.</li><li><b>Anhänger*:</b> max. 750 kg, Stützlast 30 kg; Zugmodus unter <a href="#/system">System</a>.</li><li><b>Brand oder Wasserdurchfahrt:</b> Fahrzeug verlassen und Abstand halten, Notruf wählen.</li></ul>
${warn('Am Hochvolt-System niemals selbst arbeiten. Wartung nur durch qualifiziertes Fachpersonal.')}`
});
