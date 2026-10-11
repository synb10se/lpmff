# KBA-Statistiken

Dieses Projekt verarbeitet KBA-CSV-Exporte aus dem KBA-Statistikportal und erzeugt daraus eine HTML-Statistik, die im Browser als Vorschau angezeigt und optional dauerhaft gespeichert werden kann.

Die eigentliche Weboberfläche liegt in `index.php`. Sie übernimmt die CSV-Upload- und Speicherlogik und startet das Python-Skript `csv2html.py` mit temporären Dateien.

## Überblick

Der typische Ablauf ist:

1. CSV-Datei aus dem KBA-Statistikportal herunterladen und lokal speichern.
2. CSV in `index.php` per Upload einreichen.
3. `csv2html.py` erzeugt daraus eine HTML-Auswertung.
4. Die Ausgabe wird im Browser als Vorschau in einem `iframe` angezeigt.
5. Erst nach Passwortprüfung und Bestätigung wird die Datei `kba-statistiken.html` aktualisiert.

![/doc/GitHub-Worflow.jpg](https://github.com/synb10se/lpmff/blob/main/kbastatistiken/doc/GitHub-Worflow.jpg)

## Dateien

### `index.php`

`index.php` ist die interaktive PHP-Oberfläche. Ihre tatsächlichen Funktionen sind:

- CSV-Datei per Upload akzeptieren
- mit `exec()` das Python-Skript ausführen
- die erzeugte HTML-Ausgabe als Vorschau rendern
- ein `download`-Link für das aktuelle, noch nicht gespeicherte Ergebnis anbieten
- eine Vorschau nur nach erfolgreicher Verarbeitung anzeigen
- die gespeicherte Ausgabe nur nach Eingabe des korrekten Passworts überschreiben
- bei Bedarf den Zustand der Sitzung zurücksetzen

Wichtige Details:

- Es wird immer ein temporäres Input- und Outputfile für Python verwendet.
- Die erzeugte HTML wird in der Session als `pending_html` gespeichert.
- Die gespeicherte Datei `kba-statistiken.html` wird nur gelöscht/ersetzt, wenn `action=save` mit gültigem Passwort bestätigt wird.
- Wenn kein aktuelles, ungespeichertes Ergebnis vorhanden ist, ist ein Download per `?action=download` nicht möglich.

### `csv2html.py`

Das Python-Skript erzeugt aus der CSV die HTML-Statistik. Es kann auch direkt über die Kommandozeile genutzt werden:

```bash
python3 csv2html.py --input daten.csv --output kba-statistiken.html --quiet
```

Die Ausgabe enthält die für dieses Projekt relevanten Tabellen, inklusive Jahres-/Monatsfilterung und einer HTML-Darstellung für die Browser-Vorschau.

### `kba-statistiken.html`

`kba-statistiken.html` ist die dauerhaft gespeicherte Ausgabedatei. Sie wird nicht bei jedem Upload überschrieben; sie bleibt erst dann auf dem aktuellen Stand, wenn im Formular in `index.php` das lokale Speichern bestätigt wurde.

Diese Datei kann direkt im Browser geöffnet, auf einem Webserver veröffentlicht oder im Wiki/Forum verlinkt werden. Für sie ist weder PHP noch Python erforderlich.

## Anforderungen

Für den Betrieb wird ein PHP-Webserver mit aktivierter `exec()`-Funktion benötigt. Zusätzlich muss Python 3 verfügbar sein.

Die PHP-Konstante `PYTHON_BIN` ist in `index.php` auf `python3` gesetzt:

```php
const PYTHON_BIN = 'python3';
```

Wenn Python an einem anderen Pfad liegt, muss dieser hier angepasst werden, zum Beispiel:

```php
const PYTHON_BIN = '/usr/bin/python3';
```

## Passwortschutz für das Speichern

Das lokale Speichern ist nur freigegeben, wenn die Umgebungsvariable `KBA_SAVE_PASSWORD_HASH` gesetzt ist. Sie enthält den PHP-Hash des Passworts, nicht das Klartextpasswort.

Beispiel zur Erzeugung eines Hashes:

```bash
php -r "echo password_hash('GEHEIMES_PASSWORT', PASSWORD_DEFAULT), PHP_EOL;"
```

Danach den resultierenden Hash als `KBA_SAVE_PASSWORD_HASH` in der Umgebung des PHP-Servers hinterlegen.

Ohne gültigen Hash oder mit falschem Passwort bleibt die gespeicherte Datei unverändert; die neue Vorschau bleibt in der Session erhalten, bis ein erfolgreicher Speichern-Schritt erfolgt.

## Benutzung im Browser

1. `index.php` im Webbrowser aufrufen.
2. Eine CSV-Datei auswählen.
3. Auf `Statistik erzeugen` klicken.
4. Die HTML-Auswertung erscheint als Vorschau im `iframe`.
5. Wenn die Vorschau korrekt ist, das Password eingeben und auf `Lokales Speichern bestätigen` klicken.
6. Damit wird `kba-statistiken.html` aktualisiert.

Um die Vorschau nach einer fehlerhaften oder veralteten Ausgabe wieder auf den gespeicherten Stand zu setzen, kann über das Formular `Zurücksetzen` der aktuelle Zustand verworfen werden.

## Inhalt der Statistik

Die generierte Seite enthält üblicherweise mehrere Tabs mit KBA-Analysen, zum Beispiel:

- Neuzulassungen für Leapmotor
- Marktvergleich Elektro- und Plug-in-Hybrid
- Marktvergleich Verbrenner vs. Elektromobilität

Die Tabellen sind mit Jahres- und Monatsfiltern ausgestattet; die Auswahl gilt für alle Tabs gleichzeitig. Über `Gesamtanzeige` können die Filter zurückgesetzt werden.

Die Ausgabe ist für Browserdarstellung optimiert: Überschriften und Spaltenköpfe bleiben bei längeren Tabellen sichtbar, und auf kleineren Bildschirmen werden die Tabs vertikal angeordnet.

## Wichtige Hinweise

- `index.php` ist kein fertiges, direkt zu verlinkendes Statistik-HTML, sondern die Verwaltungsschnittstelle für die Erstellung.
- Der eigentliche, für Besucher nutzbare Output ist `kba-statistiken.html`.
- Die in `index.php` erzeugte Vorschau wird nur in der Session gehalten und ist kein dauerhaftes Dateisystem-Artifact.
- Der Ablauf ist bewusst so gestaltet, dass erst nach manuellem Review und freigebender Bestätigung die gespeicherte Statistik aktualisiert wird.
