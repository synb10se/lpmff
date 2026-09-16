<?php
declare(strict_types=1);

const SUGGESTION_FILE = __DIR__ . '/data/suggestions.json';
const MODELS = ['B03X', 'B05', 'B10', 'C10', 'T03'];
const STATUSES = ['erfasst', 'versendet', 'abgelehnt', 'bestätigt', 'angekündigt', 'verfügbar'];

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

function formatDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('d.m.Y H:i', $timestamp);
}