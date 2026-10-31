WikiKit.add({
  id: 'laden',
  group: 'Fahrzeug',
  title: 'Laden & Entladen',
  keywords: 'ladung ac dc v2l ladelimit zeitplan akku stecker',
  html: ({ fig, path, info, tip }) => `
<p>Der Ladeanschluss befindet sich hinten rechts: AC-Anschluss (Wechselstrom, langsam) und DC-Anschluss (Gleichstrom, Schnellladen). Die Einstellungen finden Sie unter ${path('Einstellungen', 'Laden')}.</p>
<h2>Ablauf</h2>
<ol><li>Fahrzeug in P stellen, Parkbremse aktivieren.</li><li>Ladeklappe öffnen, Stecker bis zum Klick einstecken.</li><li>Ladevorgang prüfen. Zum Beenden Fahrzeug entriegeln und Stecker abziehen; bei Bedarf über „Ladekabel“ auf dem Bildschirm entriegeln.</li></ol>
${fig('laden-laden', 'Laden: Ladelimit, Zeitplan, Reichweitenbedingungen')}
<h2>Einstellungen im Reiter „Laden“</h2>
<ul><li><b>Ladelimit:</b> Zielladezustand per Schieberegler.</li><li><b>Ladekabel:</b> Entriegeln des Ladesteckers.</li><li><b>Sicherheitsbegrenzung des Ladestroms:</b> Begrenzt das tragbare Ladegerät auf 8 A, geeignet für 10-A-Steckdosen.</li><li><b>Zeitplan für langsames Laden:</b> Zeitfenster und Wiederholung festlegen.</li><li><b>Reichweitenbedingungen:</b> Standard oder Dynamisch.</li><li><b>Vorbereitung:</b> Temperiert den Akku vor dem Schnellladen.</li></ul>
${tip('Laut Handbuch ist für die Akku-Lebensdauer ein Ladebereich von etwa 50–80 % im Alltag günstig. Bei ≤ 10 % innerhalb von 72 Stunden laden. Die ersten drei Ladungen möglichst per AC.')}
<h2>Entladen (V2L)</h2>
<p>Der Reiter „Entladen“ versorgt externe Geräte aus dem Fahrzeugakku. Dafür in P schalten und ein Entladegerät anschließen, dann „Stromversorgung vom Fahrzeug (V2L)“ aktivieren. Unter 20 % Ladezustand wird V2L nicht zugelassen; das Entladelimit lässt sich einstellen. „V2L-Stecker entriegeln“ löst den Stecker.</p>
${fig('laden-entladen', 'Entladen (V2L)')}
${info('Das Entladegerät ist nicht im Lieferumfang enthalten.')}
<h2>Notentriegelung</h2><p>Lässt sich der Stecker nicht lösen, nutzen Sie die Notentriegelung (Kabel im Werkzeugkasten unter dem Kofferraumboden). Details im Handbuch.</p>`
});
