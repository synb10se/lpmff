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
        <div class="stat-banner" data-panel="panel-market-electric" tabindex="0" role="button" aria-label="Marktvergleich Elektro- und Plug-in-Hybrid anzeigen">
            <div class="stat-banner__copy">
                <div class="stat-banner__title">Marktvergleich Elektro- und Plug-in-Hybrid</div>
                <div class="stat-banner__meta">Filtern nach Jahr und Monat · Statistik durch Anklicken vergrössern</div>
            </div>
            <div class="chart-panel">
                <h3>Trend pro Monat</h3>
                <svg id="chart-market-electric" class="trend-chart" viewBox="0 0 900 260" preserveAspectRatio="xMidYMid meet"></svg>
                <div class="chart-tooltip" role="status" aria-live="polite"></div>
                <div class="chart-legend" aria-label="Diagrammlegende"></div>
            </div>
            <button type="button" class="stat-banner__button">Grafik anzeigen</button>
        </div>
        <div class="panel-full-content">
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
    sortable_headers = []
    for index, (header, sort_key) in enumerate(zip(
        display_headers,
        ('year', 'month', 'brand', '', '', ''),
    )):
        attributes = (
            f' data-sortable data-sort-key="{sort_key}"'
            f' data-sort-column="{index}" tabindex="0"'
            if sort_key else ''
        )
        sortable_headers.append(
            f'<th{attributes}>{html_module.escape(header or "")}</th>'
        )
    detail_html += ''.join(sortable_headers)
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
            f'{monthly_values["reev"]:,.0f}'.replace(',', '.'),
            f'{monthly_values["bev"]:,.0f}'.replace(',', '.'),
        ]
        detail_html += (
            f'                <tr{row_class} '
            f'data-reev-monthly="{monthly_values["reev"]}" '
            f'data-bev-monthly="{monthly_values["bev"]}">'
        )
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
        <div class="stat-banner" data-panel="panel-market-comparison" tabindex="0" role="button" aria-label="Marktvergleich Verbrenner-Elektromobilität anzeigen">
            <div class="stat-banner__copy">
                <div class="stat-banner__title">Marktvergleich Verbrenner-Elektromobilität</div>
                <div class="stat-banner__meta">Filtern nach Jahr und Monat · Statistik durch Anklicken vergrössern</div>
            </div>
            <div class="chart-panel">
                <h3>Trend pro Monat</h3>
                <svg id="chart-market-comparison" class="trend-chart" viewBox="0 0 900 260" preserveAspectRatio="xMidYMid meet"></svg>
                <div class="chart-tooltip" role="status" aria-live="polite"></div>
                <div class="chart-legend" aria-label="Diagrammlegende"></div>
            </div>
            <button type="button" class="stat-banner__button">Grafik anzeigen</button>
        </div>
        <div class="panel-full-content">
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
                    <th data-sortable data-sort-key="year" data-sort-column="0" tabindex="0">Jahr</th>
                    <th data-sortable data-sort-key="month" data-sort-column="1" tabindex="0">Monat</th>
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
        monthly_values = monthly_totals[(jahr, monat)]
        anzahl = totals['elektro'] + totals['verbrenner']
        values = [
            jahr, monat,
            f'{anzahl:,.0f}'.replace(',', '.'),
            f'{monthly_values["elektro"]:,.0f}'.replace(',', '.'),
            f'{monthly_values["verbrenner"]:,.0f}'.replace(',', '.'),
            period_leaders[(jahr, monat)]['elektro'],
            period_leaders[(jahr, monat)]['verbrenner'],
        ]
        row_class = ' class="detail-december-row"' if monat == 'Dezember' else ''
        detail_html += (
            f'                <tr{row_class} '
            f'data-elektro-monthly="{monthly_values["elektro"]}" '
            f'data-verbrenner-monthly="{monthly_values["verbrenner"]}">'
        )
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
        .panel-full-content {
            display: block;
        }
        .stat-banner {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(180px, min(34vw, 420px)) minmax(0, 1fr);
            align-items: center;
            gap: 18px;
            padding: 12px 18px;
            margin: 12px 0 18px;
            background: #f3f5f6;
            border: 1px solid #d5dadd;
            border-radius: 6px;
            cursor: pointer;
        }
        .stat-banner:focus-visible {
            outline: 3px solid rgba(59, 130, 246, 0.45);
            outline-offset: 2px;
        }
        .stat-banner__title {
            font-size: 17px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-banner__copy {
            grid-column: 1;
            min-width: 0;
        }
        .stat-banner__meta {
            font-size: 12px;
            color: #4b5563;
            margin-top: 4px;
        }
        .stat-banner__button {
            grid-column: 3;
            justify-self: end;
            border: 1px solid #93c5fd;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .stat-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.65);
        }
        .stat-modal[hidden] {
            display: none;
        }
        .stat-modal__backdrop {
            position: absolute;
            inset: 0;
        }
        .stat-modal__dialog {
            position: relative;
            width: min(98vw, 1650px);
            max-height: 96vh;
            overflow: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.24);
            padding: 18px 18px 12px;
            z-index: 1;
        }
        .stat-modal__close {
            position: sticky;
            top: 8px;
            float: right;
            margin-left: 12px;
            border: none;
            background: #e5e7eb;
            color: #111827;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
        }
        .stat-modal__body .panel-full-content {
            display: block;
        }
        .stat-banner .chart-panel {
            grid-column: 2;
            width: 100%;
            margin: 0;
            padding: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
            cursor: zoom-in;
        }
        .stat-banner .chart-panel h3,
        .stat-banner .chart-legend,
        .stat-banner .chart-tooltip {
            display: none;
        }
        .stat-banner .trend-chart {
            height: 52px;
            background: none;
            border: 0;
            border-radius: 0;
        }
        .stat-banner .trend-chart text,
        .stat-banner .trend-chart line {
            display: none;
        }
        .stat-modal__body .chart-panel {
            width: 100%;
            margin: 0;
            cursor: default;
        }
        .stat-modal__body .trend-chart {
            height: min(58vh, 520px);
            background: #fff;
            border: 0;
        }
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            margin-top: 10px;
            font-size: 13px;
            color: #374151;
        }
        .chart-panel {
            position: relative;
        }
        .chart-tooltip {
            display: none;
            position: absolute;
            top: 42px;
            right: 22px;
            z-index: 2;
            max-width: min(70%, 360px);
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            background: #fff;
            color: #1f2937;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
            font-size: 13px;
            pointer-events: none;
        }
        .chart-tooltip.is-visible {
            display: block;
        }
        .chart-point {
            cursor: pointer;
            stroke: #fff;
            stroke-width: 1.5;
        }
        .chart-point:focus {
            stroke: #111827;
            stroke-width: 3;
            outline: none;
        }
        .chart-legend__item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .chart-legend__swatch {
            width: 18px;
            height: 3px;
            border-radius: 2px;
        }
        body.modal-open {
            overflow: hidden;
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
        .chart-panel {
            margin: 18px 0 12px;
            background: white;
            border: 1px solid #d7dbe8;
            border-radius: 8px;
            padding: 12px 12px 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .chart-panel h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #2d3748;
        }
        .trend-chart {
            width: 100%;
            height: 260px;
            display: block;
            background: linear-gradient(#f9fafc, #ffffff);
            border: 1px solid #e5e7eb;
            border-radius: 6px;
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
        th[data-sortable] {
            cursor: pointer;
        }
        th[data-sortable]::after {
            content: ' ↕';
            color: #6b6e7e;
            font-size: 11px;
        }
        th[data-sortable][aria-sort="ascending"]::after {
            content: ' ↑';
        }
        th[data-sortable][aria-sort="descending"]::after {
            content: ' ↓';
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
            .stat-banner {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px 14px;
            }
            .stat-banner__copy {
                grid-column: 1;
                min-width: 0;
            }
            .stat-banner__button {
                grid-column: 2;
                grid-row: 1;
            }
            .stat-banner .chart-panel {
                grid-column: 1 / -1;
                max-width: none;
            }
            .stat-banner .trend-chart {
                height: 44px;
            }
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
        .page-footer {
            margin-top: 48px;
            text-align: center;
            font-size: .85rem;
        }
        .page-footer a {
            color: #555;
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
        <div class="stat-banner" data-panel="panel-registrations" tabindex="0" role="button" aria-label="Neuzulassungen Leapmotor anzeigen">
            <div class="stat-banner__copy">
                <div class="stat-banner__title">Neuzulassungen Leapmotor</div>
                <div class="stat-banner__meta">Filtern nach Jahr und Monat · Statistik durch Anklicken vergrössern</div>
            </div>
            <div class="chart-panel">
                <h3>Trend pro Modell und Monat</h3>
                <svg id="chart-registrations" class="trend-chart" viewBox="0 0 900 260" preserveAspectRatio="xMidYMid meet"></svg>
                <div class="chart-tooltip" role="status" aria-live="polite"></div>
                <div class="chart-legend" aria-label="Diagrammlegende"></div>
            </div>
            <button type="button" class="stat-banner__button">Grafik anzeigen</button>
        </div>
        <div class="panel-full-content">
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
                    <th rowspan="2" data-sortable data-sort-key="year" data-sort-column="0" tabindex="0">Jahr</th>
                    <th rowspan="2" data-sortable data-sort-key="month" data-sort-column="1" tabindex="0">Monat</th>
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
    model_totals = {model: Decimal('0') for model in target_models}
    for model_data in aggregated_data.values():
        for model in target_models:
            value = model_data.get(model, {}).get('gesamt', '').replace('.', '').replace(',', '.')
            if value and value != '-':
                try:
                    model_totals[model] += Decimal(value)
                except InvalidOperation:
                    continue
    
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
        total_label = f'gesamt ({model_totals[model]:,.0f}'.replace(',', '.') + ')'
        
        if model == 'B03X':
            html += f'                    <th class="group-{model} header-level-2">{total_label}</th>\n'
        elif model == 'B05':
            html += f'                    <th class="group-{model} header-level-2">{total_label}</th>\n'
        elif model == 'B10':
            html += f'                    <th class="group-{model} header-level-2">{total_label}</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEV</th>\n'
        elif model == 'C10':
            html += f'                    <th class="group-{model} header-level-2">{total_label}</th>\n'
            html += f'                    <th class="group-{model} header-level-2">AWD</th>\n'
            html += f'                    <th class="group-{model} header-level-2">BEV</th>\n'
            html += f'                    <th class="group-{model} header-level-2">REEV</th>\n'
        elif model == 'T03':
            html += f'                    <th class="group-{model} header-level-2">{total_label}</th>\n'
    
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
    </div>
'''
    if detail_data is not None and column_mapping is not None:
        html += generate_detail_table(detail_data, column_mapping)
        html += generate_market_comparison_table(detail_data, column_mapping)

    html += '''</div>
    <script>
        const monthOrder = {
            Januar: 1, Februar: 2, März: 3, April: 4, Mai: 5, Juni: 6,
            Juli: 7, August: 8, September: 9, Oktober: 10, November: 11, Dezember: 12
        };
        const chartColors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f'];

        const panels = Array.from(document.querySelectorAll('.tab-panel'));
        const filters = Array.from(document.querySelectorAll('.table-filter'));
        const allRows = panels.map(panel => Array.from(panel.querySelectorAll('tbody tr')));
        const allTableRows = allRows.flat();
        const years = [...new Set(allTableRows.map(row => row.cells[0]?.textContent.trim()).filter(Boolean))]
            .sort((first, second) => Number(first) - Number(second));
        const months = Object.keys(monthOrder);
        const tables = panels
            .map(panel => panel.querySelector('table'))
            .filter(Boolean);

        const parseNumericValue = value => {
            if (value === null || value === undefined) return 0;
            const cleaned = String(value)
                .replace(/\\s+/g, '')
                .replace(/\\./g, '')
                .replace(',', '.');
            const number = Number(cleaned.replace(/[^0-9.-]/g, ''));
            return Number.isFinite(number) ? number : 0;
        };

        const getMonthNumber = row => monthOrder[row.cells[1]?.textContent.trim()] || 0;
        const latestYear = years.includes(String(new Date().getFullYear()))
            ? String(new Date().getFullYear())
            : (years[years.length - 1] || '');
        const latestMonth = [...new Set(
            allTableRows
                .filter(row => row.cells[0]?.textContent.trim() === latestYear)
                .map(getMonthNumber)
                .filter(Boolean),
        )].sort((first, second) => second - first)[0];
        const defaultMonth = latestMonth
            ? months.find(month => monthOrder[month] === latestMonth)
            : '';

        const chartData = new Map();
        const renderTrendChart = (target, xLabels, seriesList, compact = true) => {
            const svg = typeof target === 'string' ? document.getElementById(target) : target;
            if (!svg) return;
            if (svg.id) chartData.set(svg.id, { xLabels, seriesList });
            svg.innerHTML = '';
            const legend = svg.parentElement.querySelector('.chart-legend');
            const tooltip = svg.parentElement.querySelector('.chart-tooltip');
            if (legend) legend.innerHTML = '';
            if (tooltip) {
                tooltip.textContent = '';
                tooltip.classList.remove('is-visible');
                delete tooltip.dataset.pinned;
            }

            if (!xLabels.length || !seriesList.length) {
                if (!compact) {
                    svg.innerHTML = '<text x="50%" y="50%" text-anchor="middle" fill="#666" font-size="13">Keine Daten</text>';
                }
                return;
            }

            const width = 900;
            const height = compact ? 150 : 400;
            const margin = compact
                ? { top: 12, right: 12, bottom: 12, left: 12 }
                : { top: 24, right: 30, bottom: 116, left: 64 };
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            const innerWidth = width - margin.left - margin.right;
            const innerHeight = height - margin.top - margin.bottom;
            const allValues = seriesList.flatMap(series => series.values).filter(value => Number.isFinite(value));
            const maxValue = allValues.length ? Math.max(...allValues, 0) : 1;
            const safeMax = maxValue > 0 ? maxValue * 1.2 : 1;
            const xFor = index => margin.left + (xLabels.length > 1 ? (index / (xLabels.length - 1)) * innerWidth : innerWidth / 2);
            const yFor = value => margin.top + innerHeight - (value / safeMax) * innerHeight;
            const formatXAxisLabel = (label, level = 'full') => {
                const [year, month] = String(label).split(' ');
                if (!month) return label;
                const shortMonth = month.slice(0, 3);
                if (level === 'short') return `${shortMonth} ${year.slice(-2)}`;
                if (level === 'medium') return `${month} ${year.slice(-2)}`;
                return `${month} ${year}`;
            };
            const labelLevel = compact
                ? (xLabels.length > 20 ? 'short' : xLabels.length > 10 ? 'medium' : 'full')
                : (xLabels.length > 30 ? 'short' : xLabels.length > 12 ? 'medium' : 'full');
            const step = compact
                ? Math.max(1, Math.ceil(xLabels.length / 7))
                : Math.max(1, Math.ceil(xLabels.length / 12));

            if (!compact) {
                for (let tick = 0; tick <= 4; tick += 1) {
                    const value = (safeMax / 4) * tick;
                    const y = yFor(value);
                    const label = value.toLocaleString('de-DE', { maximumFractionDigits: 0 });
                    svg.insertAdjacentHTML('beforeend', `
                        <line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="#e5e7eb" stroke-width="1" />
                        <text x="${margin.left - 10}" y="${y + 4}" text-anchor="end" font-size="11" fill="#555">${label}</text>
                    `);
                }

                svg.insertAdjacentHTML('beforeend', `
                    <line x1="${margin.left}" y1="${margin.top}" x2="${margin.left}" y2="${height - margin.bottom}" stroke="#666" stroke-width="1" />
                    <line x1="${margin.left}" y1="${height - margin.bottom}" x2="${width - margin.right}" y2="${height - margin.bottom}" stroke="#666" stroke-width="1" />
                `);
            }

            xLabels.forEach((label, index) => {
                if (index % step !== 0 && index !== xLabels.length - 1) return;
                const x = xFor(index);
                const y = compact ? height - 10 : height - margin.bottom + 18;
                const textAnchor = compact ? 'middle' : 'end';
                const transform = compact ? '' : `transform="rotate(-45 ${x} ${y})"`;
                const fontSize = compact ? 8 : 11;
                const displayLabel = formatXAxisLabel(label, labelLevel);
                svg.insertAdjacentHTML('beforeend', `
                    <text x="${x}" y="${y}" ${transform} text-anchor="${textAnchor}" font-size="${fontSize}" fill="#555">${displayLabel}</text>
                `);
            });

            seriesList.forEach((series, seriesIndex) => {
                const points = series.values.map((value, index) => {
                    const x = xFor(index);
                    const y = yFor(value);
                    return `${x},${y}`;
                }).join(' ');
                const color = chartColors[seriesIndex % chartColors.length];
                svg.insertAdjacentHTML('beforeend', `
                    <polyline fill="none" stroke="${color}" stroke-width="${compact ? 4 : 2.5}" points="${points}" />
                `);
                series.values.forEach((value, index) => {
                    const x = xFor(index);
                    const y = yFor(value);
                    const totalForLabel = seriesList.reduce((sum, currentSeries) => sum + (Number(currentSeries.values[index]) || 0), 0);
                    const sharePercent = totalForLabel > 0 ? (value / totalForLabel) * 100 : 0;
                    const shareLabel = `${sharePercent.toFixed(1).replace('.', ',')}%`;
                    const label = `${xLabels[index]}: ${series.name}, ${value.toLocaleString('de-DE')} (${shareLabel})`;
                    const point = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    point.setAttribute('cx', x);
                    point.setAttribute('cy', y);
                    point.setAttribute('r', compact ? '3.5' : '5');
                    point.setAttribute('fill', color);
                    point.setAttribute('class', 'chart-point');
                    point.setAttribute('tabindex', '0');
                    point.setAttribute('role', 'img');
                    point.setAttribute('aria-label', label);
                    point.dataset.tooltip = label;
                    point.dataset.pointKey = `${seriesIndex}-${index}`;
                    const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                    title.textContent = label;
                    point.appendChild(title);

                    const showTooltip = pin => {
                        if (!tooltip) return;
                        tooltip.textContent = point.dataset.tooltip;
                        tooltip.classList.add('is-visible');
                        if (pin) tooltip.dataset.pinned = point.dataset.pointKey;
                    };
                    point.addEventListener('pointerenter', () => showTooltip(false));
                    point.addEventListener('pointerleave', () => {
                        if (tooltip?.dataset.pinned) return;
                        tooltip?.classList.remove('is-visible');
                    });
                    point.addEventListener('focus', () => showTooltip(false));
                    point.addEventListener('blur', () => {
                        if (tooltip?.dataset.pinned) return;
                        tooltip?.classList.remove('is-visible');
                    });
                    point.addEventListener('click', event => {
                        event.stopPropagation();
                        if (!tooltip) return;
                        if (tooltip.dataset.pinned === point.dataset.pointKey) {
                            delete tooltip.dataset.pinned;
                            tooltip.classList.remove('is-visible');
                        } else {
                            showTooltip(true);
                        }
                    });
                    svg.appendChild(point);
                });
                if (legend) {
                    const item = document.createElement('span');
                    item.className = 'chart-legend__item';
                    const swatch = document.createElement('span');
                    swatch.className = 'chart-legend__swatch';
                    swatch.style.backgroundColor = color;
                    const label = document.createElement('span');
                    label.textContent = series.name;
                    item.append(swatch, label);
                    legend.appendChild(item);
                }
            });
            if (!compact) {
                svg.onclick = event => {
                    if (event.target !== svg || !tooltip) return;
                    delete tooltip.dataset.pinned;
                    tooltip.classList.remove('is-visible');
                };
                svg.onkeydown = event => {
                    if (event.key !== 'Escape' || !tooltip) return;
                    delete tooltip.dataset.pinned;
                    tooltip.classList.remove('is-visible');
                };
            }
        };

        const buildRegistrationsTrend = () => {
            const table = document.querySelector('#panel-registrations table');
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => !row.hidden);
            const headerCells = Array.from(table.querySelectorAll('thead th[colspan]'));
            const models = headerCells
                .map(cell => (cell.textContent || '').replace(/\\s+/g, ' ').trim())
                .map(value => value.match(/^[A-Z0-9]+/) ? value.match(/^[A-Z0-9]+/)[0] : '')
                .filter(Boolean);
            const modelIndexes = [];
            let offset = 3;
            models.forEach(model => {
                const span = Number(table.querySelector(`.group-${model}`)?.getAttribute('colspan')) || 1;
                modelIndexes.push({ model, index: offset });
                offset += span;
            });

            const pointsByLabel = new Map();
            rows.forEach(row => {
                const year = row.cells[0]?.textContent.trim();
                const month = row.cells[1]?.textContent.trim();
                if (!year || !month) return;
                const label = `${year} ${month}`;
                const entry = pointsByLabel.get(label) || {};
                modelIndexes.forEach(({ model, index }) => {
                    entry[model] = parseNumericValue(row.cells[index]?.textContent || '0');
                });
                pointsByLabel.set(label, entry);
            });

            const labels = [...pointsByLabel.keys()];
            const seriesList = models.map(model => ({
                name: model,
                values: labels.map(label => pointsByLabel.get(label)?.[model] || 0)
            }));
            renderTrendChart('chart-registrations', labels, seriesList);
        };

        const buildMarketElectricTrend = () => {
            const table = document.querySelector('#panel-market-electric table');
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => !row.hidden);
            const grouped = new Map();
            rows.forEach(row => {
                const year = row.cells[0]?.textContent.trim();
                const month = row.cells[1]?.textContent.trim();
                if (!year || !month) return;
                const label = `${year} ${month}`;
                const current = grouped.get(label) || { reev: 0, bev: 0 };
                current.reev += parseNumericValue(row.dataset.reevMonthly || '0');
                current.bev += parseNumericValue(row.dataset.bevMonthly || '0');
                grouped.set(label, current);
            });
            const labels = [...grouped.keys()];
            const seriesList = [
                { name: 'Plug-in-Hybrid', values: labels.map(label => grouped.get(label)?.reev || 0) },
                { name: 'Elektro (BEV)', values: labels.map(label => grouped.get(label)?.bev || 0) }
            ];
            renderTrendChart('chart-market-electric', labels, seriesList);
        };

        const buildMarketComparisonTrend = () => {
            const table = document.querySelector('#panel-market-comparison table');
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => !row.hidden);
            const grouped = new Map();
            rows.forEach(row => {
                const year = row.cells[0]?.textContent.trim();
                const month = row.cells[1]?.textContent.trim();
                if (!year || !month) return;
                const label = `${year} ${month}`;
                const current = grouped.get(label) || { elektro: 0, verbrenner: 0 };
                current.elektro += parseNumericValue(row.dataset.elektroMonthly || '0');
                current.verbrenner += parseNumericValue(row.dataset.verbrennerMonthly || '0');
                grouped.set(label, current);
            });
            const labels = [...grouped.keys()];
            const seriesList = [
                { name: 'Elektroantrieb', values: labels.map(label => grouped.get(label)?.elektro || 0) },
                { name: 'Verbrennerantrieb', values: labels.map(label => grouped.get(label)?.verbrenner || 0) }
            ];
            renderTrendChart('chart-market-comparison', labels, seriesList);
        };

        const renderAllTrendCharts = () => {
            buildRegistrationsTrend();
            buildMarketElectricTrend();
            buildMarketComparisonTrend();
        };

        const sortTable = (table, header) => {
            const column = Number(header.dataset.sortColumn);
            const sortKey = header.dataset.sortKey;
            const direction = header.getAttribute('aria-sort') === 'ascending' ? -1 : 1;
            const rows = Array.from(table.tBodies[0].rows);
            const valueFor = row => row.cells[column]?.textContent.trim() || '';
            const compare = (first, second) => {
                if (sortKey === 'year') {
                    return Number(valueFor(first)) - Number(valueFor(second));
                }
                if (sortKey === 'month') {
                    return getMonthNumber(first) - getMonthNumber(second);
                }
                return valueFor(first).localeCompare(valueFor(second), 'de', { sensitivity: 'base' });
            };
            rows.sort((first, second) => direction * compare(first, second));
            table.tBodies[0].append(...rows);
            table.querySelectorAll('th[data-sortable]').forEach(otherHeader => {
                otherHeader.removeAttribute('aria-sort');
            });
            header.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
            renderAllTrendCharts();
        };

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
            renderAllTrendCharts();
        };

        filters.forEach(filter => {
            const yearSelect = filter.querySelector('[data-filter="year"]');
            const monthSelect = filter.querySelector('[data-filter="month"]');
            years.forEach(year => yearSelect.appendChild(new Option(year, year)));
            months.forEach(month => monthSelect.appendChild(new Option(month, month)));
            yearSelect.addEventListener('change', () => {
                applyGlobalFilter(yearSelect.value, monthSelect.value);
            });
            monthSelect.addEventListener('change', () => {
                applyGlobalFilter(yearSelect.value, monthSelect.value);
            });
            filter.querySelector('[data-filter-reset]').addEventListener('click', () => {
                applyGlobalFilter('', '');
            });
        });

        const modal = document.createElement('div');
        modal.className = 'stat-modal';
        modal.hidden = true;
        modal.innerHTML = `
            <div class="stat-modal__backdrop"></div>
            <div class="stat-modal__dialog">
                <button type="button" class="stat-modal__close" aria-label="Fenster schließen">×</button>
                <div class="stat-modal__body"></div>
            </div>
        `;
        document.body.appendChild(modal);

        const closeModal = () => {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            const body = modal.querySelector('.stat-modal__body');
            if (body) body.innerHTML = '';
        };

        const openModal = panelId => {
            const panel = document.getElementById(panelId);
            if (!panel) return;
            const chart = panel.querySelector('.stat-banner .chart-panel');
            if (!chart) return;
            const clone = chart.cloneNode(true);
            const sourceChartId = chart.querySelector('svg')?.id;
            const data = chartData.get(sourceChartId);
            const cloneSvg = clone.querySelector('svg');
            if (cloneSvg && data) {
                cloneSvg.removeAttribute('id');
                renderTrendChart(cloneSvg, data.xLabels, data.seriesList, false);
            }
            const body = modal.querySelector('.stat-modal__body');
            if (!body) return;
            body.innerHTML = '';
            body.appendChild(clone);
            modal.hidden = false;
            document.body.classList.add('modal-open');
            const closeButton = modal.querySelector('.stat-modal__close');
            if (closeButton) closeButton.onclick = closeModal;
            const backdrop = modal.querySelector('.stat-modal__backdrop');
            if (backdrop) backdrop.onclick = closeModal;
        };

        document.querySelectorAll('.stat-banner').forEach(banner => {
            const openFromBanner = () => openModal(banner.dataset.panel);
            banner.addEventListener('click', openFromBanner);
            banner.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openFromBanner();
                }
            });
        });

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

        tables.forEach(table => table.querySelectorAll('th[data-sortable]').forEach(header => {
            const handleSort = () => sortTable(table, header);
            header.addEventListener('click', handleSort);
            header.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    handleSort();
                }
            });
        }));

        applyGlobalFilter(latestYear, defaultMonth);
    </script>
    <footer class="page-footer"><a href="https://synvoll.de/kontakt/impressum/" target="_blank" rel="noopener">Impressum</a></footer>
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