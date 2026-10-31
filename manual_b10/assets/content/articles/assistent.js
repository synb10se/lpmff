WikiKit.add({
  id: 'assistent',
  group: 'Infotainment',
  title: 'Sprachassistent',
  keywords: 'sprachsteuerung hi leapmotor weckwort',
  html: ({ fig, path }) => `
<p>${path('Einstellungen', 'Leapmotor Assistent')}. Mit dem Weckwort „Hi, Leapmotor“ steuern Sie Navigation, Medien, Klima, Fahrzeugfunktionen und Telefon per Sprache.</p>
${fig('assistant', 'Einstellungen des Leapmotor Assistenten')}
<ul><li><b>Sprachaktivierung:</b> Weckwort ein/aus; Aussprache als Vorschau.</li><li><b>Klangzone aufwecken:</b> Fahrer oder Beifahrer.</li><li><b>Kontinuierlicher Dialog:</b> Aus, 10, 15 oder 20 s ohne erneutes Weckwort.</li><li><b>Aufweckwort überspringen:</b> Befehle ohne Weckwort.</li><li><b>Beispiel für Sprachbefehle:</b> Liste unterstützter Befehle.</li></ul>
<p>Unterstützte Sprachen: Englisch, Französisch, Deutsch, Italienisch, Spanisch, brasilianisches Portugiesisch, Arabisch. Alternativ die Sprachtaste am Lenkrad.</p>`
});
