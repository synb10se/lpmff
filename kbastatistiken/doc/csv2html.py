#!/usr/bin/env python3
"""
CSV-zu-HTML Tabellengenerator (Version 2.0)
Liess eine CSV-Datei und generiert eine formatierte HTML-Tabelle mit CLI-Parametern.

Verwendung:
    python csv_to_html.py --input meine_daten.csv --output tabelle.html
    python csv_to_html.py -i data.csv -o report.html -v
"""

import csv
import argparse
from pathlib import Path
import sys


def parse_arguments():
    """Parse command line arguments."""
    parser = argparse.ArgumentParser(
        description='Generiert eine HTML-Tabelle aus CSV-Daten.',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Beispiele:
  %(prog)s --input mein_export.csv
  %(prog)s -i daten.csv -o report.html -v
  %(prog)s --input data.csv --output tabelle.html --models B10,C10,T03

Voraussetzung:
  Die CSV muss Spalten enthalten wie: Berichtsjahr, Berichtsmonat, Modellreihe, 
  Anzahl, Elektro (BEV), Plug-in-Hybrid, Allradantrieb etc.
        """
    )
    
    parser.add_argument(
        '-i', '--input',
        type=str,
        default='SP_Modellreihen.csv',
        help='Eingabe-CSV-Datei (Standard: SP_Modellreihen.csv)'
    )
    
    parser.add_argument(
        '-o', '--output',
        type=str,
        default='kba-statistik.html',
        help='Ausgabe-HTML-Datei (Standard: kba_statistik.html)'
    )
    
    parser.add_argument(
        '-m', '--models',
        type=str,
        default='B05,B10,C10,T03',
        help='Komma-separierte Liste von Modellreihen (Standard: B05,B10,C10,T03)'
    )
    
    parser.add_argument(
        '-v', '--verbose',
        action='store_true',
        help='Detaillierte Ausgabe während der Verarbeitung'
    )
    
    parser.add_argument(
        '-q', '--quiet',
        action='store_true',
        help='Nur Fehler anzeigen (kein progress output)'
    )
    
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Nur prüfen, ohne Datei zu schreiben'
    )
    
    return parser.parse_args()


def log(message, verbose=False, quiet=False):
    """Helper für Logging mit verschiedenen Stufen."""
    if quiet:
        return
    if verbose or not verbose:  # immer, außer quiet=True
        print(message)


def load_csv_data(csv_filename, verbose=False, quiet=False):
    """Liest die CSV-Datei und returns list of dictionaries."""
    log(f"\nCSV laden: {csv_filename}", verbose, quiet)
    
    if not Path(csv_filename).exists():
        print(f"✗ ERROR: Datei nicht gefunden: {csv_filename}")
        return None
    
    data = []
    try:
        with open(csv_filename, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            
            # Header debuggen (nur im Verbose-Modus)
            if verbose:
                headers = reader.fieldnames
                print(f"   ✓ CSV-Header ({len(headers)} Spalten):")
                for h in headers[:10]:
                    print(f"      - {h}")
                if len(headers) > 10:
                    print(f"      ... und {len(headers)-10} weitere")
            
            for row in reader:
                data.append(row)
        
        print(f"✓ {len(data)} Datensätze geladen")
        return data
        
    except UnicodeDecodeError:
        # Versuch nochmal mit anderer Kodierung
        print("   → UTF-8 fehlgeschlagen, versuche ISO-8859-1...")
        try:
            with open(csv_filename, 'r', encoding='iso-8859-1') as f:
                reader = csv.DictReader(f)
                data = list(reader)
            print(f"✓ {len(data)} Datensätze geladen (ISO-8859-1)")
            return data
        except Exception as e:
            print(f"✗ ERROR beim Lesen: {e}")
            return None
    except Exception as e:
        print(f"✗ ERROR beim Lesen: {e}")
        return None


def aggregate_by_month_and_model(data, column_mapping, verbose=False, quiet=False):
    """Gruppieret die Daten nach Monat und Modellreihe."""
    aggregated = {}
    skipped = 0
    
    for i, row in enumerate(data):
        jahr = row.get(column_mapping['jahr'], '').strip()
        monat = row.get(column_mapping['monat'], '').strip()
        modellreihe = row.get(column_mapping['modellreihe'], '').strip()
        
        # Skip incomplete rows
        if not jahr or not monat or not modellreihe:
            skipped += 1
            continue
        
        key = (jahr, monat)
        if key not in aggregated:
            aggregated[key] = {}
        
        if modellreihe not in aggregated[key]:
            aggregated[key][modellreihe] = {
                'gesamt': '',
                'bev': '',
                'reew': '',
                'awd': ''
            }
        
        # Werte extrahieren
        aggregated[key][modellreihe]['gesamt'] = row.get(column_mapping['gesamt'], '').strip() or '-'
        bev_val = row.get(column_mapping['bev'], '').strip()
        reew_val = row.get(column_mapping['reew'], '').strip()
        awd_val = row.get(column_mapping['awd'], '').strip()
        
        aggregated[key][modellreihe]['bev'] = bev_val if bev_val else ''
        aggregated[key][modellreihe]['reew'] = reew_val if reew_val else ''
        aggregated[key][modellreihe]['awd'] = awd_val if awd_val else ''
    
    if skipped > 0 and verbose:
        print(f"   ℹ {skipped} Zeilen übersprungen (unvollständige Daten)")
    
    return aggregated


def generate_html(aggregated_data, target_models, verbose=False, quiet=False):
    """Generiert den vollständigen HTML-Code."""
    if not quiet:
        log("\nHTML generieren...", verbose, quiet)
    
    # CSS definieren
    css_colors = {
        'B05': {'main': '#e6c84a', 'header': '#d4ba3e'},
        'B10': {'main': '#a9a2c9', 'header': '#9b91bf'},
        'C10': {'main': '#9ebca5', 'header': '#8cae98'},
        'T03': {'main': '#b8c7cf', 'header': '#a8bdb9'}
    }
    
    html = '''<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leapmotor KBA Statistik</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .table-container {
            overflow-x: auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 1000px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: center;
            font-size: 13px;
        }
        .header-level-1 {
            font-weight: bold;
            color: #fff;
            height: 40px;
            vertical-align: middle;
        }
        .header-level-2 {
            font-weight: bold;
            color: #333;
            height: 30px;
            vertical-align: top;
        }
'''
    
    # Dynamische Farben je nach Modellen
    for model in target_models:
        if model in css_colors:
            color = css_colors[model]
            safe_model = model.replace('0', '_ZERO').replace('O', '_OH')
            html += f'''        .group-{model} {{
            background-color: {color['main']};
        }}
        .group-{model}-header-1 {{
            background-color: {color['header']};
        }}
'''
    
    html += '''
        tr:nth-child(even) { background-color: #fafafa; }
        tr:nth-child(odd) { background-color: #ffffff; }
        td {
            font-family: 'Courier New', monospace;
            color: #333;
        }
        td:first-child, td:nth-child(2) {
            text-align: left;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Jahr</th>
                    <th rowspan="2">Monat</th>
'''
    
    # Dynamische Kopfzeilen basierend auf Zielsmodellen
    model_configs = {
        'B05': {'colspan': 1, 'has_sub': True},
        'B10': {'colspan': 3, 'has_sub': True},
        'C10': {'colspan': 4, 'has_sub': True},
        'T03': {'colspan': 1, 'has_sub': True}
    }
    
    for model in target_models:
        config = model_configs.get(model, {'colspan': 1, 'has_sub': False})
        sub_label = 'gesamt' if config['has_sub'] else ''
        html += f'                    <th colspan="{config["colspan"]}" class="group-{model}">{model}<br>{sub_label}</th>\n'
    
    html += '''                </tr>
                <tr>
'''
    
    # Zweite Zeile der Kopfzeilen (Unterkolumnen)
    for model in target_models:
        config = model_configs.get(model, {'colspan': 1, 'has_sub': False})
        
        if model == 'B05':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
        elif model == 'B10':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEW</th>\n'
        elif model == 'C10':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
            html += f'                    <th class="group-{model} header-level-2">AWD</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEW</th>\n'
        elif model == 'T03':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
    
    html += '''                </tr>
            </thead>
            <tbody>
'''
    
    # Datenreihen generieren
    MONATE = [
        "Januar", "Februar", "März", "April", "Mai", "Juni",
        "Juli", "August", "September", "Oktober", "November", "Dezember",
    ]
    monats_index = {name: i for i, name in enumerate(MONATE)}

    sorted_keys = sorted(aggregated_data.keys(), key=lambda k: (int(k[0]), monats_index[k[1]]))    #sorted_keys = sorted(aggregated_data.keys())
    
    for jahr_monat in sorted_keys:
        jahr, monat = jahr_monat
        model_data = aggregated_data[jahr_monat]
        
        def get_value(model_key, field):
            if model_key in model_data:
                val = model_data[model_key].get(field, '')
                return val if val else ''
            return ''
        
        cells = []
        
        # Jahr und Monat
        cells.append(f'<td>{jahr}</td>')
        cells.append(f'<td>{monat}</td>')
        
        # Daten für jedes Zielfeld
        for model in target_models:
            g = get_value(model, 'gesamt') or '-'
            cells.append(f'<td>{g}</td>')
            
            if model == 'B10':
                cells.append(f'<td>{get_value(model, "bev")}</td>')
                cells.append(f'<td>{get_value(model, "reew")}</td>')
            elif model == 'C10':
                cells.append(f'<td>{get_value(model, "awd")}</td>')
                cells.append(f'<td>{get_value(model, "bev")}</td>')
                cells.append(f'<td>{get_value(model, "reew")}</td>')
        
        html += '                <tr>' + ''.join(cells) + '</tr>\n'
    
    html += '''            </tbody>
        </table>
    </div>
</body>
</html>
'''
    
    if verbose:
        print(f"   ✓ HTML-Code generiert ({len(sorted_keys)} Zeilen)")
    
    return html


def save_html(html_content, filename, dry_run=False, quiet=False):
    """Speichert die HTML-Datei."""
    if dry_run:
        print(f"\n[DRY-RUN] HTML würde gespeichert: {filename}")
        print(f"           Dateigröße: {len(html_content)} Bytes")
        return True
    
    try:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(html_content)
        print(f"✓ HTML-Datei gespeichert: {filename}")
        print(f"  Größe: {len(html_content)} Bytes")
        return True
    except Exception as e:
        print(f"✗ ERROR beim Speichern: {e}")
        return False


def main():
    """Hauptfunktion."""
    args = parse_arguments()
    
    quiet = args.quiet
    verbose = args.verbose
    
    if not quiet:
        print("=" * 60)
        print("CSV-zu-HTML Tabellengenerator v2.0")
        print("=" * 60)
        print(f"Input:  {args.input}")
        print(f"Output: {args.output}")
        if args.models:
            print(f"Models: {args.models}")
        print()
    
    # Schritt 1: CSV laden
    data = load_csv_data(args.input, verbose, quiet)
    if not data:
        sys.exit(1)
    
    # Schritt 2: Spalten-Mapping definieren
    # WICHTIG: Passe diese Namen an deine CSV an!
    column_mapping = {
        'jahr': '\ufeffBerichtsjahr',
        'monat': 'Berichtsmonat',
        'modellreihe': 'Modellreihe',
        'gesamt': 'Anzahl',
        'bev': 'Elektro (BEV)',
        'reew': 'Plug-in-Hybrid',
        'awd': 'Allradantrieb'
    }
    
    # Warnung falls Spalten fehlen (nur im Verbose-Modus)
    if verbose:
        csv_headers = data[0].keys() if data else []
        missing = [k for k, v in column_mapping.items() if v not in csv_headers]
        if missing:
            print(f"⚠ WARNUNG: Fehlende Spalten könnten Probleme verursachen:")
            for m in missing:
                print(f"   - Gesucht: '{column_mapping[m]}'")
    
    # Schritt 3: Daten aggregieren
    aggregated = aggregate_by_month_and_model(data, column_mapping, verbose, quiet)
    
    if not aggregated:
        print("✗ ERROR: Keine gültigen Daten gefunden.")
        sys.exit(1)
    
    if not quiet:
        all_models = set()
        for md in aggregated.values():
            all_models.update(md.keys())
        print(f"✓ Gefundene Modellreihen: {', '.join(sorted(all_models))}")
        print(f"✓ Zeitraum: {len(aggregated)} Monate")
    
    # Schritt 4: Ziel-Modelle bestimmen
    if args.models:
        target_models = [m.strip() for m in args.models.split(',')]
    else:
        target_models = ['B05', 'B10', 'C10', 'T03']
    
    if not quiet:
        print(f"\n→ Ziel-Modellreihen: {', '.join(target_models)}")
    
    # Schritt 5: HTML generieren
    html = generate_html(aggregated, target_models, verbose, quiet)
    
    # Schritt 6: Speichern
    if save_html(html, args.output, args.dry_run, quiet):
        if not args.dry_run and not quiet:
            print("\n" + "=" * 60)
            print("✅ Fertig!")
            print("=" * 60)
            
            # Browser öffnen (optional)
            try:
                import webbrowser
                import os
                filepath = os.path.abspath(args.output)
                webbrowser.open(filepath)
                print(f"🌐 Öffne automatisch: {args.output}")
            except:
                pass


if __name__ == "__main__":
    main()