WikiKit.add({
  id: 'ton',
  group: 'Infotainment',
  title: 'Ton & Klang',
  keywords: 'lautstärke equalizer sound audio musik',
  html: ({ fig, path }) => `
<p>${path('Einstellungen', 'Ton')}: Reiter „Ton“ und „Klangeffekt“.</p>
${fig('ton', 'Lautstärke, Benachrichtigungstöne und Klangmodi')}
<ul><li><b>Lautstärkeregelung:</b> Medien, Navigation, Telefon, Klingelton, <a href="#/assistent">Sprachsteuerung</a> getrennt.</li><li><b>Benachrichtigungstöne:</b> Tastenrückmeldeton, Schaltwarnton, Signalton beim Verriegeln.</li><li><b>Intelligente Anpassung:</b> Stummschaltung beim Rückwärtsfahren, reduzierte Medienlautstärke bei Navigationsansage oder offenen Türen.</li><li><b>Schallfeldzone:</b> Gesamtes Fahrzeug, Vorne oder Hinten.</li><li><b>Klangeffektmodus:</b> Hi-Fi Ton, Raumklang, Theater, Dynamisch.</li></ul>
<h2>Equalizer</h2><p>Sieben Bänder (Subwoofer bis Hohe Höhen) mit Voreinstellungen Pop, Rock, Klassik, Tanzmusik, Jazz oder benutzerdefiniert; „Zurücksetz.“ stellt die Standardwerte her.</p>
${fig('ton-equalizer', 'Equalizer')}`
});
