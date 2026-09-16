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
        if ($password !== '' && password_verify($password, $hash)) {
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

function englishText(string $text): string
{
    $phrases = [
        'Verbesserungsvorschläge' => 'Improvement suggestions',
        'Verbesserungsvorschlag' => 'Improvement suggestion',
        'Benutzeroberfläche' => 'user interface',
        'Benutzeroberflächen' => 'user interfaces',
        'nicht möglich' => 'not possible',
        'nicht verfügbar' => 'not available',
        'zur Verfügung' => 'available',
        'zurücksetzen' => 'reset',
        'hinzufügen' => 'add',
        'auswählen' => 'select',
    ];
    uksort($phrases, static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));
    foreach ($phrases as $german => $english) {
        $text = preg_replace('/(?<!\p{L})' . preg_quote($german, '/') . '(?!\p{L})/iu', $english, $text) ?? $text;
    }

    $translations = [
        'Verbesserungsvorschlag' => 'Improvement suggestion',
        'Verbesserungsvorschläge' => 'Improvement suggestions',
        'Verbesserung' => 'Improvement',
        'Vorschlag' => 'Suggestion',
        'Vorschläge' => 'Suggestions',
        'Thema' => 'Topic',
        'Problem' => 'Problem',
        'Fehler' => 'Error',
        'Lösung' => 'Solution',
        'Anzeige' => 'Display',
        'Ansicht' => 'View',
        'Benutzer' => 'User',
        'Benutzung' => 'Usage',
        'Funktion' => 'Feature',
        'Funktionen' => 'Features',
        'Einstellung' => 'setting',
        'Einstellungen' => 'settings',
        'Meldung' => 'message',
        'Nachricht' => 'message',
        'Daten' => 'data',
        'Lautstärke' => 'volume',
        'Ton' => 'sound',
        'Navigation' => 'navigation',
        'Verbindung' => 'connection',
        'Laden' => 'charging',
        'Reichweite' => 'range',
        'Verbrauch' => 'consumption',
        'Klimaanlage' => 'air conditioning',
        'Heizung' => 'heating',
        'Tastatur' => 'keyboard',
        'Touchscreen' => 'touchscreen',
        'nicht' => 'not',
        'und' => 'and',
        'oder' => 'or',
        'mit' => 'with',
        'ohne' => 'without',
        'für' => 'for',
        'von' => 'of',
        'bei' => 'when',
        'kann' => 'can',
        'soll' => 'should',
        'muss' => 'must',
        'werden' => 'become',
        'wird' => 'will be',
        'sein' => 'be',
        'ist' => 'is',
        'sind' => 'are',
        'ein' => 'a',
        'eine' => 'a',
        'neue' => 'new',
        'neu' => 'new',
        'besser' => 'better',
        'verbessern' => 'improve',
        'ermöglichen' => 'enable',
        'hinzufügen' => 'add',
        'ändern' => 'change',
        'löschen' => 'delete',
    ];
    return preg_replace_callback('/\b[\p{L}ÄÖÜäöüß]+\b/u', static function (array $match) use ($translations): string {
        $word = $match[0];
        $lowerWord = mb_strtolower($word);
        $translated = $translations[$word] ?? $translations[$lowerWord] ?? null;
        if ($translated === null) {
            return $word;
        }
        return mb_strtoupper(mb_substr($word, 0, 1)) === mb_substr($word, 0, 1)
            ? ucfirst($translated)
            : $translated;
    }, $text) ?? $text;
}

function selectedReviewedSuggestions(array $suggestions): array
{
    $ids = is_array($_POST['ids'] ?? null) ? array_map(static fn (mixed $id): string => cleanText($id, 32), $_POST['ids']) : [];
    return array_values(array_filter($suggestions, static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft' && ($ids === [] || in_array($item['id'] ?? '', $ids, true))));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['csv', 'excel'], true)) {
    $items = selectedReviewedSuggestions(loadSuggestions());
    $rows = [['Thema', 'Modell', 'Vorschlag']];
    foreach ($items as $item) {
        $rows[] = [englishText((string) ($item['topic'] ?? '')), (string) ($item['model'] ?? ''), englishText((string) ($item['suggestion'] ?? ''))];
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
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reviewed-suggestions-en.xls"');
    echo "<html><head><meta charset=\"UTF-8\"></head><body><table border=\"1\">";
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . e($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

$items = array_values(array_filter(loadSuggestions(), static fn (array $item): bool => ($item['status'] ?? '') === 'geprüft'));
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Geprüfte Vorschläge exportieren</title><link rel="stylesheet" href="../style.css"><style>.export-actions{display:flex;align-items:end;gap:12px;flex-wrap:wrap}.export-actions label{min-width:180px}.export-actions select{background:#fff}.select-all{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.86rem;font-weight:700}.select-all input,.check-cell input{width:18px;height:18px}.export-note{margin:0 0 20px;color:var(--muted)}.export-heading{white-space:nowrap;font-size:clamp(2rem,4.4vw,4rem)}.export-links{display:flex;gap:32px;flex-wrap:wrap}.export-links .admin-link{margin:0}@media(max-width:700px){.export-actions{align-items:stretch;flex-direction:column}.export-actions button{width:100%}.export-heading{font-size:2rem}}</style></head>
<body><main class="page-shell">
    <header class="page-header"><div><p class="eyebrow">Geschützter Export</p><h1 class="export-heading">Geprüfte Vorschläge</h1><p class="intro">Deutsche Vorschläge werden für die externe Verwendung ins Englische übersetzt.</p></div><div class="area-links"><a class="admin-link" href="../admin/">Vorschläge bearbeiten</a><a class="admin-link" href="../">Erfassung</a></div></header>
    <section class="admin-panel"><p class="export-note">Einzelne Einträge oder alle geprüften Einträge auswählen und anschließend ein Exportformat wählen.</p><form class="export-actions" method="post"><label class="select-all"><input id="select-all" type="checkbox"> Alle auswählen</label><button class="small-button" name="action" value="csv" type="submit">CSV exportieren</button><button class="small-button" name="action" value="excel" type="submit">Excel exportieren</button>
  <?php foreach ($items as $item): ?><input class="export-id" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" hidden><?php endforeach; ?></form></section>
    <section class="table-section"><div class="section-heading"><div><p class="eyebrow">Übersicht</p><h2>Englische Übersetzung</h2></div><span class="count-badge"><?= count($items) ?> Einträge</span></div><div class="table-wrap"><table><thead><tr><th>Auswahl</th><th>Thema</th><th>Modell</th><th>Vorschlag</th></tr></thead><tbody>
    <?php if ($items === []): ?><tr><td class="empty-state" colspan="4">Keine geprüften Vorschläge vorhanden.</td></tr><?php else: foreach ($items as $item): ?><tr><td class="check-cell"><input class="row-select" type="checkbox" value="<?= e($item['id'] ?? '') ?>" aria-label="Eintrag auswählen"></td><td data-label="Thema"><?= e(englishText((string) ($item['topic'] ?? ''))) ?></td><td data-label="Modell"><span class="model-tag"><?= e($item['model'] ?? '') ?></span></td><td data-label="Vorschlag" class="suggestion-cell"><?= nl2br(e(englishText((string) ($item['suggestion'] ?? '')))) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
  <script>const all=document.getElementById('select-all');const rows=[...document.querySelectorAll('.row-select')];const hidden=[...document.querySelectorAll('.export-id')];function sync(){rows.forEach((row,i)=>{hidden[i].checked=row.checked});all.checked=rows.length>0&&rows.every(row=>row.checked)}rows.forEach(row=>row.addEventListener('change',sync));all.addEventListener('change',()=>{rows.forEach(row=>row.checked=all.checked);sync()});</script>
</main></body></html>