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
      if (verifyHtpasswdPassword($password, $hash)) {
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
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin-Anmeldung</title><link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>"></head>
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
        $leaposVersion = resolveLeapOsVersion($_POST['leapos_version'] ?? '', $_POST['leapos_version_custom'] ?? '');
        $category = cleanText($_POST['category'] ?? '', 40);
        $classification = cleanText($_POST['classification'] ?? '', 40);
        $suggestion = cleanText($_POST['suggestion'] ?? '', 10000);
        $status = cleanText($_POST['status'] ?? '', 30);
        if ($topic === '' || !isValidModel($model) || !isValidLeapOsVersion($leaposVersion) || !in_array($category, CATEGORIES, true) || !in_array($classification, CLASSIFICATIONS, true) || $suggestion === '' || !in_array($status, STATUSES, true)) {
          $error = 'Bitte alle Felder mit gültigen Werten ausfüllen.';
        } else {
            foreach ($suggestions as &$item) {
                if (($item['id'] ?? '') === $id) {
                    $item['topic'] = $topic;
                    $item['model'] = $model;
                    $item['leapos_version'] = $leaposVersion;
                    $item['category'] = $category;
                    $item['classification'] = $classification;
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
$availableLeapOsVersions = availableLeapOsVersions($suggestions);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vorschläge bearbeiten</title>
  <link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>">
  <style>
    .admin-panel { margin-bottom: 34px; padding: 22px; background: #e8f2ef; border: 1px solid #c6dfd9; }
    .page-title-nowrap { white-space: nowrap; font-size: clamp(2rem, 4.4vw, 4rem); }.admin-actions { display: grid; gap: 18px; }.edit-fields { display: grid; grid-template-columns: 1fr .75fr .9fr .9fr .9fr 2fr; gap: 14px; }.edit-fields label { min-width: 0; }.edit-suggestion { min-width: 0; }.edit-fields input, .edit-fields select, .edit-fields textarea { padding: 10px 12px; }.edit-fields textarea { min-height: 44px; resize: vertical; }.action-row { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; }.action-row label { min-width: 180px; }.action-row .select-all { min-width: auto; flex-direction: row; align-items: center; gap: 8px; }.action-row .select-all input { width: 18px; height: 18px; }.action-row select { background: #fff; }.small-button { padding: 10px 14px; color: #fff; background: var(--teal); }.small-button:disabled { cursor: not-allowed; opacity: .45; }.danger-button { color: var(--red); border: 1px solid #e6b5b0; background: #fff; }.secondary-button { color: var(--ink); background: #dce4e5; }.selection-help { margin: 14px 0 0; color: var(--muted); font-size: .85rem; }.check-cell { text-align: center; }.check-cell input { min-width: auto; width: 18px; height: 18px; }.admin-table td { vertical-align: top; }
    @media (max-width: 800px) { .edit-fields { grid-template-columns: 1fr; }.edit-suggestion { grid-column: auto; } }
    @media (max-width: 700px) { .admin-table td { display: block; }.admin-table td::before { display: block; }.check-cell { display: block; }.action-row { align-items: stretch; flex-direction: column; }.action-row label, .action-row button { width: 100%; } }
  </style>
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div><p class="eyebrow">Geschützter Bereich</p><h1 class="page-title-nowrap">Vorschläge bearbeiten</h1><p class="intro">Einträge prüfen, weiterleiten und ihren Status aktuell halten.</p></div>
      <div class="area-links"><a class="admin-link" href="../">Zur öffentlichen Ansicht</a><a class="admin-link" href="../export/">Export</a></div>
    </header>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <section class="admin-panel">
      <form id="selection-form" class="admin-actions" method="post">
        <input id="selected-id" type="hidden" name="id" value="">
        <div class="edit-fields">
          <label for="edit-topic">Thema<input id="edit-topic" name="topic" type="text" maxlength="256" disabled></label>
          <label for="edit-model">Modell<select id="edit-model" name="model" disabled><option value="<?= e(ALL_MODELS) ?>"><?= e(ALL_MODELS) ?></option><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"><?= e($model) ?></option><?php endforeach; ?></select></label>
          <div class="edit-version-fields">
            <label for="edit-leapos-version">LeapOS-Version<select id="edit-leapos-version" name="leapos_version" disabled><option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>">alle</option><?php foreach ($availableLeapOsVersions as $version): ?><option value="<?= e($version) ?>"><?= e($version) ?></option><?php endforeach; ?><option value="<?= e(ADD_LEAPOS_VERSION) ?>">Version hinzufügen …</option></select></label>
            <label class="version-custom-field" id="edit-version-custom-field" for="edit-leapos-version-custom" hidden><span>Neue Versionsnummer <span>*</span></span><input id="edit-leapos-version-custom" name="leapos_version_custom" type="text" maxlength="30" pattern="[0-9]+(\.[0-9]+)*" disabled></label>
          </div>
          <label for="edit-category">Kategorie<select id="edit-category" name="category" disabled><?php foreach (CATEGORIES as $category): ?><option value="<?= e($category) ?>"><?= e($category) ?></option><?php endforeach; ?></select></label>
          <label for="edit-classification">Einordnung<select id="edit-classification" name="classification" disabled><?php foreach (CLASSIFICATIONS as $classification): ?><option value="<?= e($classification) ?>"><?= e($classification) ?></option><?php endforeach; ?></select></label>
          <label class="edit-suggestion" for="edit-suggestion">Vorschlag<textarea id="edit-suggestion" name="suggestion" rows="2" disabled></textarea></label>
        </div>
        <div class="action-row">
          <label class="select-all"><input id="select-all" type="checkbox"> Alle auswählen</label>
          <label for="bulk-status">Status ändern<select id="bulk-status" name="status"><option value="">Bitte auswählen</option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></label>
          <button class="small-button" name="action" value="bulk_status" type="submit">Status ändern</button>
          <button class="small-button" name="action" value="edit" type="submit" disabled id="save-button">Änderungen speichern</button>
          <button class="small-button danger-button" name="action" value="delete" type="submit" onclick="return confirm('Die ausgewählten Einträge wirklich löschen?');">Löschen</button>
          <button class="small-button secondary-button" name="action" value="logout" type="submit">Abmelden</button>
        </div>
      </form>
      <p id="selection-help" class="selection-help">Bitte eine Zeile auswählen.</p>
    </section>

    <section class="table-section admin-table-section">
      <div class="section-heading"><div><p class="eyebrow">Verwaltung</p><h2><?= count($suggestions) ?> Einträge</h2></div></div>
      <div class="table-filters" data-table-filters aria-label="Tabellenfilter">
        <label class="table-filter-search">Freitext<input type="search" data-filter-search placeholder="Thema oder Vorschlag"></label>
        <label>Von<input type="date" data-filter-from></label>
        <label>Bis<input type="date" data-filter-to></label>
        <label>Status<select data-filter-status><option value="">Alle Status</option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></label>
        <label>Kategorie<select data-filter-category><option value="">Alle Kategorien</option><?php foreach (CATEGORIES as $category): ?><option value="<?= e($category) ?>"><?= e($category) ?></option><?php endforeach; ?><option value="__missing__">Nicht angegeben</option></select></label>
        <label>Einordnung<select data-filter-classification><option value="">Alle Einordnungen</option><?php foreach (CLASSIFICATIONS as $classification): ?><option value="<?= e($classification) ?>"><?= e($classification) ?></option><?php endforeach; ?><option value="__missing__">Nicht angegeben</option></select></label>
        <label>Modell<select data-filter-model><option value="">Alle Modelle</option><option value="__missing__">Nicht angegeben</option><option value="<?= e(ALL_MODELS) ?>"><?= e(ALL_MODELS) ?></option><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"><?= e($model) ?></option><?php endforeach; ?></select></label>
        <label>Version<select data-filter-version><option value="">Alle Versionen</option><option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>">alle</option><option value="---">---</option><?php foreach ($availableLeapOsVersions as $version): ?><option value="<?= e($version) ?>"><?= e($version) ?></option><?php endforeach; ?></select></label>
        <button class="filter-reset" type="button" data-filter-reset>Zurücksetzen</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Auswahl</th><th>Nr.</th><th>Erfasst am</th><th>Status</th><th>Thema</th><th>Modell / LeapOS</th><th>Vorschlag</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?><tr><td class="empty-state" colspan="7">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr data-filter-row data-filter-date="<?= e(substr((string) ($item['created_at'] ?? ''), 0, 10)) ?>" data-filter-status="<?= e($item['status'] ?? 'erfasst') ?>" data-filter-category="<?= e($item['category'] ?? '') ?>" data-filter-classification="<?= e($item['classification'] ?? '') ?>" data-filter-model="<?= e($item['model'] ?? '') ?>" data-filter-version="<?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?>" data-filter-topic="<?= e($item['topic'] ?? '') ?>" data-filter-suggestion="<?= e($item['suggestion'] ?? '') ?>">
              <td class="check-cell"><input form="selection-form" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" data-topic="<?= e($item['topic'] ?? '') ?>" data-model="<?= e($item['model'] ?? '') ?>" data-leapos-version="<?= e($item['leapos_version'] ?? UNKNOWN_LEAPOS_VERSION) ?>" data-category="<?= e($item['category'] ?? 'Sonstige') ?>" data-classification="<?= e($item['classification'] ?? 'Vorschlag') ?>" data-suggestion="<?= e($item['suggestion'] ?? '') ?>" data-status="<?= e($item['status'] ?? 'erfasst') ?>" aria-label="Eintrag auswählen"></td>
              <td data-label="Nr."><?= e($item['number'] ?? '') ?></td>
              <td data-label="Erfasst am"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Status"><div class="record-symbols"><span><?= renderSymbol('status', $item['status'] ?? 'erfasst') ?></span><span><?= renderSymbol('category', $item['category'] ?? 'Nicht angegeben') ?></span><span><?= renderSymbol('classification', $item['classification'] ?? 'Nicht angegeben') ?></span></div></td>
              <td data-label="Thema"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="Modell / LeapOS"><div class="model-version-tags"><span class="model-tag"><?= e($item['model'] ?? '---') ?></span><span class="model-tag"><?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?></span></div></td>
              <td data-label="Vorschlag" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          <tr data-filter-empty hidden><td class="empty-state" colspan="7">Keine passenden Einträge gefunden.</td></tr>
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
    const leaposVersion = document.getElementById('edit-leapos-version');
    const leaposVersionCustomField = document.getElementById('edit-version-custom-field');
    const leaposVersionCustom = document.getElementById('edit-leapos-version-custom');
    const category = document.getElementById('edit-category');
    const classification = document.getElementById('edit-classification');
    const suggestion = document.getElementById('edit-suggestion');
    const selectedId = document.getElementById('selected-id');
    const saveButton = document.getElementById('save-button');
    const selectionHelp = document.getElementById('selection-help');
    const selectAll = document.getElementById('select-all');
    function updateSelection() {
      const selected = checkboxes.filter((checkbox) => checkbox.checked);
      const single = selected.length === 1 ? selected[0] : null;
      const visibleCheckboxes = checkboxes.filter((checkbox) => !checkbox.closest('tr').hidden);
      const visibleSelectedCount = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;
      selectedId.value = single ? single.value : '';
      topic.value = single ? single.dataset.topic : '';
      model.value = single ? single.dataset.model : '<?= e(ALL_MODELS) ?>';
      const savedVersion = single ? single.dataset.leaposVersion : '<?= e(UNKNOWN_LEAPOS_VERSION) ?>';
      const normalizedVersion = savedVersion === '<?= e(LEGACY_UNKNOWN_LEAPOS_VERSION) ?>' ? '<?= e(UNKNOWN_LEAPOS_VERSION) ?>' : savedVersion;
      const isCustomVersion = single && ![...leaposVersion.options].some((option) => option.value === normalizedVersion);
      leaposVersion.value = isCustomVersion ? '<?= e(ADD_LEAPOS_VERSION) ?>' : normalizedVersion;
      leaposVersionCustom.value = isCustomVersion ? savedVersion : '';
      leaposVersionCustomField.hidden = !isCustomVersion;
      leaposVersionCustom.required = Boolean(isCustomVersion);
      category.value = single ? single.dataset.category : 'Sonstige';
      classification.value = single ? single.dataset.classification : 'Vorschlag';
      suggestion.value = single ? single.dataset.suggestion : '';
      document.getElementById('bulk-status').value = single ? single.dataset.status : '';
      topic.disabled = !single;
      model.disabled = !single;
      leaposVersion.disabled = !single;
      leaposVersionCustom.disabled = !single || !isCustomVersion;
      category.disabled = !single;
      classification.disabled = !single;
      suggestion.disabled = !single;
      saveButton.disabled = !single;
      selectAll.checked = visibleCheckboxes.length > 0 && visibleSelectedCount === visibleCheckboxes.length;
      selectAll.indeterminate = visibleSelectedCount > 0 && visibleSelectedCount < visibleCheckboxes.length;
      selectionHelp.textContent = selected.length === 0 ? 'Bitte eine Zeile auswählen.' : (single ? 'Eine Zeile ausgewählt: Felder können bearbeitet werden.' : selected.length + ' Zeilen ausgewählt: Status ändern oder löschen ist möglich.');
    }
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    leaposVersion.addEventListener('change', () => {
      const addingVersion = leaposVersion.value === '<?= e(ADD_LEAPOS_VERSION) ?>';
      leaposVersionCustomField.hidden = !addingVersion;
      leaposVersionCustom.disabled = !addingVersion;
      leaposVersionCustom.required = addingVersion;
      if (!addingVersion) {
        leaposVersionCustom.value = '';
      }
    });
    selectAll.addEventListener('change', () => {
      checkboxes.filter((checkbox) => !checkbox.closest('tr').hidden).forEach((checkbox) => { checkbox.checked = selectAll.checked; });
      updateSelection();
    });
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
    document.querySelectorAll('[data-table-filters]').forEach((panel) => {
      const section = panel.closest('.table-section');
      const rows = [...section.querySelectorAll('tbody tr[data-filter-row]')];
      const emptyRow = section.querySelector('[data-filter-empty]');
      const field = (name) => panel.querySelector(`[data-filter-${name}]`);
      const dateFrom = field('from');
      const dateTo = field('to');
      const search = field('search');
      const selectFields = ['status', 'category', 'classification', 'model', 'version'].map((name) => ({
        control: field(name),
        dataKey: `filter${name[0].toUpperCase()}${name.slice(1)}`,
      }));

      function matchesFilter(filterValue, rowValue) {
        if (filterValue === '') return true;
        if (filterValue === '__missing__') return rowValue === '';
        return filterValue === rowValue;
      }

      function highlightText(element, searchValue) {
        element.querySelectorAll('mark.search-highlight').forEach((mark) => {
          mark.replaceWith(document.createTextNode(mark.textContent ?? ''));
        });
        element.normalize();
        if (searchValue === '') return;

        const escapedSearch = searchValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const textNodes = [];
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) textNodes.push(walker.currentNode);

        textNodes.forEach((textNode) => {
          const text = textNode.nodeValue ?? '';
          const matcher = new RegExp(escapedSearch, 'giu');
          const fragment = document.createDocumentFragment();
          let lastIndex = 0;
          let match;
          while ((match = matcher.exec(text)) !== null) {
            fragment.append(document.createTextNode(text.slice(lastIndex, match.index)));
            const mark = document.createElement('mark');
            mark.className = 'search-highlight';
            mark.textContent = match[0];
            fragment.append(mark);
            lastIndex = matcher.lastIndex;
          }
          if (lastIndex > 0) {
            fragment.append(document.createTextNode(text.slice(lastIndex)));
            textNode.replaceWith(fragment);
          }
        });
      }

      function applyFilters() {
        const searchValue = search.value.trim().toLocaleLowerCase('de');
        let visibleCount = 0;
        rows.forEach((row) => {
          const rowDate = row.dataset.filterDate;
          const rowText = `${row.dataset.filterTopic} ${row.dataset.filterSuggestion}`.toLocaleLowerCase('de');
          const matchesDate = (!dateFrom.value || rowDate >= dateFrom.value) && (!dateTo.value || rowDate <= dateTo.value);
          const matchesSelects = selectFields.every(({ control, dataKey }) => matchesFilter(control.value, row.dataset[dataKey]));
          const visible = matchesDate && matchesSelects && (!searchValue || rowText.includes(searchValue));
          highlightText(row.querySelector('[data-label="Thema"]'), searchValue);
          highlightText(row.querySelector('[data-label="Vorschlag"]'), searchValue);
          row.hidden = !visible;
          const checkbox = row.querySelector('input[name="ids[]"]');
          if (!visible && checkbox) checkbox.checked = false;
          if (visible) visibleCount++;
        });
        emptyRow.hidden = visibleCount > 0 || rows.length === 0;
        updateSelection();
      }

      panel.querySelectorAll('input, select').forEach((control) => {
        control.addEventListener('input', applyFilters);
        control.addEventListener('change', applyFilters);
      });
      panel.querySelector('[data-filter-reset]').addEventListener('click', () => {
        panel.querySelectorAll('input, select').forEach((control) => { control.value = ''; });
        applyFilters();
      });
      applyFilters();
    });
  </script>
</body>
</html>