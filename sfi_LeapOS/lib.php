<?php
declare(strict_types=1);

const SUGGESTION_FILE = __DIR__ . '/data/suggestions.json';
const MODELS = ['B03X', 'B05', 'B10', 'C10', 'T03'];
const ALL_MODELS = 'alle';
const STATUSES = ['erfasst', 'geprüft', 'versendet', 'abgelehnt', 'bestätigt', 'angekündigt', 'verfügbar'];

function isValidModel(string $model): bool
{
    return $model === ALL_MODELS || in_array($model, MODELS, true);
}

function loadSuggestions(): array
{
    if (!is_readable(SUGGESTION_FILE)) {
        return [];
    }

    $contents = file_get_contents(SUGGESTION_FILE);
    $suggestions = $contents === false ? null : json_decode($contents, true);
    return is_array($suggestions) ? $suggestions : [];
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