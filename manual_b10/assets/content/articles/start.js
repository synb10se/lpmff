WikiKit.add({
  id: 'start',
  group: 'Einstieg',
  title: 'Willkommen',
  keywords: 'einführung start übersicht',
  html: ({ path, warn, caution, info, tip, card }) => `
<p class="lead">Kurzanleitung für den Leapmotor B10 mit dem Infotainment-System LeapOS 3.41.30. Sie ersetzt nicht das Benutzerhandbuch, erklärt aber die wichtigsten Funktionen, insbesondere die im Handbuch fehlenden Bildschirmeinstellungen.</p>
${info('Funktionen und Menünamen können je nach Ausstattung, Land und Software-Stand (OTA-Updates) abweichen. Mit * markierte Funktionen im Handbuch sind modellabhängig. Die Bildschirmfotos zeigen LeapOS 3.41.30.')}
<h2>Themen</h2>
<div class="cards">
${card('bildschirm', 'Hauptbildschirm', 'Statusleiste, Widgets, untere Leiste')}
${card('laden', 'Laden & Entladen', 'Ladelimit, Zeitplan, V2L')}
${card('tueren', 'Türen, Fenster, Heckklappe', 'Fenster, Sonnenblende, Heckklappe')}
${card('fahren', 'Fahreinstellungen', 'Beschleunigung, Rekuperation, One-Pedal')}
${card('adas', 'Fahrerassistenz', 'Spurhalte-, Kollisions- und Kamerafunktionen')}
${card('licht', 'Licht', 'Scheinwerfer, Ambientelicht')}
${card('dashcam', 'Dashcam', 'Aufnahme und Speicher')}
${card('wartung', 'Wartung & Notfall', 'Pflege, Reifendruck, Pannenhilfe')}
</div>
<h2>Warnstufen</h2>
${warn('Gefahr für Leben oder Gesundheit.')}${caution('Gefahr von Schäden am Fahrzeug.')}${info('Zusätzliche Informationen.')}${tip('Praktische Empfehlung.')}
<p>Menüpfade werden so dargestellt: ${path('Einstellungen', 'System', 'Allgemein')}. „Einstellungen“ öffnen Sie über das Zahnrad in der unteren Leiste.</p>`
});
