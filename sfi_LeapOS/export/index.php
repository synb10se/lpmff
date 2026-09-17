<?php
declare(strict_types=1);
session_start();
require dirname(__DIR__) . '/lib.php';

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = preg_match('/^(localhost|127\.0\.0\.1)(:[0-9]+)?$/', $host) === 1;
$passwordFile = $isLocalHost
    ? '/Applications/MAMP/access/sfi_LeapOS/.htpasswd'
    : '/var/www/vhosts/h331132.web114.alfahosting-server.de/access/sfi_LeapOS/.htpasswd';
$error = null;
$authenticated = ($_SESSION['suggestions_export_authenticated'] ?? false) === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    foreach (file($passwordFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        $parts = explode(':', $line, 2);
        $hash = count($parts) === 2 ? trim($parts[1]) : $line;
        if ($password !== '' && verifyHtpasswdPassword($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['suggestions_export_authenticated'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?'));
            exit;
        }
    }
    $error = 'Das Passwort ist nicht korrekt.';
}

if (!$authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Export-Anmeldung</title><link rel="stylesheet" href="../style.css"></head>
    <body><main class="page-shell"><section class="form-panel" style="max-width: 520px; margin: 10vh auto 0;"><p class="eyebrow">Geschützter Bereich</p><h1 style="font-size: 2.5rem;">Export</h1><p class="intro">Bitte Passwort eingeben, um geprüfte Vorschläge zu exportieren.</p>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <form method="post"><input type="hidden" name="action" value="login"><label for="password">Passwort<input id="password" name="password" type="password" autocomplete="current-password" required autofocus></label><button class="primary-button" type="submit">Anmelden</button></form>
    </section></main></body></html>
    <?php
    exit;
}

function translateExportContent(string $text): string
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return '';
    }

    $sentences = preg_split('/(?<=[.!?])\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
    if ($sentences === false || $sentences === []) {
        $sentences = [$trimmed];
    }

    $chunks = [];
    $current = '';
    foreach ($sentences as $sentence) {
        $candidate = $current === '' ? $sentence : $current . ' ' . $sentence;
        if (mb_strlen($candidate) <= 500) {
            $current = $candidate;
            continue;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }
        $current = $sentence;
    }
    if ($current !== '') {
        $chunks[] = $current;
    }

    $translatedParts = [];
    foreach ($chunks as $chunk) {
        $translated = null;
        $urls = [
            'https://api.mymemory.translated.net/get?q=' . rawurlencode($chunk) . '&langpair=de|en',
            'https://translate.googleapis.com/translate_a/single?client=gtx&sl=de&tl=en&dt=t&q=' . rawurlencode($chunk),
        ];

        foreach ($urls as $serviceUrl) {
            $response = null;

            if (function_exists('curl_init')) {
                $ch = curl_init($serviceUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 8,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERAGENT => 'Mozilla/5.0',
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $response = curl_exec($ch);
                if ($response === false) {
                    $response = null;
                }
                curl_close($ch);
            }

            if ($response === null && function_exists('file_get_contents')) {
                try {
                    $response = @file_get_contents($serviceUrl, false, stream_context_create([
                        'http' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true],
                        'https' => ['method' => 'GET', 'timeout' => 15, 'ignore_errors' => true],
                    ]));
                } catch (Throwable $exception) {
                    $response = null;
                }
            }

            if (!is_string($response) || $response === '') {
                continue;
            }

            try {
                $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $exception) {
                continue;
            }

            if (isset($decoded['responseData']['translatedText']) && is_string($decoded['responseData']['translatedText']) && trim($decoded['responseData']['translatedText']) !== '') {
                $translated = trim($decoded['responseData']['translatedText']);
                break;
            }

            if (isset($decoded[0]) && is_array($decoded[0])) {
                $parts = [];
                foreach ($decoded[0] as $part) {
                    if (isset($part[0]) && is_string($part[0])) {
                        $parts[] = $part[0];
                    }
                }
                $combined = trim(implode('', $parts));
                if ($combined !== '') {
                    $translated = $combined;
                    break;
                }
            }
        }

        $translatedParts[] = $translated !== null ? $translated : $chunk;
    }

    return trim(implode(' ', $translatedParts));
}

function exportRowForDisplay(array $item): array
{
    // Only the free-form content fields are translated for export.
    // Model identifiers stay as-is, because they are technical labels and not natural-language text.
    return [
        'topic' => translateExportContent((string) ($item['topic'] ?? '')),
        'model' => ($item['model'] ?? '') === '' || ($item['model'] ?? '') === ALL_MODELS ? 'all' : (string) $item['model'],
        'suggestion' => translateExportContent((string) ($item['suggestion'] ?? '')),
    ];
}

function selectedReviewedSuggestions(array $suggestions): array
{
    $ids = is_array($_POST['ids'] ?? null) ? array_map(static fn (mixed $id): string => cleanText($id, 32), $_POST['ids']) : [];
    return array_values(array_filter($suggestions, static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft' && ($ids === [] || in_array($item['id'] ?? '', $ids, true))));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['csv', 'excel'], true)) {
    $items = selectedReviewedSuggestions(loadSuggestions());
    $rows = [['topic', 'model', 'suggestion']];
    foreach ($items as $item) {
        $translated = exportRowForDisplay($item);
        $rows[] = [$translated['topic'], $translated['model'], $translated['suggestion']];
    }
    if ($_POST['action'] === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reviewed-suggestions-en.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;
    }

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(false);
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('worksheet');
    $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $xml->startElement('sheetData');
    foreach ($rows as $rowIndex => $row) {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) ($rowIndex + 1));
        foreach ($row as $cellIndex => $cell) {
            $column = chr(65 + $cellIndex);
            $xml->startElement('c');
            $xml->writeAttribute('r', $column . ($rowIndex + 1));
            $xml->writeAttribute('t', 'inlineStr');
            $xml->writeAttribute('s', '1');
            $xml->startElement('is');
            $xml->writeElement('t', str_replace("\n", ' ', (string) $cell));
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();
    }
    $xml->endElement();
    $xml->endElement();
    $xml->endDocument();
    $sheetXml = $xml->outputMemory(true);

    $stylesXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1">
    <font>
      <sz val="11"/>
      <name val="Calibri"/>
      <family val="2"/>
    </font>
  </fonts>
  <fills count="1">
    <fill><patternFill patternType="none"/></fill>
  </fills>
  <borders count="1">
    <border><left/><right/><top/><bottom/><diagonal/></border>
  </borders>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">
      <alignment vertical="top"/>
    </xf>
  </cellXfs>
</styleSheet>
XML;

    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'reviewed_suggestions_');
    if ($tmpFile === false) {
        http_response_code(500);
        exit('Export konnte nicht erstellt werden.');
    }
    $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML
    );
    $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML
    );
    $zip->addFromString('docProps/core.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>LeapOS</dc:creator><cp:lastModifiedBy>LeapOS</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2026-09-16T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2026-09-16T00:00:00Z</dcterms:modified></cp:coreProperties>
XML
    );
    $zip->addFromString('docProps/app.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>LeapOS</Application></Properties>
XML
    );
    $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Suggestions" sheetId="1" r:id="rId1"/></sheets></workbook>
XML
    );
    $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>
XML
    );
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reviewed-suggestions-en.xlsx"');
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

$items = array_values(array_filter(loadSuggestions(), static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft'));
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Geprüfte Vorschläge exportieren</title><link rel="stylesheet" href="../style.css"><style>.export-actions{display:flex;align-items:end;gap:12px;flex-wrap:wrap}.export-actions label{min-width:180px}.export-actions select{background:#fff}.select-all{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.86rem;font-weight:700}.select-all input,.check-cell input{width:18px;height:18px}.export-note{margin:0 0 20px;color:var(--muted)}.export-heading{white-space:nowrap;font-size:clamp(2rem,4.4vw,4rem)}.export-links{display:flex;gap:32px;flex-wrap:wrap}.export-links .admin-link{margin:0}.export-table-section thead th{top:0}@media(max-width:700px){.export-actions{align-items:stretch;flex-direction:column}.export-actions button{width:100%}.export-heading{font-size:2rem}}</style></head>
<body><main class="page-shell">
    <header class="page-header"><div><p class="eyebrow">Geschützter Export</p><h1 class="export-heading">Geprüfte Vorschläge</h1><p class="intro">Deutsche Vorschläge werden für die externe Verwendung ins Englische übersetzt.</p></div><div class="area-links"><a class="admin-link" href="../admin/">Vorschläge bearbeiten</a><a class="admin-link" href="../">Erfassung</a></div></header>
    <section class="admin-panel"><p class="export-note">Einzelne Einträge oder alle geprüften Einträge auswählen und anschließend ein Exportformat wählen.</p><form class="export-actions" method="post"><label class="select-all"><input id="select-all" type="checkbox"> Alle auswählen</label><button class="small-button" name="action" value="csv" type="submit">CSV exportieren</button><button class="small-button" name="action" value="excel" type="submit">Excel exportieren</button>
  <?php foreach ($items as $item): ?><input class="export-id" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" hidden><?php endforeach; ?></form></section>
    <section class="table-section export-table-section"><div class="section-heading"><div><p class="eyebrow">Übersicht</p><h2>Englische Übersetzung</h2></div><span class="count-badge"><?= count($items) ?> Einträge</span></div><div class="table-wrap"><table><thead><tr><th>selection</th><th>topic</th><th>model</th><th>suggestion</th></tr></thead><tbody>
    <?php if ($items === []): ?><tr><td class="empty-state" colspan="4">Keine geprüften Vorschläge vorhanden.</td></tr><?php else: foreach ($items as $item): $translated = exportRowForDisplay($item); ?><tr><td class="check-cell"><input class="row-select" type="checkbox" value="<?= e($item['id'] ?? '') ?>" aria-label="Eintrag auswählen"></td><td data-label="topic"><?= e($translated['topic']) ?></td><td data-label="model"><span class="model-tag"><?= e($translated['model']) ?></span></td><td data-label="suggestion" class="suggestion-cell"><?= nl2br(e($translated['suggestion'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
  <script>const all=document.getElementById('select-all');const rows=[...document.querySelectorAll('.row-select')];const hidden=[...document.querySelectorAll('.export-id')];function sync(){rows.forEach((row,i)=>{hidden[i].checked=row.checked});all.checked=rows.length>0&&rows.every(row=>row.checked)}rows.forEach(row=>row.addEventListener('change',sync));all.addEventListener('change',()=>{rows.forEach(row=>row.checked=all.checked);sync()});</script>
</main></body></html>