WikiKit.add({
  id: 'laden',
  group: 'Mein Fahrzeug',
  title: 'Laden',
  keywords: 'ladezentrum ladelimit ladezeitplan batterie vorwärmen ladestation verknüpfen entriegeln',
  html: ({ fig, tip, info }) => `
<p>Die Ladekarte auf „Mein Fahrzeug“ zeigt Ladestand, <b>Ladelimit</b>, Ladeart (z. B. „Langsames Laden“) und die verbleibende Zeit. Ein Tipp darauf öffnet das <b>Ladezentrum</b>.</p>
${fig('meinfahrzeug-ladezentrum', 'Ladezentrum')}
<h2>Funktionen im Ladezentrum</h2>
<ul>
<li><b>Live-Werte:</b> Ladestand, Restzeit, Stromstärke, Spannung und Leistung.</li>
<li><b>Ladegerät entriegeln:</b> Gibt den Ladestecker frei.</li>
<li><b>Oberes Ladelimit:</b> Schieberegler von 50 % bis 100 %. Als optimal für die Batterie wird 90 % angezeigt.</li>
<li><b>Ladezeitplan:</b> Das Fahrzeug lädt täglich im festgelegten Zeitraum, sofern es angeschlossen ist. Unterstützt wird nur Langsamladen (AC).</li>
<li><b>Vorwärmen der Batterie:</b> Bei niedrigen Außentemperaturen wird die Batterie temperiert und die Funktion automatisch beendet, sobald die ideale Temperatur erreicht ist. Zeitraum einstellbar.</li>
<li><b>Ladestation verknüpfen:</b> Nach dem Verknüpfen startet das Laden einfach durch Anschließen an der Ladestation.</li>
</ul>
${tip('Für den Alltag genügt meist ein Ladelimit um 80–90 %. Auf 100 % nur laden, wenn die volle Reichweite benötigt wird.')}
${info('Seit Version 1.16 enthält die Ladehistorie eine Unterscheidung nach DC- und AC-Laden. Seit 1.8.3 kann der Ladevorgang auch aus der App gestartet werden.')}`
});
