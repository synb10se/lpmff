<?php
declare(strict_types=1);

const SUGGESTION_FILE = __DIR__ . '/data/suggestions.json';
const MODELS = ['B03X', 'B05', 'B10', 'C10', 'T03'];
const ALL_MODELS = 'alle';
const LEAPOS_VERSIONS = ['4.31.22'];
const UNKNOWN_LEAPOS_VERSION = 'alle';
const LEGACY_UNKNOWN_LEAPOS_VERSION = 'nicht bekannt';
const ADD_LEAPOS_VERSION = '__add_leapos_version__';
const CATEGORIES = ['App', 'Assistenzsysteme', 'Fahrverhalten', 'Infotainment', 'Laden', 'Sonstige'];
const CLASSIFICATIONS = ['Einschränkung', 'Fehler', 'Vorschlag'];
const STATUSES = ['erfasst', 'geprüft', 'versendet', 'abgelehnt', 'bestätigt', 'angekündigt', 'verfügbar'];

function isValidModel(string $model): bool
{
    return $model === ALL_MODELS || in_array($model, MODELS, true);
}

function isValidLeapOsVersion(string $version): bool
{
    return in_array($version, [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true)
        || in_array($version, LEAPOS_VERSIONS, true)
        || preg_match('/\A\d+(?:\.\d+)*\z/', $version) === 1;
}

function isPredefinedLeapOsVersion(string $version): bool
{
    return in_array($version, [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION, ...LEAPOS_VERSIONS], true);
}

function availableLeapOsVersions(array $suggestions): array
{
    $versions = [];
    foreach ($suggestions as $suggestion) {
        $version = is_array($suggestion) ? ($suggestion['leapos_version'] ?? null) : null;
        if (!is_string($version)
            || !isValidLeapOsVersion($version)
            || in_array($version, [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true)) {
            continue;
        }
        $versions[] = $version;
    }

    return array_values(array_unique($versions));
}

function resolveLeapOsVersion(mixed $selection, mixed $customVersion): string
{
    $selection = cleanText($selection, 30);
    if ($selection === ADD_LEAPOS_VERSION) {
        return cleanText($customVersion, 30);
    }

    return $selection === LEGACY_UNKNOWN_LEAPOS_VERSION ? UNKNOWN_LEAPOS_VERSION : $selection;
}

function displayLeapOsVersion(mixed $version): string
{
    if (!is_string($version) || trim($version) === '') {
        return '---';
    }

    return in_array($version, [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true) ? 'alle' : $version;
}

function renderSymbol(string $type, string $value): string
{
    $labels = [
        'category' => array_combine(CATEGORIES, CATEGORIES),
        'classification' => array_combine(CLASSIFICATIONS, CLASSIFICATIONS),
        'status' => array_combine(STATUSES, STATUSES),
    ];
    $icons = [
        'category' => [
            'App' => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 5h4M11 18h2"/>',
            'Assistenzsysteme' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M1 12h3M20 12h3"/>',
            'Fahrverhalten' => '<path d="m3 14 2-6h14l2 6v5h-2v-2H5v2H3z"/><path d="M6 14h.01M18 14h.01M7 8l1-3h8l1 3"/>',
            'Infotainment' => '<rect x="3" y="4" width="18" height="13" rx="1.5"/><path d="M8 21h8M12 17v4"/>',
            'Laden' => '<path d="m13 2-8 11h6l-1 9 9-13h-6z"/>',
            'Sonstige' => '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
        ],
        'classification' => [
            'Einschränkung' => '<circle cx="12" cy="12" r="9"/><path d="M7 12h10"/>',
            'Fehler' => '<path d="m12 3 10 18H2z"/><path d="M12 9v5M12 17h.01"/>',
            'Vorschlag' => '<path d="M9 18h6M10 22h4M8 14a7 7 0 1 1 8 0c-1 .7-1.5 1.5-1.5 2.5h-5C9.5 15.5 9 14.7 8 14Z"/>',
        ],
        'status' => [
            'erfasst' => '<path d="M3 7h18v13H3zM3 12h5l2 3h4l2-3h5"/>',
            'geprüft' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5M8 10.5l2 2 3.5-4"/>',
            'versendet' => '<path d="m22 2-7 20-4-9-9-4zM22 2 11 13"/>',
            'abgelehnt' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/>',
            'bestätigt' => '<path d="m12 2 2.5 2 3.2-.2.8 3.1 2.7 1.7-1 3 1 3-2.7 1.7-.8 3.1-3.2-.2-2.5 2-2.5-2-3.2.2-.8-3.1L2.8 14.6l1-3-1-3 2.7-1.7.8-3.1 3.2.2z"/><path d="m8.5 12 2.2 2.2 4.8-4.8"/>',
            'angekündigt' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M12 13v3l2 1"/><circle cx="12" cy="16" r="4"/>',
            'verfügbar' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
        ],
    ];
    $knownValue = isset($labels[$type][$value]);
    $label = $labels[$type][$value] ?? $value;
    $path = $icons[$type][$value] ?? '<circle cx="12" cy="12" r="8"/><path d="m7 17 10-10"/>';
    $iconClass = $knownValue ? 'symbol-icon' : 'symbol-icon symbol-icon-missing';
    return '<span class="' . $iconClass . '" role="img" aria-label="' . e($label) . '" title="' . e($label) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg></span>';
}

function loadSuggestions(): array
{
    if (!is_readable(SUGGESTION_FILE)) {
        return [];
    }

    $contents = file_get_contents(SUGGESTION_FILE);
    $suggestions = $contents === false ? null : json_decode($contents, true);
    if (!is_array($suggestions)) {
        return [];
    }

    $reservedNumbers = [];
    foreach ($suggestions as $suggestion) {
        $number = is_array($suggestion) ? ($suggestion['number'] ?? null) : null;
        if (is_int($number) && $number > 0) {
            $reservedNumbers[$number] = true;
        }
    }

    $assignedNumbers = [];
    $nextAvailableNumber = 1;
    $needsSave = false;
    foreach ($suggestions as &$suggestion) {
        if (is_array($suggestion)) {
            if (array_key_exists('author', $suggestion) || array_key_exists('author_id', $suggestion)) {
                $needsSave = true;
            }
            unset($suggestion['author'], $suggestion['author_id']);

            $number = $suggestion['number'] ?? null;
            if (!is_int($number) || $number < 1 || isset($assignedNumbers[$number])) {
                while (isset($reservedNumbers[$nextAvailableNumber]) || isset($assignedNumbers[$nextAvailableNumber])) {
                    $nextAvailableNumber++;
                }
                $number = $nextAvailableNumber++;
                $suggestion['number'] = $number;
                $needsSave = true;
            }
            $assignedNumbers[$number] = true;
        }
    }
    unset($suggestion);

    if ($needsSave) {
        saveSuggestions($suggestions);
    }

    return $suggestions;
}

function nextSuggestionNumber(array $suggestions): int
{
    $maxNumber = 0;
    foreach ($suggestions as $suggestion) {
        $number = is_array($suggestion) ? ($suggestion['number'] ?? null) : null;
        if (is_int($number) && $number > $maxNumber) {
            $maxNumber = $number;
        }
    }

    return $maxNumber + 1;
}

function saveSuggestions(array $suggestions): bool
{
    $json = json_encode($suggestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    return file_put_contents(SUGGESTION_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

function cleanText(mixed $value, int $maxLength): string
{
    $text = is_string($value) ? trim($value) : '';
    return mb_substr($text, 0, $maxLength);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function verifyHtpasswdPassword(string $password, string $storedHash): bool
{
    $storedHash = trim($storedHash);
    if ($storedHash === '') {
        return false;
    }

    if (password_verify($password, $storedHash)) {
        return true;
    }

    if (str_starts_with($storedHash, '{SHA}')) {
        $shaDigest = base64_encode(sha1($password, true));
        return hash_equals(substr($storedHash, 5), $shaDigest);
    }

    if (str_starts_with($storedHash, '$apr1$')) {
        return hash_equals($storedHash, createApr1Hash($password, $storedHash));
    }

    $cryptHash = crypt($password, $storedHash);
    return is_string($cryptHash) && hash_equals($storedHash, $cryptHash);
}

function createApr1Hash(string $password, string $storedHash): string
{
    $salt = substr($storedHash, 6, 8);
    $magic = '$apr1$';
    $alternate = md5($password . $magic . $salt . $password, true);
    $context = $password . $magic . $salt;

    for ($remaining = strlen($password); $remaining > 0; $remaining -= 16) {
        $context .= substr($alternate, 0, min(16, $remaining));
    }
    for ($remaining = strlen($password); $remaining > 0; $remaining >>= 1) {
        $context .= ($remaining & 1) !== 0 ? "\0" : $password[0];
    }

    $digest = md5($context, true);
    for ($iteration = 0; $iteration < 1000; $iteration++) {
        $context = ($iteration & 1) !== 0 ? $password : $digest;
        if ($iteration % 3 !== 0) {
            $context .= $salt;
        }
        if ($iteration % 7 !== 0) {
            $context .= $password;
        }
        $context .= ($iteration & 1) !== 0 ? $digest : $password;
        $digest = md5($context, true);
    }

    $encoded = '';
    $encoded .= apr1Encode($digest[0], $digest[6], $digest[12], 4);
    $encoded .= apr1Encode($digest[1], $digest[7], $digest[13], 4);
    $encoded .= apr1Encode($digest[2], $digest[8], $digest[14], 4);
    $encoded .= apr1Encode($digest[3], $digest[9], $digest[15], 4);
    $encoded .= apr1Encode($digest[4], $digest[10], $digest[5], 4);
    $encoded .= apr1Encode("\0", $digest[11], "\0", 2);
    return $magic . $salt . '$' . $encoded;
}

function apr1Encode(string $first, string $second, string $third, int $length): string
{
    $value = (ord($first) << 16) | (ord($second) << 8) | ord($third);
    $alphabet = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $encoded = '';
    for ($index = 0; $index < $length; $index++) {
        $encoded .= $alphabet[$value & 0x3f];
        $value >>= 6;
    }
    return $encoded;
}

function formatDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('d.m.Y H:i', $timestamp);
}