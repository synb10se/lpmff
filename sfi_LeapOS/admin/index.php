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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reauth'])) {
  unset($_SESSION['suggestions_admin_authenticated']);
}
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

    $ids = is_array($_POST['ids'] ?? null) ? array_map(static fn (mixed $id): string => cleanText($id, 32), $_POST['ids']) : [];

    if ($action === 'delete') {
      if ($ids === []) {
        $error = 'Bitte mindestens eine Zeile auswählen.';
      } else {
        $suggestions = array_values(array_filter($suggestions, static fn (array $item): bool => !in_array($item['id'] ?? '', $ids, true)));
        $changed = true;
        $notice = 'Die ausgewählten Einträge wurden gelöscht.';
      }
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
                  $item['status'] = ($item['status'] ?? 'erfasst') === 'erfasst' && $status === 'erfasst'
                    ? 'geprüft'
                    : $status;
                    $changed = true;
                    break;
                }
            }
            unset($item);
            $notice = 'Der Eintrag wurde geändert.';
        }
    } elseif ($action === 'bulk_status') {
        $status = cleanText($_POST['status'] ?? '', 30);
      if ($ids === [] || !in_array($status, STATUSES, true)) {
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
    .admin-actions { display: grid; gap: 18px; }.edit-fields { display: grid; grid-template-columns: 1.1fr .7fr 2fr; gap: 14px; }.edit-fields label { min-width: 0; }.edit-suggestion { min-width: 0; }.edit-fields input, .edit-fields select, .edit-fields textarea { padding: 10px 12px; }.edit-fields textarea { min-height: 44px; resize: vertical; }.action-row { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; }.action-row label { min-width: 180px; }.action-row select { background: #fff; }.small-button { padding: 10px 14px; color: #fff; background: var(--teal); }.small-button:disabled { cursor: not-allowed; opacity: .45; }.danger-button { color: var(--red); border: 1px solid #e6b5b0; background: #fff; }.secondary-button { color: var(--ink); background: #dce4e5; }.selection-help { margin: 14px 0 0; color: var(--muted); font-size: .85rem; }.check-cell { text-align: center; }.check-cell input { min-width: auto; width: 18px; height: 18px; }.admin-table td { vertical-align: top; }
    @media (max-width: 800px) { .edit-fields { grid-template-columns: 1fr; }.edit-suggestion { grid-column: auto; } }
    @media (max-width: 700px) { .admin-table td { display: block; }.admin-table td::before { display: block; }.check-cell { display: block; }.action-row { align-items: stretch; flex-direction: column; }.action-row label, .action-row button { width: 100%; } }
  </style>
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div><p class="eyebrow">Geschützter Bereich</p><h1>Vorschläge bearbeiten</h1><p class="intro">Einträge prüfen, weiterleiten und ihren Status aktuell halten.</p></div>
      <div><a class="admin-link" href="../">Zur öffentlichen Ansicht</a><a class="admin-link" href="../export/">Export</a></div>
    </header>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <section class="admin-panel">
      <form id="selection-form" class="admin-actions" method="post">
        <input id="selected-id" type="hidden" name="id" value="">
        <div class="edit-fields">
          <label for="edit-topic">Thema<input id="edit-topic" name="topic" type="text" maxlength="256" disabled></label>
          <label for="edit-model">Modell<select id="edit-model" name="model" disabled><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"><?= e($model) ?></option><?php endforeach; ?></select></label>
          <label class="edit-suggestion" for="edit-suggestion">Vorschlag<textarea id="edit-suggestion" name="suggestion" rows="2" disabled></textarea></label>
        </div>
        <div class="action-row">
          <label for="bulk-status">Status ändern<select id="bulk-status" name="status"><option value="">Bitte auswählen</option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></label>
          <button class="small-button" name="action" value="bulk_status" type="submit">Status ändern</button>
          <button class="small-button" name="action" value="edit" type="submit" disabled id="save-button">Änderungen speichern</button>
          <button class="small-button danger-button" name="action" value="delete" type="submit" onclick="return confirm('Die ausgewählten Einträge wirklich löschen?');">Löschen</button>
          <button class="small-button secondary-button" name="action" value="logout" type="submit">Abmelden</button>
        </div>
      </form>
      <p id="selection-help" class="selection-help">Bitte eine Zeile auswählen.</p>
    </section>

    <section class="table-section">
      <div class="section-heading"><div><p class="eyebrow">Verwaltung</p><h2><?= count($suggestions) ?> Einträge</h2></div></div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Auswahl</th><th>Erfasst am</th><th>Thema</th><th>Modell</th><th>Vorschlag</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?><tr><td class="empty-state" colspan="6">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr>
              <td class="check-cell"><input form="selection-form" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" data-topic="<?= e($item['topic'] ?? '') ?>" data-model="<?= e($item['model'] ?? '') ?>" data-suggestion="<?= e($item['suggestion'] ?? '') ?>" data-status="<?= e($item['status'] ?? 'erfasst') ?>" aria-label="Eintrag auswählen"></td>
              <td data-label="Erfasst am"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Thema"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="Modell"><span class="model-tag"><?= e($item['model'] ?? '') ?></span></td>
              <td data-label="Vorschlag" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
              <td data-label="Status"><span class="status status-<?= e($item['status'] ?? 'erfasst') ?>"><?= e($item['status'] ?? 'erfasst') ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <script>
    const selectionForm = document.getElementById('selection-form');
    const checkboxes = [...document.querySelectorAll('input[name="ids[]"]')];
    const topic = document.getElementById('edit-topic');
    const model = document.getElementById('edit-model');
    const suggestion = document.getElementById('edit-suggestion');
    const selectedId = document.getElementById('selected-id');
    const saveButton = document.getElementById('save-button');
    const selectionHelp = document.getElementById('selection-help');
    function updateSelection() {
      const selected = checkboxes.filter((checkbox) => checkbox.checked);
      const single = selected.length === 1 ? selected[0] : null;
      selectedId.value = single ? single.value : '';
      topic.value = single ? single.dataset.topic : '';
      model.value = single ? single.dataset.model : '<?= e(MODELS[0]) ?>';
      suggestion.value = single ? single.dataset.suggestion : '';
      document.getElementById('bulk-status').value = single ? single.dataset.status : '';
      topic.disabled = !single;
      model.disabled = !single;
      suggestion.disabled = !single;
      saveButton.disabled = !single;
      selectionHelp.textContent = selected.length === 0 ? 'Bitte eine Zeile auswählen.' : (single ? 'Eine Zeile ausgewählt: Felder können bearbeitet werden.' : selected.length + ' Zeilen ausgewählt: Status ändern oder löschen ist möglich.');
    }
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    selectionForm.addEventListener('submit', (event) => {
      const selected = checkboxes.filter((checkbox) => checkbox.checked);
      if (selected.length === 0 && event.submitter?.value !== 'logout') {
        event.preventDefault();
        selectionHelp.textContent = 'Bitte mindestens eine Zeile auswählen.';
      }
      if (event.submitter?.value === 'edit' && selected.length !== 1) {
        event.preventDefault();
        selectionHelp.textContent = 'Zum Speichern genau eine Zeile auswählen.';
      }
    });
  </script>
</body>
</html>