<?php
declare(strict_types=1);
session_start();
require dirname(__DIR__) . '/lib.php';

$error = null;
$notice = null;
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = preg_match('/^(localhost|127\.0\.0\.1)(:[0-9]+)?$/', $host) === 1;
$passwordFile = $isLocalHost
  ? '/Applications/MAMP/access/sfi_LeapOS/.htpasswd'
  : '/var/www/vhosts/h331132.web114.alfahosting-server.de/access/sfi_LeapOS/.htpasswd';
$authenticated = ($_SESSION['suggestions_admin_authenticated'] ?? false) === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
  $validPassword = false;
  if ($password !== '' && is_readable($passwordFile)) {
    foreach (file($passwordFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
      $line = trim($line);
      $parts = explode(':', $line, 2);
      $hash = count($parts) === 2 ? trim($parts[1]) : $line;
      if (password_verify($password, $hash)) {
        $validPassword = true;
        break;
      }
    }
  }
  if ($validPassword) {
    session_regenerate_id(true);
    $_SESSION['suggestions_admin_authenticated'] = true;
    header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?'));
    exit;
  }
  $error = 'Das Passwort ist nicht korrekt.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
  $_SESSION = [];
  session_destroy();
  header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?'));
  exit;
}

if (!$authenticated) {
  ?>
  <!DOCTYPE html>
  <html lang="de">
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin-Anmeldung</title><link rel="stylesheet" href="../style.css"></head>
  <body><main class="page-shell"><section class="form-panel" style="max-width: 520px; margin: 10vh auto 0;"><p class="eyebrow">Geschützter Bereich</p><h1 style="font-size: 2.5rem;">Bearbeitung</h1><p class="intro">Bitte Passwort eingeben, um die Vorschläge zu bearbeiten.</p>
  <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="action" value="login"><label for="password">Passwort<input id="password" name="password" type="password" autocomplete="current-password" required autofocus></label><button class="primary-button" type="submit">Anmelden</button></form>
  </section></main></body></html>
  <?php
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $suggestions = loadSuggestions();
    $changed = false;

    if ($action === 'delete') {
        $id = cleanText($_POST['id'] ?? '', 32);
        $suggestions = array_values(array_filter($suggestions, static fn (array $item): bool => ($item['id'] ?? '') !== $id));
        $changed = true;
        $notice = 'Der Eintrag wurde gelöscht.';
    } elseif ($action === 'edit') {
        $id = cleanText($_POST['id'] ?? '', 32);
        $topic = cleanText($_POST['topic'] ?? '', 256);
        $model = cleanText($_POST['model'] ?? '', 20);
        $suggestion = cleanText($_POST['suggestion'] ?? '', 10000);
        $status = cleanText($_POST['status'] ?? '', 30);
        if ($topic === '' || !in_array($model, MODELS, true) || $suggestion === '' || !in_array($status, STATUSES, true)) {
            $error = 'Bitte Thema, Modell, Vorschlag und einen gültigen Status angeben.';
        } else {
            foreach ($suggestions as &$item) {
                if (($item['id'] ?? '') === $id) {
                    $item['topic'] = $topic;
                    $item['model'] = $model;
                    $item['suggestion'] = $suggestion;
                    $item['status'] = $status;
                    $changed = true;
                    break;
                }
            }
            unset($item);
            $notice = 'Der Eintrag wurde geändert.';
        }
    } elseif ($action === 'bulk_status') {
        $status = cleanText($_POST['status'] ?? '', 30);
        $ids = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
        if (!in_array($status, STATUSES, true)) {
            $error = 'Bitte einen gültigen Status auswählen.';
        } else {
            foreach ($suggestions as &$item) {
                if (in_array($item['id'] ?? '', $ids, true)) {
                    $item['status'] = $status;
                    $changed = true;
                }
            }
            unset($item);
            $notice = $changed ? 'Die ausgewählten Status wurden geändert.' : 'Keine Einträge ausgewählt.';
        }
    }

    if ($changed && !saveSuggestions($suggestions)) {
        $error = 'Die Änderungen konnten nicht gespeichert werden. Bitte die Schreibrechte prüfen.';
        $notice = null;
    }
}

$suggestions = loadSuggestions();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vorschläge bearbeiten</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .admin-panel { margin-bottom: 34px; padding: 22px; background: #e8f2ef; border: 1px solid #c6dfd9; }
    .bulk-form { display: flex; align-items: end; gap: 14px; flex-wrap: wrap; }
    .bulk-form label { min-width: 220px; }.bulk-form select { background: #fff; }.small-button { padding: 10px 14px; color: #fff; background: var(--teal); }.danger-button { color: var(--red); border: 1px solid #e6b5b0; background: #fff; }.row-form input, .row-form select, .row-form textarea { min-width: 120px; padding: 9px; font-size: .88rem; }.row-form textarea { min-width: 250px; min-height: 70px; }.row-form { display: grid; gap: 8px; }.row-actions { display: flex; gap: 8px; flex-wrap: wrap; }.check-cell { text-align: center; }.check-cell input { min-width: auto; width: 18px; height: 18px; }.admin-table td { vertical-align: middle; }
    @media (max-width: 700px) { .admin-table td { display: block; }.admin-table td::before { display: none; }.row-form textarea { min-width: 100%; } }
  </style>
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div><p class="eyebrow">Geschützter Bereich</p><h1>Vorschläge bearbeiten</h1><p class="intro">Einträge prüfen, weiterleiten und ihren Status aktuell halten.</p></div>
      <a class="admin-link" href="../">Zur öffentlichen Ansicht</a>
    </header>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <section class="admin-panel">
      <form id="bulk-form" class="bulk-form" method="post">
        <input type="hidden" name="action" value="bulk_status">
        <label for="bulk-status">Status für ausgewählte Zeilen
          <select id="bulk-status" name="status" required><option value="">Bitte auswählen</option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select>
        </label>
        <button class="small-button" type="submit">Status ändern</button>
      </form>
    </section>

    <section class="table-section">
      <div class="section-heading"><div><p class="eyebrow">Verwaltung</p><h2><?= count($suggestions) ?> Einträge</h2></div></div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Auswahl</th><th>Erfasst am</th><th>Bearbeiten</th><th>Aktionen</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?><tr><td class="empty-state" colspan="4">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr>
              <td class="check-cell"><input form="bulk-form" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" aria-label="Eintrag auswählen"></td>
              <td><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td>
                <form class="row-form" method="post">
                  <input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>">
                  <input name="topic" maxlength="256" value="<?= e($item['topic'] ?? '') ?>" aria-label="Thema" required>
                  <select name="model" aria-label="Modell" required><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"<?= ($item['model'] ?? '') === $model ? ' selected' : '' ?>><?= e($model) ?></option><?php endforeach; ?></select>
                  <textarea name="suggestion" aria-label="Vorschlag" required><?= e($item['suggestion'] ?? '') ?></textarea>
                  <select name="status" aria-label="Status" required><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"<?= ($item['status'] ?? '') === $status ? ' selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
                  <button class="small-button" type="submit">Änderung speichern</button>
                </form>
              </td>
              <td><form method="post" onsubmit="return confirm('Diesen Eintrag wirklich löschen?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($item['id'] ?? '') ?>"><button class="small-button danger-button" type="submit">Löschen</button></form></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>