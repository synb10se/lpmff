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
from decimal import Decimal, InvalidOperation
import html as html_module
import io
from pathlib import Path
import sys


def configure_output_encoding():
    """Prevent server locale settings from breaking status output."""
    for stream_name in ('stdout', 'stderr'):
        stream = getattr(sys, stream_name)
        if hasattr(stream, 'buffer'):
            wrapped_stream = io.TextIOWrapper(
                stream.buffer,
                encoding='utf-8',
                errors='replace',
            )
            setattr(sys, stream_name, wrapped_stream)


configure_output_encoding()


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
        default='lm-kba-statistik.html',
        help='Ausgabe-HTML-Datei (Standard: lm-kba-statistik.html)'
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
        
        print(f"   ✓ {len(data)} Datensätze geladen")
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
        if row.get(column_mapping['marke'], '').strip().upper() != 'LEAPMOTOR':
            continue

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
                'reev': '',
                'awd': ''
            }
        
        # Werte extrahieren
        aggregated[key][modellreihe]['gesamt'] = row.get(column_mapping['gesamt'], '').strip() or '-'
        bev_val = row.get(column_mapping['bev'], '').strip()
        reev_val = row.get(column_mapping['reev'], '').strip()
        awd_val = row.get(column_mapping['awd'], '').strip()
        
        aggregated[key][modellreihe]['bev'] = bev_val if bev_val else ''
        aggregated[key][modellreihe]['reev'] = reev_val if reev_val else ''
        aggregated[key][modellreihe]['awd'] = awd_val if awd_val else ''
    
    if skipped > 0 and verbose:
        print(f"   ℹ {skipped} Zeilen übersprungen (unvollständige Daten)")
    
    return aggregated


def generate_detail_table(data, column_mapping):
    """Generiert kumulierte Werte je Jahr, Monat und Marke."""
    headers = [
        'Jahr',
        'Monat',
        column_mapping['marke'], 'Anzahl kumuliert p.a.',
        column_mapping['reev'], 
        column_mapping['bev'], 
    ]
    display_headers = [header.lstrip('\ufeff') for header in headers]
    monat_index = {
        name: index for index, name in enumerate((
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
        ), start=1)
    }
    filtered_rows = [
        row for row in data
        if row.get(column_mapping['bev'], '').strip()
        or row.get(column_mapping['reev'], '').strip()
    ]

    monthly_totals = {}
    for row in filtered_rows:
        jahr = row.get(column_mapping['jahr'], '').strip()
        monat = row.get(column_mapping['monat'], '').strip()
        marke = row.get(column_mapping['marke'], '').strip()
        key = (jahr, monat, marke)
        if key not in monthly_totals:
            monthly_totals[key] = {
                'gesamt': Decimal('0'),
                'reev': Decimal('0'),
                'bev': Decimal('0'),
            }
        for field in ('gesamt', 'reev', 'bev'):
            value = row.get(column_mapping[field], '').strip()
            if value:
                try:
                    monthly_totals[key][field] += Decimal(
                        value.replace('.', '').replace(',', '.')
                    )
                except InvalidOperation:
                    pass

    sorted_keys = sorted(
        monthly_totals,
        key=lambda key: (
            int(key[0] or 0),
            monat_index.get(key[1], 0),
            key[2].upper(),
        ),
    )

    cumulative_totals = {}
    annual_brand_totals = {}
    for jahr, monat, marke in sorted_keys:
        annual_brand_totals[(jahr, marke)] = (
            annual_brand_totals.get((jahr, marke), Decimal('0'))
            + monthly_totals[(jahr, monat, marke)]['gesamt']
        )
    largest_brand_totals = {}
    for (jahr, marke), total in annual_brand_totals.items():
        largest_brand_totals[jahr] = max(
            largest_brand_totals.get(jahr, Decimal('0')),
            total,
        )

    detail_html = '''
    <div class="detail-table-container tab-panel" id="panel-market-electric">
        <div class="detail-heading">
            <h2>Marktvergleich Elektro- und Plug-in-Hybrid</h2>
            <p>Je Marke werden die Jahreswerte kumuliert; die Dezemberfarbe zeigt den relativen Anteil der Marke im Vergleich zur stärksten Marke.</p>
        </div>
        <div class="table-filter" aria-label="Zeitraumfilter">
            <label>Jahr <select data-filter="year"><option value="">Alle</option></select></label>
            <label>Monat <select data-filter="month"><option value="">Alle</option></select></label>
            <button type="button" data-filter-reset>Gesamtanzeige</button>
        </div>
        <table class="detail-table">
            <thead>
                <tr>'''
    detail_html += ''.join(
        f'<th>{html_module.escape(header or "")}</th>'
        for header in display_headers
    )
    detail_html += '''</tr>
            </thead>
            <tbody>
'''
    for jahr, monat, marke in sorted_keys:
        total_key = (jahr, marke)
        monthly_values = monthly_totals[(jahr, monat, marke)]
        if total_key not in cumulative_totals:
            cumulative_totals[total_key] = {
                'gesamt': Decimal('0'),
                'reev': Decimal('0'),
                'bev': Decimal('0'),
            }
        for field in ('gesamt', 'reev', 'bev'):
            cumulative_totals[total_key][field] += monthly_values[field]

        row_class = ' class="detail-december-row"' if monat == 'Dezember' else ''
        share_color = None
        share_text_color = None
        share_percent = None
        if monat == 'Dezember' and largest_brand_totals[jahr] > 0:
            share = annual_brand_totals[total_key] / largest_brand_totals[jahr]
            share_percent = f'{share * 100:.1f}'.replace('.', ',') + '%'
            if share > Decimal('0.8'):
                share_color = 'LimeGreen'
                share_text_color = '#111'
            elif share > Decimal('0.6'):
                share_color = 'khaki'
                share_text_color = '#111'
            elif share > Decimal('0.4'):
                share_color = 'PeachPuff'
                share_text_color = '#111'
            elif share > Decimal('0.2'):
                share_color = 'LightSteelBlue'
                share_text_color = '#111'
            else:
                share_color = 'gray'
                share_text_color = '#fff'
        cumulative_anzahl = f'{cumulative_totals[total_key]["gesamt"]:,.0f}'.replace(',', '.')
        if share_percent:
            cumulative_anzahl += f' ({share_percent})'
        values = [
            jahr,
            monat,
            marke,
            cumulative_anzahl,
            f'{cumulative_totals[total_key]["reev"]:,.0f}'.replace(',', '.'),
            f'{cumulative_totals[total_key]["bev"]:,.0f}'.replace(',', '.'),
        ]
        detail_html += f'                <tr{row_class}>'
        detail_html += ''.join(
            f'<td style="background-color: {share_color}; color: {share_text_color}">{html_module.escape(value)}</td>'
            if monat == 'Dezember' and index == 3 and share_color
            else f'<td class="detail-december-cell">{html_module.escape(value)}</td>'
            if monat == 'Dezember' and index in (0, 1, 2)
            else f'<td>{html_module.escape(value)}</td>'
            for index, value in enumerate(values)
        )
        detail_html += '</tr>\n'
    detail_html += '''            </tbody>
        </table>
    </div>
'''
    return detail_html


def generate_market_comparison_table(data, column_mapping):
    """Generiert den kumulierten Vergleich von Elektro- und Verbrennerantrieben."""
    month_names = (
        'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
    )
    month_index = {name: index for index, name in enumerate(month_names, start=1)}

    monthly_totals = {}
    for row in data:
        jahr = row.get(column_mapping['jahr'], '').strip()
        monat = row.get(column_mapping['monat'], '').strip()
        if not jahr or not monat:
            continue
        key = (jahr, monat)
        totals = monthly_totals.setdefault(key, {
            'elektro': Decimal('0'),
            'verbrenner': Decimal('0'),
        })
        def parse_value(field):
            value = row.get(column_mapping[field], '').strip()
            if not value:
                return Decimal('0')
            try:
                return Decimal(value.replace('.', '').replace(',', '.'))
            except InvalidOperation:
                return Decimal('0')

        gesamt = parse_value('gesamt')
        elektro = parse_value('reev') + parse_value('bev')
        totals['elektro'] += elektro
        totals['verbrenner'] += max(gesamt - elektro, Decimal('0'))

    sorted_keys = sorted(
        monthly_totals,
        key=lambda key: (int(key[0] or 0), month_index.get(key[1], 0)),
    )

    monthly_brand_totals = {}
    for row in data:
        jahr = row.get(column_mapping['jahr'], '').strip()
        monat = row.get(column_mapping['monat'], '').strip()
        marke = row.get(column_mapping['marke'], '').strip()
        if not jahr or not monat or not marke:
            continue
        key = (jahr, monat)
        if key not in monthly_totals:
            continue
        def parse_value(field):
            value = row.get(column_mapping[field], '').strip()
            try:
                return Decimal(value.replace('.', '').replace(',', '.')) if value else Decimal('0')
            except InvalidOperation:
                return Decimal('0')

        gesamt = parse_value('gesamt')
        elektro = parse_value('reev') + parse_value('bev')
        brand_key = (jahr, monat, marke)
        brand_totals = monthly_brand_totals.setdefault(brand_key, {
            'elektro': Decimal('0'), 'verbrenner': Decimal('0'),
        })
        brand_totals['elektro'] += elektro
        brand_totals['verbrenner'] += max(gesamt - elektro, Decimal('0'))

    cumulative_totals = {}
    comparison_rows = []
    for jahr, monat in sorted_keys:
        total_key = jahr
        total = cumulative_totals.setdefault(total_key, {
            'elektro': Decimal('0'), 'verbrenner': Decimal('0'),
        })
        for field in ('elektro', 'verbrenner'):
            total[field] += monthly_totals[(jahr, monat)][field]

        comparison_rows.append((
            (jahr, monat),
            dict(total),
        ))

    detail_html = '''
    <div class="detail-table-container tab-panel" id="panel-market-comparison">
        <div class="detail-heading">
            <h2>Marktvergleich Verbrenner-Elektromobilität</h2>
            <p>Elektroantrieb umfasst Plug-in-Hybrid und Elektro (BEV); Verbrennerantrieb ist die verbleibende Anzahl. Beide Werte werden über das Jahr kumuliert.</p>
        </div>
        <div class="table-filter" aria-label="Zeitraumfilter">
            <label>Jahr <select data-filter="year"><option value="">Alle</option></select></label>
            <label>Monat <select data-filter="month"><option value="">Alle</option></select></label>
            <button type="button" data-filter-reset>Gesamtanzeige</button>
        </div>
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Jahr</th>
                    <th>Monat</th>
                    <th>Anzahl kumuliert p.a.</th>
                    <th>Elektroantrieb</th>
                    <th>Verbrennerantrieb</th>
                    <th>Spitzenreiter Elektroantrieb</th>
                    <th>Spitzenreiter Verbrennerantrieb</th>
                </tr>
            </thead>
            <tbody>
'''
    period_leaders = {}
    cumulative_brand_totals = {}
    periods = sorted(
        {(key[0], key[1]) for key, _ in comparison_rows},
        key=lambda period: (int(period[0] or 0), month_index.get(period[1], 0)),
    )
    for jahr, monat in periods:
        for (brand_year, brand_month, marke), monthly_values in monthly_brand_totals.items():
            if brand_year == jahr and brand_month == monat:
                brand_key = (jahr, marke)
                brand_total = cumulative_brand_totals.setdefault(brand_key, {
                    'elektro': Decimal('0'), 'verbrenner': Decimal('0'),
                })
                for field in ('elektro', 'verbrenner'):
                    brand_total[field] += monthly_values[field]
        brand_values = {
            brand: totals for (brand_year, brand), totals in cumulative_brand_totals.items()
            if brand_year == jahr
        }
        period_leaders[(jahr, monat)] = {
            field: max(
                brand_values,
                key=lambda brand: (brand_values[brand][field], brand),
            ) if brand_values else ''
            for field in ('elektro', 'verbrenner')
        }

    for key, totals in comparison_rows:
        jahr, monat = key
        anzahl = totals['elektro'] + totals['verbrenner']
        values = [
            jahr, monat,
            f'{anzahl:,.0f}'.replace(',', '.'),
            f'{totals["elektro"]:,.0f}'.replace(',', '.'),
            f'{totals["verbrenner"]:,.0f}'.replace(',', '.'),
            period_leaders[(jahr, monat)]['elektro'],
            period_leaders[(jahr, monat)]['verbrenner'],
        ]
        row_class = ' class="detail-december-row"' if monat == 'Dezember' else ''
        detail_html += f'                <tr{row_class}>'
        detail_html += ''.join(
            f'<td class="detail-december-cell">{html_module.escape(value)}</td>'
            if monat == 'Dezember'
            else f'<td>{html_module.escape(value)}</td>'
            for index, value in enumerate(values)
        )
        detail_html += '</tr>\n'

    detail_html += '''            </tbody>
        </table>
    </div>
'''
    return detail_html


def generate_html(aggregated_data, target_models, detail_data=None,
                  column_mapping=None, verbose=False, quiet=False):
    """Generiert den vollständigen HTML-Code."""
    if not quiet:
        log("\nHTML generieren...", verbose, quiet)
    
    # CSS definieren
    css_colors = {
        'B03X': {'main': "#737d82", 'header': "#585f63"}, 
        'B05': {'main': '#e6c84a', 'header': '#d4ba3e'},
        'B10': {'main': '#a9a2c9', 'header': '#9b91bf'},
        'C10': {'main': '#9ebca5', 'header': '#8cae98'},
        'T03': {'main': '#b8c7cf', 'header': '#a8bdb9'}
    }
    december_colors = ['#f7d9d4', '#d8e8f5', '#e3efd7', '#f5e8c8', '#e5dcf2']
    
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
        .tab-layout {
            width: 100%;
        }
        .tab-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .tab-button {
            border: 1px solid #aeb1c5;
            border-radius: 4px 4px 0 0;
            padding: 9px 12px;
            background: #d5d7e3;
            color: #333;
            cursor: pointer;
            font: inherit;
            text-align: left;
        }
        .tab-button.active {
            background: #c5c7d8;
            font-weight: bold;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .table-container {
            max-height: 80vh;
            overflow: auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .detail-table-container {
            margin-top: 30px;
            max-height: 80vh;
            overflow: auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container h2,
        .detail-table-container h2 {
            margin: 0;
            font-size: 18px;
            line-height: 22px;
        }
        .detail-heading {
            position: sticky;
            top: 0;
            z-index: 4;
            display: flex;
            align-items: baseline;
            gap: 16px;
            height: 48px;
            box-sizing: border-box;
            padding: 5px 10px 10px;
            background: #c5c7d8;
            border-bottom: 1px solid #aeb1c5;
        }
        .detail-heading p {
            margin: 0;
            color: #555;
            font-size: 12px;
            white-space: normal;
        }
        .table-filter {
            position: sticky;
            top: 48px;
            z-index: 4;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 10px;
            min-height: 44px;
            padding: 6px 10px;
            background: #f0f1f6;
            border-bottom: 1px solid #c5c7d8;
        }
        .table-filter label {
            font-size: 12px;
            font-weight: bold;
        }
        .table-filter select,
        .table-filter button {
            min-height: 28px;
            padding: 4px 8px;
            border: 1px solid #aeb1c5;
            border-radius: 3px;
            background: white;
            font: inherit;
        }
        .table-filter button {
            cursor: pointer;
            background: #d5d7e3;
        }
        .detail-table-container table thead tr:first-child th {
            top: 92px;
            z-index: 3;
        }
        .tab-panel > table thead tr:first-child th {
            top: 92px;
            z-index: 3;
        }
        .tab-panel > table thead tr:nth-child(2) th {
            top: 132px;
            z-index: 3;
        }
        .detail-december-cell {
            background-color: #f5e8c8;
        }
        table {
            border-collapse: collapse;
            table-layout: auto;
            width: max-content;
            min-width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: center;
            font-size: 13px;
            white-space: nowrap;
            box-sizing: border-box;
        }
        thead tr:first-child th {
            position: sticky;
            top: 92px;
            z-index: 2;
            background-color: #e1e2ef;
        }
        thead tr:nth-child(2) th {
            position: sticky;
            top: 132px;
            z-index: 2;
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
'''
    for color_index, color in enumerate(december_colors):
        html += f'''        tr.december-row-{color_index} td:nth-child(-n+3) {{
            background-color: {color};
        }}
'''

    html += '''
        td {
            font-family: 'Courier New', monospace;
            color: #333;
        }
        td:first-child, td:nth-child(2) {
            text-align: left;
            font-family: Arial, sans-serif;
        }
        @media (max-width: 700px) {
            .tab-list {
                flex-direction: column;
            }
            .tab-button {
                width: 100%;
            }
            .detail-heading {
                display: block;
                height: 72px;
            }
            .detail-heading p {
                margin-top: 4px;
            }
            .table-filter {
                top: 72px;
                min-height: 70px;
            }
            .detail-table-container table thead tr:first-child th,
            .tab-panel > table thead tr:first-child th,
            thead tr:first-child th {
                top: 142px;
            }
            .tab-panel > table thead tr:nth-child(2) th,
            thead tr:nth-child(2) th {
                top: 182px;
            }
        }
    </style>
</head>
<body>
    <div class="tab-layout">
        <div class="tab-list" role="tablist">
            <button class="tab-button active" type="button" role="tab" aria-selected="true" data-tab="panel-registrations">Neuzulassungen Leapmotor</button>
            <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab="panel-market-electric">Marktvergleich Elektro- und Plug-in-Hybrid</button>
            <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab="panel-market-comparison">Marktvergleich Verbrenner-Elektromobilität</button>
        </div>
    <div class="table-container tab-panel active" id="panel-registrations">
        <div class="detail-heading">
            <h2>Neuzulassungen Leapmotor</h2>
            <p>Die Tabelle zeigt ausschließlich LEAPMOTOR-Neuzulassungen; die kumulierte Anzahl wird innerhalb jedes Jahres fortgeschrieben.</p>
        </div>
        <div class="table-filter" aria-label="Zeitraumfilter">
            <label>Jahr <select data-filter="year"><option value="">Alle</option></select></label>
            <label>Monat <select data-filter="month"><option value="">Alle</option></select></label>
            <button type="button" data-filter-reset>Gesamtanzeige</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Jahr</th>
                    <th rowspan="2">Monat</th>
                    <th rowspan="2">Kumuliert pro Jahr</th>
'''
    
    # Dynamische Kopfzeilen basierend auf Zielmodellen
    model_configs = {
        'B03X': {'colspan': 1, 'has_sub': True},
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
        
        if model == 'B03X':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
        elif model == 'B05':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
        elif model == 'B10':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEV</th>\n'
        elif model == 'C10':
            html += f'                    <th class="group-{model} header-level-2">gesamt</th>\n'
            html += f'                    <th class="group-{model} header-level-2">AWD</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEV</th>\n'
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
    yearly_totals = {}
    december_row_index = 0
    
    for jahr_monat in sorted_keys:
        jahr, monat = jahr_monat
        model_data = aggregated_data[jahr_monat]
        
        def get_value(model_key, field):
            if model_key in model_data:
                val = model_data[model_key].get(field, '')
                return val if val else ''
            return ''

        monthly_total = Decimal('0')
        for model in target_models:
            value = get_value(model, 'gesamt').replace('.', '').replace(',', '.')
            if value and value != '-':
                try:
                    monthly_total += Decimal(value)
                except InvalidOperation:
                    continue
        yearly_totals[jahr] = yearly_totals.get(jahr, Decimal('0')) + monthly_total
        
        cells = []
        
        # Jahr und Monat
        cells.append(f'<td>{jahr}</td>')
        cells.append(f'<td>{monat}</td>')
        cumulative_total = yearly_totals[jahr]
        cumulative_display = f'{cumulative_total:,.0f}'.replace(',', '.')
        cells.append(f'<td>{cumulative_display}</td>')
        
        # Daten für jedes Zielfeld
        for model in target_models:
            g = get_value(model, 'gesamt') or '-'
            cells.append(f'<td>{g}</td>')
            
            if model == 'B10':
                cells.append(f'<td>{get_value(model, "bev")}</td>')
                cells.append(f'<td>{get_value(model, "reev")}</td>')
            elif model == 'C10':
                cells.append(f'<td>{get_value(model, "awd")}</td>')
                cells.append(f'<td>{get_value(model, "bev")}</td>')
                cells.append(f'<td>{get_value(model, "reev")}</td>')
        
        row_class = ''
        if monat == 'Dezember':
            color_index = december_row_index % len(december_colors)
            row_class = f' class="december-row-{color_index}"'
            december_row_index += 1

        html += f'                <tr{row_class}>' + ''.join(cells) + '</tr>\n'
    
    html += '''            </tbody>
        </table>
    </div>
'''
    if detail_data is not None and column_mapping is not None:
        html += generate_detail_table(detail_data, column_mapping)
        html += generate_market_comparison_table(detail_data, column_mapping)

    html += '''</div>
    <script>
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-button').forEach(tab => {
                    tab.classList.toggle('active', tab === button);
                    tab.setAttribute('aria-selected', tab === button ? 'true' : 'false');
                });
                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.toggle('active', panel.id === button.dataset.tab);
                });
            });
        });

        const monthOrder = {
            Januar: 1, Februar: 2, März: 3, April: 4, Mai: 5, Juni: 6,
            Juli: 7, August: 8, September: 9, Oktober: 10, November: 11, Dezember: 12
        };
        const panels = Array.from(document.querySelectorAll('.tab-panel'));
        const filters = Array.from(document.querySelectorAll('.table-filter'));
        const allRows = panels.map(panel => Array.from(panel.querySelectorAll('tbody tr')));
        const years = [...new Set(allRows.flat().map(row => row.cells[0]?.textContent.trim()).filter(Boolean))].sort();
        const months = Object.keys(monthOrder);

        filters.forEach(filter => {
            const yearSelect = filter.querySelector('[data-filter="year"]');
            const monthSelect = filter.querySelector('[data-filter="month"]');
            years.forEach(year => yearSelect.appendChild(new Option(year, year)));
            months.forEach(month => monthSelect.appendChild(new Option(month, month)));
        });

        const applyGlobalFilter = (year, month) => {
            filters.forEach(filter => {
                filter.querySelector('[data-filter="year"]').value = year;
                filter.querySelector('[data-filter="month"]').value = month;
            });
            allRows.forEach(rows => rows.forEach(row => {
                const matchesYear = !year || row.cells[0]?.textContent.trim() === year;
                const matchesMonth = !month || row.cells[1]?.textContent.trim() === month;
                row.hidden = !(matchesYear && matchesMonth);
            }));
        };

        filters.forEach(filter => {
            filter.querySelectorAll('select').forEach(select => select.addEventListener('change', () => {
                applyGlobalFilter(
                    filter.querySelector('[data-filter="year"]').value,
                    filter.querySelector('[data-filter="month"]').value,
                );
            }));
            filter.querySelector('[data-filter-reset]').addEventListener('click', () => {
                applyGlobalFilter('', '');
            });
        });
    </script>
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
        'marke': 'Marke',
        'jahr': '\ufeffBerichtsjahr',
        'monat': 'Berichtsmonat',
        'modellreihe': 'Modellreihe',
        'gesamt': 'Anzahl',
        'bev': 'Elektro (BEV)',
        'reev': 'Plug-in-Hybrid',
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
        target_models = ['B03X', 'B05', 'B10', 'C10', 'T03']
    
    if not quiet:
        print(f"\n→ Ziel-Modellreihen: {', '.join(target_models)}")
    
    # Schritt 5: HTML generieren
    html = generate_html(
        aggregated,
        target_models,
        detail_data=data,
        column_mapping=column_mapping,
        verbose=verbose,
        quiet=quiet,
    )
    
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