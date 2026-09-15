# Aufbereitung von KBA-Statistiken
Statistische Daten zu den Neuzulassungen können direkt im [KBA-Statistikportal](https://experience.arcgis.com/experience/4fe31a1cadce449bb27045ca2fafb9d3) als CSV-Datei abgefragt werden. Dieses Projekt wandelt die CSV-Daten in eine interaktive HTML-Statistik um.

## Umsetzung
Da es keinen Automatismus für die Abfrage des Statistikportals gibt, werden die Daten manuell abgefragt, als CSV-Datei gespeichert und anschließend über das Webformular verarbeitet.

![Workflow-Darstellung KBA-Python-Webserver](doc/GitHub-Worflow.jpg)

Die Schritte sind:
1. Gewünschte Daten aus dem KBA-Statistikportal extrahieren und lokal als CSV-Datei speichern.
2. CSV-Datei über `index.php` hochladen und mit `csv2html.py` verarbeiten lassen.
3. Erzeugte Statistik prüfen und das lokale Speichern ausdrücklich bestätigen.

## Dateien und ihre Verwendung

### `index.php`

`index.php` ist die Weboberfläche für die Verarbeitung:

- nimmt eine KBA-CSV-Datei als Upload entgegen;
- ruft `csv2html.py` über Python auf;
- zeigt die neu erzeugte Statistik zunächst als Vorschau in einem iframe an;
- überschreibt die lokale HTML-Datei erst nach Klick auf **Lokales Speichern bestätigen**;
- verlangt vor dem Überschreiben zusätzlich das konfigurierte Speicherpasswort;
- verwendet für die Sitzung einen temporären, noch nicht gespeicherten HTML-Inhalt.

`index.php` muss über einen PHP-Webserver aufgerufen werden, zum Beispiel über eine MAMP- oder Apache-Installation. Die Datei ist nicht die eigentliche Statistik und sollte nicht durch einen direkten Link als fertige Ausgabeseite verwendet werden.

Die Vorschau im iframe nutzt den verfügbaren Bildschirmbereich und besitzt eine größere Mindesthöhe. Dadurch können die Tabs und Tabellen auch bei umfangreichen CSV-Ausgaben komfortabel gescrollt werden.

### `kba-statistiken.html`

`kba-statistiken.html` ist die dauerhaft gespeicherte, eigenständige Ausgabedatei. Sie kann direkt im Browser geöffnet, auf einem Webserver veröffentlicht oder im Forum/Wiki verlinkt werden. Für die Anzeige dieser Datei wird weder PHP noch Python benötigt.

Die Datei wird nicht beim bloßen Generieren überschrieben. Erst die Bestätigung im `index.php`-Formular ersetzt die bisherige Version.

### `csv2html.py`

Das Python-Skript verarbeitet die CSV-Datei und erzeugt die HTML-Ausgabe. Es kann auch direkt über die Kommandozeile verwendet werden:

```text
python3 csv2html.py --input daten.csv --output kba-statistiken.html
```

### PHP-Webseite installieren

Für den PHP-Betrieb werden mindestens diese Dateien in das Webverzeichnis hochgeladen:

- `index.php`
- `csv2html.py`

Der Webserver benötigt PHP mit aktivierter `exec()`-Funktion sowie Python 3.6.8 oder neuer. `index.php` ruft `csv2html.py` mit temporären Eingabe- und Ausgabedateien auf. Die CSV und die Zwischen-Ausgabe werden nach der Verarbeitung gelöscht. Die dauerhaft gespeicherte Datei heißt `kba-statistiken.html` und bleibt im selben Verzeichnis erhalten.

Falls Python nicht unter `python3` erreichbar ist, in `index.php` die Konstante `PYTHON_BIN` an den Serverpfad anpassen, zum Beispiel:

```php
const PYTHON_BIN = '/usr/bin/python3';
```

Für das lokale Speichern muss außerdem die Umgebungsvariable `KBA_SAVE_PASSWORD_HASH` gesetzt sein. Sie enthält einen mit PHP erzeugten Passwort-Hash, niemals das Klartextpasswort. Einen Hash erzeugt man zum Beispiel mit:

```text
php -r "echo password_hash('GEHEIMES_PASSWORT', PASSWORD_DEFAULT), PHP_EOL;"
```

Den ausgegebenen Hash als `KBA_SAVE_PASSWORD_HASH` in der PHP-/MAMP-Umgebung hinterlegen. Ohne korrekt gesetzten Hash oder bei einem falschen Passwort wird `kba-statistiken.html` nicht verändert. Die erzeugte Vorschau bleibt bis zu einem erfolgreichen Speichern in der Sitzung erhalten.

Danach `index.php` über die Domain aufrufen, eine CSV-Datei auswählen und **Statistik erzeugen** anklicken. Die Vorschau kann zunächst geprüft werden. Erst **Lokales Speichern bestätigen** schreibt die neue Version in `kba-statistiken.html`.

Die bisherigen Flask-Dateien und `requirements.txt` werden für die PHP-Variante nicht benötigt.

## Inhalt der Statistik

Die Ausgabe enthält drei Tabs:

1. **Neuzulassungen Leapmotor**
	- enthält ausschließlich Datensätze mit der Marke `LEAPMOTOR`;
	- zeigt die konfigurierten Modellreihen und ihre Antriebswerte;
	- summiert die Gesamtzahl innerhalb jedes Jahres.
2. **Marktvergleich Elektro- und Plug-in-Hybrid**
	- fasst alle Marken je Monat zusammen;
	- zeigt `Plug-in-Hybrid` und `Elektro (BEV)` sowie kumulierte Jahreswerte;
	- hebt die Dezemberwerte der Anzahl relativ zur stärksten Marke mit fünf Farbstufen hervor.
3. **Marktvergleich Verbrenner-Elektromobilität**
	- fasst alle Marken und Modellreihen je Jahr und Monat zusammen;
	- `Elektroantrieb` besteht aus `Plug-in-Hybrid` plus `Elektro (BEV)`;
	- `Verbrennerantrieb` ist die verbleibende Anzahl;
	- weist die Spitzenreiter beider Antriebsarten je Zeitraum aus.

Alle Tabellen besitzen eine Jahres- und Monatsauswahl. Die Auswahl gilt gleichzeitig für alle drei Tabs und bleibt beim Wechseln der Tabs erhalten. Mit **Gesamtanzeige** werden die Filter zurückgesetzt. Die Tabellenüberschriften und Spaltenköpfe bleiben beim Scrollen sichtbar; auf kleinen Bildschirmen werden die Tabs untereinander angeordnet.
