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

3. Die mitgelieferte `…/admin/.htaccess` wählt den Pfad anhand des Hosts automatisch: `localhost` und `127.0.0.1` verwenden MAMP, andere Hosts den Alfahosting-Pfad. Der Ordner `admin` ist dann über HTTP Basic Authentication geschützt.
4. `AllowOverride AuthConfig` (oder `AllowOverride All`) für dieses Verzeichnis aktivieren und `mod_auth_basic`/`mod_authn_file` laden.

Die öffentliche Seite liegt in `index.php` und die Verwaltung ist unter `…/admin` erreichbar. 
Die JSON-Datei ist zusätzlich durch `…/data/.htaccess` vor direktem Abruf geschützt.