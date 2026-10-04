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
    $error = t('login.error');
}

if (!$authenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="<?= e(currentLanguage()) ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e(t('export.login.title')) ?></title><link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>"></head>
    <body><main class="page-shell"><?= renderLanguageSelector(true) ?><section class="form-panel" style="max-width: 520px; margin: 10vh auto 0;"><p class="eyebrow"><?= e(t('admin.area')) ?></p><h1 style="font-size: 2.5rem;"><?= e(t('export.link')) ?></h1><p class="intro"><?= e(t('export.login.intro')) ?></p>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <form method="post"><input type="hidden" name="action" value="login"><label for="password"><?= e(t('field.password')) ?><input id="password" name="password" type="password" autocomplete="current-password" required autofocus></label><button class="primary-button" type="submit"><?= e(t('login.submit')) ?></button></form>
    </section></main></body></html>
    <?php
    exit;
}

function translateLibreTranslateBatch(array $texts): ?array
{
    $endpoint = libreTranslateEndpoint();
    $endpointParts = parse_url($endpoint);
    if ($endpoint === '' || !is_array($endpointParts) || !in_array($endpointParts['scheme'] ?? '', ['http', 'https'], true) || !isset($endpointParts['host'])) {
        return null;
    }

    $payload = ['q' => array_values($texts), 'source' => 'de', 'target' => 'en', 'format' => 'text'];
    $apiKey = trim((string) (getenv('LIBRETRANSLATE_API_KEY') ?: ''));
    if ($apiKey !== '') {
        $payload['api_key'] = $apiKey;
    }

    try {
        $requestBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (JsonException) {
        return null;
    }

    $response = null;
    if (function_exists('curl_init')) {
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $curlResponse = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (is_string($curlResponse) && $httpStatus >= 200 && $httpStatus < 300) {
            $response = $curlResponse;
        }
    } else {
        $response = @file_get_contents($endpoint, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $requestBody,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]));
    }

    if (!is_string($response) || $response === '') {
        return null;
    }

    try {
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }

    $translations = $decoded['translatedText'] ?? null;
    if (is_string($translations) && count($texts) === 1) {
        $translations = [$translations];
    }
    if (!is_array($translations) || count($translations) !== count($texts)) {
        return null;
    }

    foreach ($translations as &$translation) {
        if (!is_string($translation) || trim($translation) === '') {
            return null;
        }
        $translation = trim($translation);
    }
    unset($translation);

    return $translations;
}

function translateExportContents(array $texts): array
{
    $trimmedTexts = array_map(static fn (mixed $text): string => trim((string) $text), $texts);
    $chunksByText = [];
    $allChunks = [];

    foreach ($trimmedTexts as $textIndex => $text) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($sentences === false || $sentences === []) {
            $sentences = $text === '' ? [] : [$text];
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

        $chunksByText[$textIndex] = [];
        foreach ($chunks as $chunk) {
            $chunksByText[$textIndex][] = count($allChunks);
            $allChunks[] = $chunk;
        }
    }

    if ($allChunks === []) {
        return $trimmedTexts;
    }
    if (libreTranslateEndpoint() === '') {
        $GLOBALS['export_translation_unavailable'] = true;
        return $trimmedTexts;
    }

    $translatedChunks = [];
    foreach (array_chunk($allChunks, 10) as $batch) {
        $translations = translateLibreTranslateBatch($batch);
        if ($translations === null) {
            $GLOBALS['export_translation_unavailable'] = true;
            $translations = $batch;
        }
        array_push($translatedChunks, ...$translations);
    }

    $translatedTexts = [];
    foreach ($chunksByText as $textIndex => $chunkIndexes) {
        $parts = array_map(static fn (int $chunkIndex): string => $translatedChunks[$chunkIndex], $chunkIndexes);
        $translatedTexts[$textIndex] = trim(implode(' ', $parts));
    }

    return $translatedTexts;
}

function translateExportContent(string $text): string
{
    return translateExportContents([$text])[0] ?? '';
}

function exportRowForDisplay(array $item, ?array $translatedContent = null): array
{
    static $englishCatalogue = null;
    $englishCatalogue ??= require dirname(__DIR__) . '/languages/en.php';
    $version = displayLeapOsVersion($item['leapos_version'] ?? null);
    $category = trim((string) ($item['category'] ?? ''));
    $classification = trim((string) ($item['classification'] ?? ''));

    return [
        'number' => (string) ($item['number'] ?? ''),
        'topic' => $translatedContent[0] ?? translateExportContent((string) ($item['topic'] ?? '')),
        'model' => ($item['model'] ?? '') === '' || ($item['model'] ?? '') === ALL_MODELS ? 'all' : (string) $item['model'],
        'leapos_version' => in_array($version, [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true) ? $englishCatalogue['version.all'] : $version,
        'category' => $category === '' ? 'Not specified' : ($englishCatalogue['value.' . $category] ?? 'Not specified'),
        'classification' => $classification === '' ? 'Not specified' : ($englishCatalogue['value.' . $classification] ?? 'Not specified'),
        'suggestion' => $translatedContent[1] ?? translateExportContent((string) ($item['suggestion'] ?? '')),
    ];
}

function exportRowsForDisplay(array $items): array
{
    $freeText = [];
    foreach ($items as $item) {
        $freeText[] = (string) ($item['topic'] ?? '');
        $freeText[] = (string) ($item['suggestion'] ?? '');
    }
    $translatedText = translateExportContents($freeText);

    $displayRows = [];
    foreach ($items as $index => $item) {
        $displayRows[] = ['id' => $item['id'] ?? ''] + exportRowForDisplay($item, [
            $translatedText[$index * 2] ?? '',
            $translatedText[$index * 2 + 1] ?? '',
        ]);
    }

    return $displayRows;
}

function selectedReviewedSuggestions(array $suggestions): array
{
    $ids = is_array($_POST['ids'] ?? null) ? array_map(static fn (mixed $id): string => cleanText($id, 32), $_POST['ids']) : [];
    return array_values(array_filter($suggestions, static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft' && ($ids === [] || in_array($item['id'] ?? '', $ids, true))));
}

function markExportedSuggestionsAsSent(array $exportedItems): bool
{
    $exportedIds = [];
    foreach ($exportedItems as $item) {
        $id = is_array($item) ? ($item['id'] ?? '') : '';
        if (is_string($id) && $id !== '') {
            $exportedIds[$id] = true;
        }
    }
    if ($exportedIds === []) {
        return true;
    }

    $suggestions = loadSuggestions();
    $changed = false;
    foreach ($suggestions as &$item) {
        $id = $item['id'] ?? '';
        if (is_string($id) && isset($exportedIds[$id]) && ($item['status'] ?? '') === 'geprüft') {
            $item['status'] = 'versendet';
            $changed = true;
        }
    }
    unset($item);

    return !$changed || saveSuggestions($suggestions);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['csv', 'excel'], true)) {
    $items = selectedReviewedSuggestions(loadSuggestions());
    $displayItems = exportRowsForDisplay($items);
    $rows = [['number', 'topic', 'model', 'leapos_version', 'category', 'classification', 'suggestion']];
    foreach ($displayItems as $translated) {
        $rows[] = [$translated['number'], $translated['topic'], $translated['model'], $translated['leapos_version'], $translated['category'], $translated['classification'], $translated['suggestion']];
    }
    if ($_POST['action'] === 'csv') {
        $tmpFile = tempnam(sys_get_temp_dir(), 'reviewed_suggestions_');
        if ($tmpFile === false) {
            http_response_code(500);
            exit(t('export.error.create'));
        }
        $output = fopen($tmpFile, 'wb');
        if ($output === false) {
            unlink($tmpFile);
            http_response_code(500);
            exit(t('export.error.create'));
        }
        $writeFailed = fwrite($output, "\xEF\xBB\xBF") === false;
        foreach ($rows as $row) {
            if (fputcsv($output, $row, ';', '"', '') === false) {
                $writeFailed = true;
                break;
            }
        }
        $writeFailed = !fflush($output) || $writeFailed;
        fclose($output);
        if ($writeFailed) {
            unlink($tmpFile);
            http_response_code(500);
            exit(t('export.error.create'));
        }
        if (!markExportedSuggestionsAsSent($items)) {
            unlink($tmpFile);
            http_response_code(500);
            exit(t('export.error.mark_sent'));
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reviewed-suggestions-en.csv"');
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }

    $columnWidths = [];
    foreach (array_keys($rows[0]) as $columnIndex) {
        $longestLine = 0;
        foreach ($rows as $row) {
            $lines = preg_split('/\r\n|\r|\n/', (string) ($row[$columnIndex] ?? '')) ?: [''];
            foreach ($lines as $line) {
                $longestLine = max($longestLine, mb_strlen($line));
            }
        }
        $columnWidths[$columnIndex] = min(max($longestLine + 2, 10), 60);
    }
    $columnWidths[6] = min($columnWidths[6], 54);

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(false);
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('worksheet');
    $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $xml->startElement('cols');
    foreach ($columnWidths as $columnIndex => $width) {
        $xml->startElement('col');
        $xml->writeAttribute('min', (string) ($columnIndex + 1));
        $xml->writeAttribute('max', (string) ($columnIndex + 1));
        $xml->writeAttribute('width', (string) $width);
        $xml->writeAttribute('customWidth', '1');
        $xml->endElement();
    }
    $xml->endElement();
    $xml->startElement('sheetData');
    foreach ($rows as $rowIndex => $row) {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) ($rowIndex + 1));
        foreach ($row as $cellIndex => $cell) {
            $column = chr(65 + $cellIndex);
            $xml->startElement('c');
            $xml->writeAttribute('r', $column . ($rowIndex + 1));
            $xml->writeAttribute('t', 'inlineStr');
            $xml->writeAttribute('s', $cellIndex === 6 ? '2' : '1');
            $xml->startElement('is');
            $xml->writeElement('t', (string) $cell);
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
    <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">
      <alignment vertical="top"/>
    </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">
            <alignment vertical="top" wrapText="1"/>
        </xf>
  </cellXfs>
</styleSheet>
XML;

    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'reviewed_suggestions_');
    if ($tmpFile === false) {
        http_response_code(500);
        exit(t('export.error.create'));
    }
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        unlink($tmpFile);
        http_response_code(500);
        exit(t('export.error.create'));
    }
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
    if (!$zip->close()) {
        unlink($tmpFile);
        http_response_code(500);
        exit(t('export.error.create'));
    }
    if (!markExportedSuggestionsAsSent($items)) {
        unlink($tmpFile);
        http_response_code(500);
        exit(t('export.error.mark_sent'));
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reviewed-suggestions-en.xlsx"');
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

$items = array_values(array_filter(loadSuggestions(), static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft'));
$GLOBALS['export_translation_unavailable'] = libreTranslateEndpoint() === '';
$displayItems = exportRowsForDisplay($items);
?>
<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e(t('export.title')) ?></title><link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>"></head>
<body><main class="page-shell">
    <header class="page-header"><div><p class="eyebrow"><?= e(t('export.protected')) ?></p><h1 class="export-heading"><?= e(t('export.title')) ?></h1><p class="intro"><?= e(t('export.intro')) ?></p></div><div class="area-links"><a class="admin-link" href="../admin/"><?= e(t('export.edit_link')) ?></a><a class="admin-link" href="../"><?= e(t('export.intake_link')) ?></a><?= renderLanguageSelector() ?></div></header>
    <?php if ($GLOBALS['export_translation_unavailable']): ?><p class="message error"><?= e(t('export.translation_unavailable')) ?></p><?php endif; ?>
        <section class="admin-panel export-panel"><p class="export-note"><?= e(t('export.note')) ?></p><form class="export-actions" method="post"><label class="select-all"><input id="select-all" type="checkbox"> <?= e(t('export.select_all')) ?></label><button class="small-button" name="action" value="csv" type="submit"><?= e(t('export.csv')) ?></button><button class="small-button" name="action" value="excel" type="submit"><?= e(t('export.excel')) ?></button>
  <?php foreach ($items as $item): ?><input class="export-id" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" hidden><?php endforeach; ?></form></section>
        <p id="export-download-error" class="message error" role="alert" hidden></p>
    <section class="table-section export-table-section"><div class="section-heading"><div><p class="eyebrow"><?= e(t('overview')) ?></p><h2><?= e(t('export.translation')) ?></h2></div><span class="count-badge"><?= count($items) ?> <?= e(t(count($items) === 1 ? 'entry' : 'entries')) ?></span></div><div class="table-wrap"><table><thead><tr><th><?= e(t('table.selection')) ?></th><th><?= e(t('table.number')) ?></th><th><?= e(t('table.topic')) ?></th><th><?= e(t('field.model')) ?></th><th><?= e(t('field.version')) ?></th><th><?= e(t('field.category')) ?></th><th><?= e(t('field.classification')) ?></th><th><?= e(t('table.suggestion')) ?></th></tr></thead><tbody>
    <?php if ($items === []): ?><tr><td class="empty-state" colspan="8"><?= e(t('export.empty')) ?></td></tr><?php else: foreach ($displayItems as $translated): ?><tr><td class="check-cell"><label class="export-row-select"><input class="row-select" type="checkbox" value="<?= e($translated['id']) ?>" aria-label="<?= e(t('export.select_row')) ?>"><span class="export-row-select-label"><?= e(t('export.select_row')) ?></span></label></td><td data-label="<?= e(t('table.number')) ?>"><?= e($translated['number']) ?></td><td data-label="<?= e(t('table.topic')) ?>"><?= e($translated['topic']) ?></td><td data-label="<?= e(t('field.model')) ?>"><span class="model-tag"><?= e($translated['model']) ?></span></td><td data-label="<?= e(t('field.version.mobile')) ?>"><?= e($translated['leapos_version']) ?></td><td data-label="<?= e(t('field.category')) ?>"><?= e($translated['category']) ?></td><td data-label="<?= e(t('field.classification.mobile')) ?>"><?= e($translated['classification']) ?></td><td data-label="<?= e(t('table.suggestion')) ?>" class="suggestion-cell"><?= nl2br(e($translated['suggestion'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
    <script>
        const exportForm = document.querySelector('.export-actions');
        const all = document.getElementById('select-all');
        const rows = [...document.querySelectorAll('.row-select')];
        const hidden = [...document.querySelectorAll('.export-id')];
        const downloadError = document.getElementById('export-download-error');
        const downloadErrorText = <?= json_encode(t('export.error.download'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        function sync() {
            rows.forEach((row, index) => { hidden[index].checked = row.checked; });
            all.checked = rows.length > 0 && rows.every((row) => row.checked);
        }

        rows.forEach((row) => row.addEventListener('change', sync));
        all.addEventListener('change', () => {
            rows.forEach((row) => { row.checked = all.checked; });
            sync();
        });
        exportForm.addEventListener('submit', async (event) => {
            const action = event.submitter?.value;
            if (!['csv', 'excel'].includes(action)) return;
            event.preventDefault();
            event.submitter.disabled = true;
            downloadError.hidden = true;

            try {
                const formData = new FormData(exportForm);
                formData.set('action', action);
                const response = await fetch(exportForm.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });
                const expectedType = action === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                if (!response.ok || !response.headers.get('Content-Type')?.includes(expectedType)) {
                    throw new Error('Export request failed');
                }

                const objectUrl = URL.createObjectURL(await response.blob());
                const downloadLink = document.createElement('a');
                downloadLink.href = objectUrl;
                downloadLink.download = action === 'csv' ? 'reviewed-suggestions-en.csv' : 'reviewed-suggestions-en.xlsx';
                document.body.append(downloadLink);
                downloadLink.click();
                downloadLink.remove();
                setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
                setTimeout(() => window.location.reload(), 250);
            } catch {
                downloadError.textContent = downloadErrorText;
                downloadError.hidden = false;
                event.submitter.disabled = false;
            }
        });
    </script>
</main></body></html>