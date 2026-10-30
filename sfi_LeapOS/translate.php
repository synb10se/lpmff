<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=UTF-8');
set_time_limit(120);

$fail = static function (int $status): never {
    http_response_code($status);
    echo json_encode(['ok' => false]);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail(405);
}

$id = cleanText($_POST['id'] ?? '', 32);
$language = currentLanguage();
session_write_close();

$item = null;
foreach (loadSuggestions() as $candidate) {
    if (($candidate['id'] ?? '') === $id) {
        $item = $candidate;
        break;
    }
}
if ($item === null) {
    $fail(404);
}

if (!verifySourceLanguage($item)) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $GLOBALS['translate_error'] ?? 'detect failed']);
    exit;
}

$translated = !needsTranslation($item, $language) || translateSuggestionItem($item, $language);

// Reload before saving and skip entries edited in the meantime.
$all = loadSuggestions();
foreach ($all as &$entry) {
    if (($entry['id'] ?? '') === $id
        && ($entry['topic'] ?? '') === ($item['topic'] ?? '')
        && ($entry['suggestion'] ?? '') === ($item['suggestion'] ?? '')) {
        $entry['source_language'] = $item['source_language'];
        $entry['language_checked'] = true;
        $entry['translations'] = $item['translations'] ?? [];
    }
}
unset($entry);
saveSuggestions($all);

if (!$translated) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $GLOBALS['translate_error'] ?? 'translate failed']);
    exit;
}

echo json_encode([
    'ok' => true,
    'topic' => suggestionTextForLanguage($item, 'topic'),
    'suggestion' => suggestionTextForLanguage($item, 'suggestion'),
], JSON_UNESCAPED_UNICODE);
