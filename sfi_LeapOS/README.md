# Verbesserungsvorschläge

Kleine PHP-/Apache-Anwendung zur Erfassung von Verbesserungsvorschlägen. (sfi - suggestions for improvement)
Die Einträge werden in `…/data/suggestions.json` gespeichert. 
Neue Einträge erhalten automatisch den Status `erfasst`.

## Apache einrichten

1. Den Inhalt von `sfi_LeapOS` in ein Apache-Webverzeichnis kopieren. Der Webserver benötigt PHP und Schreibrechte für `data/suggestions.json`.
2. Eine `.htpasswd`-Datei außerhalb des öffentlich erreichbaren Webverzeichnisses anlegen, zum Beispiel:

   ```text
   htpasswd -c /var/www/.htpasswd admin
   ```

3. In `…/admin/.htaccess` den Wert von `AuthUserFile` auf den absoluten Pfad dieser Datei ändern. Der Ordner `admin` ist dann über HTTP Basic Authentication geschützt.
4. `AllowOverride AuthConfig` (oder `AllowOverride All`) für dieses Verzeichnis aktivieren und `mod_auth_basic`/`mod_authn_file` laden.

Die öffentliche Seite liegt in `index.php` und die Verwaltung ist unter `…/admin` erreichbar. 
Die JSON-Datei ist zusätzlich durch `…/data/.htaccess` vor direktem Abruf geschützt.