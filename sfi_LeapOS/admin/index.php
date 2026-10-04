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
  $error = t('login.error');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
  $_SESSION = [];
  session_destroy();
  header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?'));
  exit;
}

if (!$authenticated) {
  ?>
  <!DOCTYPE html>
  <html lang="<?= e(currentLanguage()) ?>">
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e(t('admin.login.title')) ?></title><link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>"></head>
  <body><main class="page-shell"><?= renderLanguageSelector(true) ?><section class="form-panel" style="max-width: 520px; margin: 10vh auto 0;"><p class="eyebrow"><?= e(t('admin.area')) ?></p><h1 style="font-size: 2.5rem;"><?= e(t('admin.link')) ?></h1><p class="intro"><?= e(t('admin.login.intro')) ?></p>
  <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="action" value="login"><label for="password"><?= e(t('field.password')) ?><input id="password" name="password" type="password" autocomplete="current-password" required autofocus></label><button class="primary-button" type="submit"><?= e(t('login.submit')) ?></button></form>
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
        $error = t('admin.selection.required');
      } else {
        $suggestions = array_values(array_filter($suggestions, static fn (array $item): bool => !in_array($item['id'] ?? '', $ids, true)));
        $changed = true;
        $notice = t('admin.notice.deleted');
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
          $error = t('admin.error.fields');
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
            $notice = t('admin.notice.edited');
        }
    } elseif ($action === 'bulk_status') {
        $status = cleanText($_POST['status'] ?? '', 30);
      if ($ids === [] || !in_array($status, STATUSES, true)) {
            $error = t('admin.error.status');
        } else {
            foreach ($suggestions as &$item) {
                if (in_array($item['id'] ?? '', $ids, true)) {
                    $item['status'] = $status;
                    $changed = true;
                }
            }
            unset($item);
            $notice = $changed ? t('admin.notice.status_changed') : t('admin.notice.none_selected');
        }
    }

    if ($changed && !saveSuggestions($suggestions)) {
        $error = t('admin.error.save');
        $notice = null;
    }
}

$suggestions = loadSuggestions();
$availableLeapOsVersions = availableLeapOsVersions($suggestions);
?>
<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('admin.title')) ?></title>
  <link rel="stylesheet" href="../style.css?v=<?= e(stylesheetVersion()) ?>">
  <style>
    .admin-panel { margin-bottom: 34px; padding: 22px; background: #e8f2ef; border: 1px solid #c6dfd9; }
    .page-title-nowrap { white-space: nowrap; font-size: clamp(2rem, 4.4vw, 4rem); }.admin-actions { display: grid; gap: 18px; }.edit-fields { display: grid; grid-template-columns: 1fr .75fr .9fr .9fr .9fr 2fr; gap: 14px; }.edit-fields label { min-width: 0; }.edit-suggestion { min-width: 0; }.edit-fields input, .edit-fields select, .edit-fields textarea { padding: 10px 12px; }.edit-fields textarea { min-height: 44px; resize: vertical; }.action-row { display: flex; align-items: end; gap: 10px; flex-wrap: wrap; }.action-row label { min-width: 180px; }.action-row .select-all { min-width: auto; flex-direction: row; align-items: center; gap: 8px; }.action-row .select-all input { width: 18px; height: 18px; }.action-row select { background: #fff; }.small-button { padding: 10px 14px; color: #fff; background: var(--teal); }.small-button:disabled { cursor: not-allowed; opacity: .45; }.danger-button { color: var(--red); border: 1px solid #e6b5b0; background: #fff; }.secondary-button { color: var(--ink); background: #dce4e5; }.selection-help { margin: 14px 0 0; color: var(--muted); font-size: .85rem; }.check-cell { text-align: center; }.check-cell input { min-width: auto; width: 18px; height: 18px; }.admin-table td { vertical-align: top; }
    @media (max-width: 800px) { .edit-fields { grid-template-columns: 1fr; }.edit-suggestion { grid-column: auto; } }
    @media (max-width: 700px) { .page-title-nowrap { font-size: 1.4rem; }.admin-table td { display: block; }.admin-table td::before { display: block; }.check-cell { display: block; }.action-row { align-items: stretch; flex-direction: column; }.action-row label, .action-row button { width: 100%; } }
  </style>
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div><p class="eyebrow"><?= e(t('admin.area')) ?></p><h1 class="page-title-nowrap"><?= e(t('admin.title')) ?></h1><p class="intro"><?= e(t('admin.intro')) ?></p></div>
      <div class="area-links"><a class="admin-link" href="../"><?= e(t('public.link')) ?></a><a class="admin-link" href="../export/"><?= e(t('export.link')) ?></a><?= renderLanguageSelector() ?></div>
    </header>
    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <section class="admin-panel">
      <form id="selection-form" class="admin-actions" method="post">
        <input id="selected-id" type="hidden" name="id" value="">
        <div class="edit-fields">
          <label for="edit-topic"><?= e(t('field.topic')) ?><input id="edit-topic" name="topic" type="text" maxlength="256" disabled></label>
          <label for="edit-model"><?= e(t('field.model')) ?><select id="edit-model" name="model" disabled><option value="<?= e(ALL_MODELS) ?>"><?= e(t('model.all')) ?></option><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"><?= e($model) ?></option><?php endforeach; ?></select></label>
          <div class="edit-version-fields">
            <label for="edit-leapos-version"><?= e(t('field.version')) ?><select id="edit-leapos-version" name="leapos_version" disabled><option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>"><?= e(t('version.all')) ?></option><?php foreach ($availableLeapOsVersions as $version): ?><option value="<?= e($version) ?>"><?= e($version) ?></option><?php endforeach; ?><option value="<?= e(ADD_LEAPOS_VERSION) ?>"><?= e(t('version.add')) ?></option></select></label>
            <label class="version-custom-field" id="edit-version-custom-field" for="edit-leapos-version-custom" hidden><span><?= e(t('version.new')) ?> <span>*</span></span><input id="edit-leapos-version-custom" name="leapos_version_custom" type="text" maxlength="30" pattern="[0-9]+(\.[0-9]+)*" disabled></label>
          </div>
          <label for="edit-category"><?= e(t('field.category')) ?><select id="edit-category" name="category" disabled><?php foreach (CATEGORIES as $category): ?><option value="<?= e($category) ?>"><?= e(t('value.' . $category)) ?></option><?php endforeach; ?></select></label>
          <label for="edit-classification"><?= e(t('field.classification')) ?><select id="edit-classification" name="classification" disabled><?php foreach (CLASSIFICATIONS as $classification): ?><option value="<?= e($classification) ?>"><?= e(t('value.' . $classification)) ?></option><?php endforeach; ?></select></label>
          <label class="edit-suggestion" for="edit-suggestion"><?= e(t('field.suggestion')) ?><textarea id="edit-suggestion" name="suggestion" rows="2" disabled></textarea></label>
        </div>
        <div class="action-row">
          <label class="select-all"><input id="select-all" type="checkbox"> <?= e(t('admin.select_all')) ?></label>
          <label for="bulk-status"><?= e(t('admin.field.status')) ?><select id="bulk-status" name="status"><option value=""><?= e(t('admin.status.prompt')) ?></option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e(t('value.' . $status)) ?></option><?php endforeach; ?></select></label>
          <button class="small-button" name="action" value="bulk_status" type="submit"><?= e(t('admin.status.submit')) ?></button>
          <button class="small-button" name="action" value="edit" type="submit" disabled id="save-button"><?= e(t('admin.save')) ?></button>
          <button class="small-button danger-button" name="action" value="delete" type="submit" onclick="return confirm(<?= e(json_encode(t('admin.delete.confirm'))) ?>);">
            <?= e(t('admin.delete')) ?></button>
          <button class="small-button secondary-button" name="action" value="logout" type="submit"><?= e(t('admin.logout')) ?></button>
        </div>
      </form>
      <p id="selection-help" class="selection-help"><?= e(t('admin.selection.none')) ?></p>
    </section>

    <section class="table-section admin-table-section">
      <div class="section-heading"><div><p class="eyebrow"><?= e(t('admin.title.count')) ?></p><h2><?= count($suggestions) ?> <?= e(t(count($suggestions) === 1 ? 'entry' : 'entries')) ?></h2></div></div>
      <div class="table-filters" data-table-filters aria-label="<?= e(t('overview')) ?>">
        <label class="table-filter-search"><?= e(t('filter.search')) ?><input type="search" data-filter-search placeholder="<?= e(t('filter.search.placeholder')) ?>"></label>
        <label><?= e(t('filter.from')) ?><input type="date" data-filter-from></label>
        <label><?= e(t('filter.to')) ?><input type="date" data-filter-to></label>
        <label><?= e(t('filter.status')) ?><select data-filter-status><option value=""><?= e(t('filter.all_statuses')) ?></option><?php foreach (STATUSES as $status): ?><option value="<?= e($status) ?>"><?= e(t('value.' . $status)) ?></option><?php endforeach; ?></select></label>
        <label><?= e(t('filter.category')) ?><select data-filter-category><option value=""><?= e(t('filter.all_categories')) ?></option><?php foreach (CATEGORIES as $category): ?><option value="<?= e($category) ?>"><?= e(t('value.' . $category)) ?></option><?php endforeach; ?><option value="__missing__"><?= e(t('filter.missing')) ?></option></select></label>
        <label><?= e(t('filter.classification')) ?><select data-filter-classification><option value=""><?= e(t('filter.all_classifications')) ?></option><?php foreach (CLASSIFICATIONS as $classification): ?><option value="<?= e($classification) ?>"><?= e(t('value.' . $classification)) ?></option><?php endforeach; ?><option value="__missing__"><?= e(t('filter.missing')) ?></option></select></label>
        <label><?= e(t('filter.model')) ?><select data-filter-model><option value=""><?= e(t('filter.all_models')) ?></option><option value="__missing__"><?= e(t('filter.missing')) ?></option><option value="<?= e(ALL_MODELS) ?>"><?= e(t('model.all')) ?></option><?php foreach (MODELS as $model): ?><option value="<?= e($model) ?>"><?= e($model) ?></option><?php endforeach; ?></select></label>
        <label><?= e(t('filter.version')) ?><select data-filter-version><option value=""><?= e(t('filter.all_versions')) ?></option><option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>"><?= e(t('version.all')) ?></option><option value="---">---</option><?php foreach ($availableLeapOsVersions as $version): ?><option value="<?= e($version) ?>"><?= e($version) ?></option><?php endforeach; ?></select></label>
        <button class="filter-reset" type="button" data-filter-reset><?= e(t('filter.reset')) ?></button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th><?= e(t('table.selection')) ?></th><th><?= e(t('table.number')) ?></th><th><?= e(t('table.created')) ?></th><th><?= e(t('filter.status')) ?></th><th><?= e(t('table.topic')) ?></th><th><?= e(t('table.model_version')) ?></th><th><?= e(t('table.suggestion')) ?></th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?><tr><td class="empty-state" colspan="7"><?= e(t('table.empty')) ?></td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr data-filter-row data-filter-date="<?= e(substr((string) ($item['created_at'] ?? ''), 0, 10)) ?>" data-filter-status="<?= e($item['status'] ?? 'erfasst') ?>" data-filter-category="<?= e($item['category'] ?? '') ?>" data-filter-classification="<?= e($item['classification'] ?? '') ?>" data-filter-model="<?= e($item['model'] ?? '') ?>" data-filter-version="<?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?>" data-filter-topic="<?= e($item['topic'] ?? '') ?>" data-filter-suggestion="<?= e($item['suggestion'] ?? '') ?>">
              <td class="check-cell"><input form="selection-form" type="checkbox" name="ids[]" value="<?= e($item['id'] ?? '') ?>" data-topic="<?= e($item['topic'] ?? '') ?>" data-model="<?= e($item['model'] ?? '') ?>" data-leapos-version="<?= e($item['leapos_version'] ?? UNKNOWN_LEAPOS_VERSION) ?>" data-category="<?= e($item['category'] ?? 'Sonstige') ?>" data-classification="<?= e($item['classification'] ?? 'Vorschlag') ?>" data-suggestion="<?= e($item['suggestion'] ?? '') ?>" data-status="<?= e($item['status'] ?? 'erfasst') ?>" aria-label="<?= e(t('table.selection')) ?>"></td>
              <td data-label="<?= e(t('table.number')) ?>"><?= e($item['number'] ?? '') ?></td>
              <td data-label="<?= e(t('table.created')) ?>"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Status"><div class="record-symbols"><span><?= renderSymbol('status', $item['status'] ?? 'erfasst') ?></span><span><?= renderSymbol('category', $item['category'] ?? 'Nicht angegeben') ?></span><span><?= renderSymbol('classification', $item['classification'] ?? 'Nicht angegeben') ?></span></div></td>
              <td data-label="<?= e(t('table.topic')) ?>" data-field="topic"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="<?= e(t('table.model_version')) ?>"><div class="model-version-tags"><span class="model-tag"><?= e(($item['model'] ?? '') === ALL_MODELS ? t('model.all') : ($item['model'] ?? '---')) ?></span><span class="model-tag"><?= e(displayLeapOsVersion($item['leapos_version'] ?? null) === UNKNOWN_LEAPOS_VERSION ? t('version.all') : displayLeapOsVersion($item['leapos_version'] ?? null)) ?></span></div></td>
              <td data-label="<?= e(t('table.suggestion')) ?>" data-field="suggestion" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          <tr data-filter-empty hidden><td class="empty-state" colspan="7"><?= e(t('table.no_matches')) ?></td></tr>
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
    const selectionMessages = {
      none: <?= json_encode(t('admin.selection.none'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
      single: <?= json_encode(t('admin.selection.single'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
      multiple: <?= json_encode(t('admin.selection.multiple'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
      required: <?= json_encode(t('admin.selection.required'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
      singleRequired: <?= json_encode(t('admin.selection.single_required'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    };
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
      selectionHelp.textContent = selected.length === 0 ? selectionMessages.none : (single ? selectionMessages.single : selected.length + ' ' + selectionMessages.multiple);
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
        selectionHelp.textContent = selectionMessages.required;
      }
      if (event.submitter?.value === 'edit' && selected.length !== 1) {
        event.preventDefault();
        selectionHelp.textContent = selectionMessages.singleRequired;
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
      const filterControls = [...panel.querySelectorAll('input, select')];
      const filterStorageKey = `sfi-leapos-admin-filters:${window.location.pathname}`;
      try {
        const savedFilters = JSON.parse(sessionStorage.getItem(filterStorageKey) ?? '[]');
        filterControls.forEach((control, index) => {
          if (typeof savedFilters[index] === 'string') control.value = savedFilters[index];
        });
      } catch {}
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
          highlightText(row.querySelector('[data-field="topic"]'), searchValue);
          highlightText(row.querySelector('[data-field="suggestion"]'), searchValue);
          row.hidden = !visible;
          const checkbox = row.querySelector('input[name="ids[]"]');
          if (!visible && checkbox) checkbox.checked = false;
          if (visible) visibleCount++;
        });
        emptyRow.hidden = visibleCount > 0 || rows.length === 0;
        updateSelection();
      }

      panel.querySelectorAll('input, select').forEach((control) => {
        const updateFilters = () => {
          applyFilters();
          try {
            sessionStorage.setItem(filterStorageKey, JSON.stringify(filterControls.map((filterControl) => filterControl.value)));
          } catch {}
        };
        control.addEventListener('input', updateFilters);
        control.addEventListener('change', updateFilters);
      });
      panel.querySelector('[data-filter-reset]').addEventListener('click', () => {
        panel.querySelectorAll('input, select').forEach((control) => { control.value = ''; });
        applyFilters();
        try {
          sessionStorage.removeItem(filterStorageKey);
        } catch {}
      });
      applyFilters();
    });
  </script>
</body>
</html>