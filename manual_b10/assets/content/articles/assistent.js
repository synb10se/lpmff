WikiKit.add({
  id: 'assistent',
  group: 'Infotainment',
  title: 'Sprachassistent',
  keywords: 'sprachsteuerung sprachbefehle weckwort sprachassistent',
  html: ({ fig, path, tip }) => `
<p>${path('Einstellungen', 'Leapmotor Assistent')}. Mit dem Weckwort „Hi, Leapmotor“ steuern Sie Navigation, Medien, Klima, Fahrzeugfunktionen und Telefon per Sprache.</p>
${fig('assistant', 'Einstellungen des Leapmotor Assistenten')}
<ul><li><b>Sprachaktivierung:</b> Weckwort ein/aus; Aussprache als Vorschau.</li><li><b>Klangzone aufwecken:</b> Fahrer oder Beifahrer.</li><li><b>Kontinuierlicher Dialog:</b> Aus, 10, 15 oder 20 s ohne erneutes Weckwort.</li><li><b>Aufweckwort überspringen:</b> Befehle ohne Weckwort.</li><li><b>Beispiel für Sprachbefehle:</b> Liste unterstützter Befehle.</li></ul>
<p>Unterstützte Sprachen: Englisch, Französisch, Deutsch, Italienisch, Spanisch, brasilianisches Portugiesisch, Arabisch. </br>Alternativ kann die Sprachtaste am Lenkrad verwendet werden, um den Sprachassistenten aufzuwecken, solange kein Apple CarPlay oder Android Auto laufen. In dem Fall wird nämlich jeweils nur noch die Sprachfunktion der App mit der Taste unterstützt</p>
<h2>Sprachbefehle</h2>
<p>Die folgenden Beispiele stammen aus einer Sammlung zu LeapOS 3.41.22 und die Erkennung kann je nach Softwareversion abweichen. Die Befehle gelten eventuell abweichend auch für andere Fahrzeuge von Leapmotor und nicht nur für den B10. </br>Sagen Sie den gewünschten Befehl nach „Hi, Leapmotor“ oder aktivieren Sie den Assistenten mit der rechten oberen Lenkradtaste.</p>
<h3>Radio</h3>
<ul><li>„Radio ein“ / „Radio aus“</li></ul>
<p>„Radio ein“ schaltet möglicherweie nur auf FM und nicht zwingend auf den zuletzt verwendeten Sender oder die zuletzt verwendete Radioquelle.</p>
<h3>Heizung und Lüftung</h3>
<ul>
  <li>„Sitzheizung an“ / „Sitzheizung aus“</li>
  <li>„Beifahrer Sitzheizung an“ / „Beifahrer Sitzheizung aus“</li>
  <li>„Sitzlüftung an“ / „Sitzlüftung aus“</li>
  <li>„Beifahrer Sitzlüftung an“ / „Beifahrer Sitzlüftung aus“</li>
  <li>„Lüftergeschwindigkeit erhöhen“ / „Lüftergeschwindigkeit verringern“</li>
  <li>„Temperatur auf [Grad] Grad stellen“, z. B. „Klimaanlage 22 Grad“</li>
  <li>„Mir ist kalt“ / „Mir ist warm“</li>
  <li>„Umluft an“ / „Umluft aus“; „Frischluft an“ / „Frischluft aus“</li>
  <li>„Entfrostung vorne“; „Entfrostung hinten“</li>
  <li>„Spiegelheizung an“ / „Spiegelheizung aus“</li>
</ul>
<p>Die Sitzheizung berücksichtigt die Sitzbelegung und schaltet nur die belegte Sitzseite ein. Sie lässt sich per Sprache nur vollständig ein- oder ausschalten und nicht stufenweise regeln. „Mir ist kalt“ erhöht die eingestellte Temperatur um 2 Grad und „Mir ist warm“ senkt sie um 2 Grad. Ob „Temperatur erhöhen“ und „Temperatur verringern“ erkannt werden. konnte empirisch nicht bestätigt werden.</p>
<h3>Fenster, Sonnenblende und Heckklappe</h3>
<ul>
  <li>„Alle Fenster auf“ / „Alle Fenster zu“</li>
  <li>„Beifahrer Fenster auf“ / „Beifahrer Fenster zu“</li>
  <li>„Alle Fenster zur Hälfte öffnen“</li>
  <li>„Fenster öffnen“ / „Fenster schließen“ (Fahrerfenster)</li>
  <li>„Beifahrer Fenster öffnen“ / „Beifahrer Fenster schließen“</li>
  <li>„Fenster ein wenig öffnen“ / „Fenster ein wenig schließen“; analog mit „Beifahrer Fenster“</li>
  <li>„Sonnenblende öffnen“ / „Sonnenblende schließen“</li>
  <li>„Sonnenrollo zu 50 % öffnen“ (auch andere Prozentwerte, z. B. 80 %)</li>
  <li>„Heckklappe öffnen“ / „Heckklappe schließen“</li>
</ul>
<p>„Fenster ein wenig öffnen“ bzw. „Fenster ein wenig schließen“ lässt sich wiederholen: Zwei Öffnungsbefehle ergeben ungefähr einen 4 cm breiten Spalt und weitere Wiederholungen verstellen das Fenster schrittweise. Die Heckklappe lässt sich per Sprache nur bei stehendem Fahrzeug bedienen.</p>
<h3>Beleuchtung und Fahrmodus</h3>
<ul>
  <li>„Aktiviere Ambientebeleuchtung“ / „Deaktiviere Ambientebeleuchtung“</li>
  <li>„Ändere Farbe Ambientebeleuchtung auf [Farbe]“, z. B. Rot, Blau, Lila oder Grün</li>
  <li>„Aktiviere Innenbeleuchtung“ / „Deaktiviere Innenbeleuchtung“</li>
  <li>„Aktiviere“ / „Deaktiviere Nebelscheinwerfer“, „Abblendlicht“ oder „Standlicht“</li>
  <li>„Ändere Beschleunigungsmodus auf Comfort“, „Standard“ oder „Sport“</li>
</ul>
<p>Bei den Außenleuchten erfolgt keine akustische Bestätigung, daher bitte die Kontrollleuchte prüfen.</p>
<h3>Assistent beenden</h3>
<ul><li>„Hey Leapmotor, zieh dich zurück“</li></ul>
${tip('Der Assistent kann durch eine laufende Tonwiedergabe irritiert werden. Vor dem Start des Assistenten sollte diese über die rechte Multifunktionstaste am Lenkrad gestoppt werden.')}
<p>Quelle: <a href="https://www.lpmff.de/wiki/entry/114-leapmotor-b10-sprachsteuerung/" target="_blank" rel="noopener noreferrer">Leapmotor B10: Sprachsteuerung</a> (Stand: 6. November 2026).</p>`
});
