<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$error = null;
$notice = null;
$oldInput = ['topic' => '', 'model' => ALL_MODELS, 'leapos_version' => UNKNOWN_LEAPOS_VERSION, 'category' => '', 'classification' => '', 'suggestion' => ''];
$isAddingLeapOsVersion = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['saved'])) {
  $notice = t('notice.submitted');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldInput = [
        'topic' => cleanText($_POST['topic'] ?? '', 256),
        'model' => cleanText($_POST['model'] ?? '', 20),
        'leapos_version' => resolveLeapOsVersion($_POST['leapos_version'] ?? '', $_POST['leapos_version_custom'] ?? ''),
        'category' => cleanText($_POST['category'] ?? '', 40),
        'classification' => cleanText($_POST['classification'] ?? '', 40),
        'suggestion' => cleanText($_POST['suggestion'] ?? '', 10000),
    ];
      $isAddingLeapOsVersion = cleanText($_POST['leapos_version'] ?? '', 30) === ADD_LEAPOS_VERSION;

    if ($oldInput['topic'] === '' || mb_strlen($oldInput['topic']) > 256) {
      $error = t('error.topic');
      } elseif (!isValidModel($oldInput['model'])) {
      $error = t('error.model');
    } elseif (!isValidLeapOsVersion($oldInput['leapos_version'])) {
      $error = t('error.version');
    } elseif (!in_array($oldInput['category'], CATEGORIES, true)) {
      $error = t('error.category');
    } elseif (!in_array($oldInput['classification'], CLASSIFICATIONS, true)) {
      $error = t('error.classification');
    } elseif ($oldInput['suggestion'] === '') {
      $error = t('error.suggestion');
    } else {
        $suggestions = loadSuggestions();
        $suggestions[] = [
            'id' => bin2hex(random_bytes(8)),
          'number' => nextSuggestionNumber($suggestions),
            'created_at' => date(DATE_ATOM),
            'topic' => $oldInput['topic'],
            'model' => $oldInput['model'],
            'leapos_version' => $oldInput['leapos_version'],
            'category' => $oldInput['category'],
            'classification' => $oldInput['classification'],
            'suggestion' => $oldInput['suggestion'],
            'status' => 'erfasst',
        ];

        if (saveSuggestions($suggestions)) {
          header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1', true, 303);
          exit;
        } else {
            $error = t('error.submit');
        }
    }
}

$allSuggestions = loadSuggestions();
$availableLeapOsVersions = availableLeapOsVersions($allSuggestions);
$suggestions = array_reverse($allSuggestions);
?>
<!DOCTYPE html>
<html lang="<?= e(currentLanguage()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(t('title.document')) ?></title>
  <link rel="icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon" sizes="32x32">
  <link rel="shortcut icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon">
  <link rel="stylesheet" href="style.css?v=<?= e(stylesheetVersion()) ?>">
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div>
        <p class="eyebrow"><?= e(t('brand')) ?></p>
        <h1><?= e(t('title.public')) ?></h1>
        <p class="intro"><?= e(t('intro.public')) ?></p>
      </div>
      <div class="area-links"><a class="admin-link" href="admin/?reauth=1"><?= e(t('admin.link')) ?></a><a class="admin-link" href="export/"><?= e(t('export.link')) ?></a><?= renderLanguageSelector() ?></div>
    </header>

    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <details class="form-panel form-collapsible">
      <summary class="form-toggle"><span class="eyebrow"><?= e(t('entry.new')) ?></span><span class="toggle-icon" aria-hidden="true"></span></summary>
      <div class="form-content">
        <div class="form-heading"><h2 id="form-title"><?= e(t('entry.prompt')) ?></h2><span class="required-note"><?= e(t('required.note')) ?></span></div>
      <form method="post" action="">
        <div class="form-grid">
          <label for="topic"><span><?= e(t('field.topic')) ?> <span>*</span></span>
            <input id="topic" name="topic" type="text" maxlength="256" value="<?= e($oldInput['topic']) ?>" required>
          </label>
          <label for="model"><span><?= e(t('field.model')) ?> <span>*</span></span>
            <select id="model" name="model" required>
              <option value="<?= e(ALL_MODELS) ?>"<?= $oldInput['model'] === ALL_MODELS ? ' selected' : '' ?>><?= e(t('model.all')) ?></option>
              <?php foreach (MODELS as $model): ?>
                <option value="<?= e($model) ?>"<?= $oldInput['model'] === $model ? ' selected' : '' ?>><?= e($model) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="version-fields">
            <label for="leapos_version"><span><?= e(t('field.version')) ?> <span>*</span></span>
              <select id="leapos_version" name="leapos_version" required>
                <option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>"<?= in_array($oldInput['leapos_version'], [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true) ? ' selected' : '' ?>><?= e(t('version.all')) ?></option>
              <?php foreach ($availableLeapOsVersions as $version): ?>
                  <option value="<?= e($version) ?>"<?= $oldInput['leapos_version'] === $version ? ' selected' : '' ?>><?= e($version) ?></option>
              <?php endforeach; ?>
                <option value="<?= e(ADD_LEAPOS_VERSION) ?>"<?= $isAddingLeapOsVersion ? ' selected' : '' ?>><?= e(t('version.add')) ?></option>
              </select>
            </label>
            <label class="version-custom-field" for="leapos_version_custom"<?= $isAddingLeapOsVersion ? '' : ' hidden' ?>><span><?= e(t('version.new')) ?> <span>*</span></span>
              <input id="leapos_version_custom" name="leapos_version_custom" type="text" maxlength="30" pattern="[0-9]+(\.[0-9]+)*" value="<?= $isAddingLeapOsVersion ? e($oldInput['leapos_version']) : '' ?>"<?= $isAddingLeapOsVersion ? ' required' : '' ?>>
            </label>
          </div>
          <label for="category"><span><?= e(t('field.category')) ?> <span>*</span></span>
            <select id="category" name="category" required>
              <option value="" disabled<?= $oldInput['category'] === '' ? ' selected' : '' ?>><?= e(t('select.prompt')) ?></option>
              <?php foreach (CATEGORIES as $category): ?>
                <option value="<?= e($category) ?>"<?= $oldInput['category'] === $category ? ' selected' : '' ?>><?= e(t('value.' . $category)) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label for="classification"><span><?= e(t('field.classification')) ?> <span>*</span></span>
            <select id="classification" name="classification" required>
              <option value="" disabled<?= $oldInput['classification'] === '' ? ' selected' : '' ?>><?= e(t('select.prompt')) ?></option>
              <?php foreach (CLASSIFICATIONS as $classification): ?>
                <option value="<?= e($classification) ?>"<?= $oldInput['classification'] === $classification ? ' selected' : '' ?>><?= e(t('value.' . $classification)) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="wide-field" for="suggestion"><span><?= e(t('field.suggestion')) ?> <span>*</span></span>
            <textarea id="suggestion" name="suggestion" rows="5" required><?= e($oldInput['suggestion']) ?></textarea>
          </label>
        </div>
        <button class="primary-button" type="submit"><?= e(t('submit.entry')) ?></button>
      </form>
      </div>
    </details>

    <section class="table-section public-table-section" aria-labelledby="list-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow"><?= e(t('overview')) ?></p>
          <h2 id="list-title"><?= e(t('suggestions.recorded')) ?></h2>
        </div>
        <div class="section-actions">
          <span class="count-badge"><?= count($suggestions) ?> <?= e(t(count($suggestions) === 1 ? 'entry' : 'entries')) ?></span>
          <button class="help-button" type="button" id="help-open" aria-haspopup="dialog"><?= e(t('help')) ?></button>
        </div>
      </div>
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
        <table>
          <thead><tr><th><?= e(t('table.number')) ?></th><th><?= e(t('table.created')) ?></th><th><?= e(t('filter.status')) ?></th><th><?= e(t('table.topic')) ?></th><th><?= e(t('table.model_version')) ?></th><th><?= e(t('table.suggestion')) ?></th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?>
            <tr><td class="empty-state" colspan="6"><?= e(t('table.empty')) ?></td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr data-filter-row data-filter-date="<?= e(substr((string) ($item['created_at'] ?? ''), 0, 10)) ?>" data-filter-status="<?= e($item['status'] ?? 'erfasst') ?>" data-filter-category="<?= e($item['category'] ?? '') ?>" data-filter-classification="<?= e($item['classification'] ?? '') ?>" data-filter-model="<?= e($item['model'] ?? '') ?>" data-filter-version="<?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?>" data-filter-topic="<?= e($item['topic'] ?? '') ?>" data-filter-suggestion="<?= e($item['suggestion'] ?? '') ?>">
              <td data-label="<?= e(t('table.number')) ?>"><?= e($item['number'] ?? '') ?></td>
              <td data-label="<?= e(t('table.created')) ?>"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Status"><div class="record-symbols"><span><?= renderSymbol('status', $item['status'] ?? 'erfasst') ?></span><span><?= renderSymbol('category', $item['category'] ?? 'Nicht angegeben') ?></span><span><?= renderSymbol('classification', $item['classification'] ?? 'Nicht angegeben') ?></span></div></td>
              <td data-label="<?= e(t('table.topic')) ?>" data-field="topic"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="<?= e(t('table.model_version')) ?>"><div class="model-version-tags"><span class="model-tag"><?= e(($item['model'] ?? '') === ALL_MODELS ? t('model.all') : ($item['model'] ?? '---')) ?></span><span class="model-tag"><?= e(displayLeapOsVersion($item['leapos_version'] ?? null) === UNKNOWN_LEAPOS_VERSION ? t('version.all') : displayLeapOsVersion($item['leapos_version'] ?? null)) ?></span></div></td>
              <td data-label="<?= e(t('table.suggestion')) ?>" data-field="suggestion" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          <tr data-filter-empty hidden><td class="empty-state" colspan="6"><?= e(t('table.no_matches')) ?></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <dialog class="help-dialog" id="help-dialog" aria-labelledby="help-title">
      <div class="help-dialog-content">
        <div class="help-dialog-header">
          <div>
            <p class="eyebrow"><?= e(t('overview')) ?></p>
            <h2 id="help-title"><?= e(t('help')) ?></h2>
          </div>
          <button class="dialog-close" type="button" id="help-close" aria-label="<?= e(t('dialog.close_help')) ?>">&times;</button>
        </div>
        <div class="help-sections">
          <section class="help-guide" aria-labelledby="help-guide-title">
            <h3 id="help-guide-title"><?= e(t('help.operation')) ?></h3>
            <div class="help-guide-grid">
              <div>
                <h4><?= e(t('help.users')) ?></h4>
                <p><?= e(t('help.users.text')) ?></p>
              </div>
              <div>
                <h4><?= e(t('help.admins')) ?></h4>
                <p><?= e(t('help.admins.text')) ?></p>
              </div>
            </div>
          </section>
          <section class="help-section" aria-labelledby="help-categories-title">
            <h3 id="help-categories-title"><?= e(t('help.category')) ?></h3>
            <ul class="help-symbol-list">
              <?php foreach (CATEGORIES as $category): ?>
                <li><?= renderSymbol('category', $category) ?><span><?= e(t('value.' . $category)) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <section class="help-section" aria-labelledby="help-classifications-title">
            <h3 id="help-classifications-title"><?= e(t('help.classification')) ?></h3>
            <ul class="help-symbol-list">
              <?php foreach (CLASSIFICATIONS as $classification): ?>
                <li><?= renderSymbol('classification', $classification) ?><span><?= e(t('value.' . $classification)) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <section class="help-section help-status-section" aria-labelledby="help-status-title">
            <h3 id="help-status-title"><?= e(t('help.status')) ?></h3>
            <dl class="status-help-list">
              <div><dt><?= renderSymbol('status', 'erfasst') ?><span><?= e(t('value.erfasst')) ?></span></dt><dd><?= e(t('status.erfasst.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'geprüft') ?><span><?= e(t('value.geprüft')) ?></span></dt><dd><?= e(t('status.geprüft.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'versendet') ?><span><?= e(t('value.versendet')) ?></span></dt><dd><?= e(t('status.versendet.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'abgelehnt') ?><span><?= e(t('value.abgelehnt')) ?></span></dt><dd><?= e(t('status.abgelehnt.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'bestätigt') ?><span><?= e(t('value.bestätigt')) ?></span></dt><dd><?= e(t('status.bestätigt.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'angekündigt') ?><span><?= e(t('value.angekündigt')) ?></span></dt><dd><?= e(t('status.angekündigt.help')) ?></dd></div>
              <div><dt><?= renderSymbol('status', 'verfügbar') ?><span><?= e(t('value.verfügbar')) ?></span></dt><dd><?= e(t('status.verfügbar.help')) ?></dd></div>
            </dl>
          </section>
        </div>
      </div>
    </dialog>
  </main>
  <script>
    const helpDialog = document.getElementById('help-dialog');
    const openHelp = document.getElementById('help-open');
    const closeHelp = document.getElementById('help-close');
    const leapOsVersionSelect = document.getElementById('leapos_version');
    const leapOsVersionCustomField = document.querySelector('.version-custom-field');
    const leapOsVersionCustomInput = document.getElementById('leapos_version_custom');

    function updateLeapOsVersionInput() {
      const addingVersion = leapOsVersionSelect.value === '<?= e(ADD_LEAPOS_VERSION) ?>';
      leapOsVersionCustomField.hidden = !addingVersion;
      leapOsVersionCustomInput.required = addingVersion;
      if (!addingVersion) {
        leapOsVersionCustomInput.value = '';
      }
    }

    openHelp.addEventListener('click', () => helpDialog.showModal());
    closeHelp.addEventListener('click', () => helpDialog.close());
    helpDialog.addEventListener('click', (event) => {
      if (event.target === helpDialog) {
        helpDialog.close();
      }
    });
    leapOsVersionSelect.addEventListener('change', updateLeapOsVersionInput);
    updateLeapOsVersionInput();

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
          highlightText(row.querySelector('[data-field="topic"]'), searchValue);
          highlightText(row.querySelector('[data-field="suggestion"]'), searchValue);
          row.hidden = !visible;
          if (visible) visibleCount++;
        });
        emptyRow.hidden = visibleCount > 0 || rows.length === 0;
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