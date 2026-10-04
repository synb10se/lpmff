# Verbesserungsvorschläge

Kleine PHP-/Apache-Anwendung zur Erfassung von Verbesserungsvorschlägen. (sfi - suggestions for improvement)
Die Einträge werden in `…/data/suggestions.json` gespeichert. 
Neue Einträge erhalten automatisch den Status `erfasst`.

## Apache einrichten

1. Den Inhalt von `sfi_LeapOS` in ein Apache-Webverzeichnis kopieren. Der Webserver benötigt PHP und Schreibrechte für `data/suggestions.json`.
2. Eine `.htpasswd`-Datei außerhalb des öffentlich erreichbaren Webverzeichnisses anlegen. Für den lokalen MAMP-Test lautet der Pfad `/Applications/MAMP/access/sfi_LeapOS/.htpasswd`; auf Alfahosting lautet er `/var/www/vhosts/h331132.web114.alfahosting-server.de/access/sfi_LeapOS/.htpasswd`. Zum Anlegen kann beispielsweise verwendet werden:

   ```text
   htpasswd -c /Applications/MAMP/access/sfi_LeapOS/.htpasswd admin
   ```

3. Die Adminseite liest die `.htpasswd` anhand des Hosts: `localhost` und `127.0.0.1` verwenden MAMP, andere Hosts den Alfahosting-Pfad. Die Anmeldung fragt aus Komfortgründen nur das Passwort ab; der Benutzername aus der `.htpasswd` wird nicht benötigt. Die `admin/.htaccess` verhindert dabei keine PHP-Ausführung und schützt den Ordner nicht selbst per HTTP Basic Authentication.
4. `AllowOverride AuthConfig` (oder `AllowOverride All`) für dieses Verzeichnis aktivieren und `mod_auth_basic`/`mod_authn_file` laden.

Die öffentliche Seite liegt in `index.php` und die Verwaltung ist unter `…/admin` erreichbar. 
Die JSON-Datei ist zusätzlich durch `…/data/.htaccess` vor direktem Abruf geschützt.

## Sprachen

Die Oberfläche ist auf Deutsch (`de`) und Englisch (`en`) verfügbar. Die Sprache lässt sich über die Auswahl oben rechts ändern und wird für die aktuelle Sitzung gespeichert. Die Texte liegen getrennt in `languages/de.php` und `languages/en.php`; beide Dateien verwenden dieselben Schlüssel. Um eine weitere Sprache bereitzustellen, eine passende Katalogdatei ergänzen und den ISO-639-Code in `SUPPORTED_LANGUAGES` in `lib.php` aufnehmen. Die Optionsbeschriftung wird aus dem Sprachcode gebildet.

Die angebotenen LeapOS-Versionen kombinieren `LEAPOS_VERSIONS` aus `lib.php` mit aktuell gespeicherten Vorschlägen. Benutzer können über „Version hinzufügen …“ eine numerische Versionsnummer mit Punkt-Segmenten eingeben. Ändert oder löscht ein Administrator den letzten Vorschlag mit einer solchen Version, wird sie anschließend nicht mehr angeboten.

URL-Parameter wie `username`, `userID` und `userid` werden nicht ausgewertet. Vorschläge enthalten keine Angaben zum Autor oder zu einer Autorennummer.

Unter `…/export` gibt es eine weitere passwortgeschützte Seite. Sie zeigt ausschließlich Einträge mit dem Status `geprüft` und enthält Nummer, Thema, Modell, LeapOS-Version, Kategorie, Einordnung und Vorschlag. Kategorie und Einordnung erscheinen in Klarschrift; unbekannte Werte werden als `Not specified` ausgewiesen. Der Export ist als CSV oder Excel-kompatible `.xlsx`-Datei verfügbar.

Freitextübersetzungen für Thema und Vorschlag laufen über LibreTranslate. Der kostenlose, selbst gehostete Dienst hat kein festes Anbieter-Zeichenkontingent; tatsächliche Grenzen hängen von der Serverleistung und der Dienstkonfiguration ab. Beispiel für einen lokalen Dienst:

```text
pip install libretranslate
libretranslate --host 127.0.0.1 --port 5000 --load-only de,en
```

Die Anwendung verwendet standardmäßig `http://killerminion.ddns.berlin:4712/translate`. Für andere Umgebungen kann `LIBRETRANSLATE_URL` in der Apache-Umgebung auf einen abweichenden vollständigen Übersetzungsendpunkt gesetzt werden, zum Beispiel `http://127.0.0.1:5000/translate`. Bei einem geschützten oder verwalteten Endpunkt kann zusätzlich `LIBRETRANSLATE_API_KEY` gesetzt werden. API-Schlüssel gehören ausschließlich in die Serverkonfiguration, nicht in das Repository. Der bereitgestellte Endpunkt ist derzeit nur über HTTP erreichbar; Freitexte werden daher unverschlüsselt übertragen. Für vertrauliche Inhalte sollte TLS am Dienst oder an einem Reverse Proxy aktiviert werden. Ohne erreichbaren Dienst werden Freitexte unverändert ausgegeben und die Exportseite zeigt einen Hinweis. Weitere Informationen: [LibreTranslate API Usage](https://docs.libretranslate.com/guides/api_usage/) und [Installation](https://docs.libretranslate.com/guides/installation/).

Beim Deployment müssen versteckte Dateien wie `.htaccess` ausdrücklich im Dateipaket enthalten sein. Die VS-Code-Deployment-Konfiguration enthält deshalb `admin/.htaccess` und `data/.htaccess` explizit. `data/suggestions.json` ist absichtlich nicht im Deployment-Paket: Diese Datei enthält die laufenden Vorschläge und darf bei Code-Deployments nicht überschrieben werden.