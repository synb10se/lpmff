WikiKit.add({
  id: 'profile',
  group: 'Bedienung',
  title: 'Benutzerprofile',
  keywords: 'konto gast profil',
  html: ({ fig }) => `
<p>Jedes Profil speichert persönliche Einstellungen. Sie wechseln über das Profilbild oben links. Der <b>Gastmodus</b> nutzt keine gespeicherten Einstellungen.</p>
${fig('benutzerprofile', 'Konten verwalten: Konto hinzufügen, Gastmodus, Löschen')}
<h2>Einstellungen speichern</h2>
<p>Pro Profil können Sie getrennt festlegen, was gemerkt wird:</p>
<ul><li><a href="#/klima">Klimaanlageneinstellungen</a> (Ein/Aus, Gebläse, Temperatur, Umluft, Luftverteilung)</li><li>Benutzerspezifische Lenkradanpassung</li><li>Wiedergabe automatisch fortsetzen</li><li>Soundeffekte und Lautstärke (siehe <a href="#/ton">Ton &amp; Klang</a>)</li></ul>
${fig('benutzerprofile-einstellungen', 'Einstellungen speichern')}${fig('benutzerprofile-einstellungen-klima', 'Erklärung zu den gespeicherten Klimaeinstellungen')}
<h2>Profilbild und Name</h2>
<p>Über „Bearbeiten“ ändern Sie Namen und Bild.</p>${fig('benutzerprofile-profilbild', 'Profilbild auswählen')}${fig('benutzerprofile-neu', 'Gastmodus aktiv')}`
});
