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

Beim Deployment müssen versteckte Dateien wie `.htaccess` ausdrücklich im Dateipaket enthalten sein. Die VS-Code-Deployment-Konfiguration enthält deshalb `admin/.htaccess` und `data/.htaccess` explizit. `data/suggestions.json` ist absichtlich nicht im Deployment-Paket: Diese Datei enthält die laufenden Vorschläge und darf bei Code-Deployments nicht überschrieben werden.