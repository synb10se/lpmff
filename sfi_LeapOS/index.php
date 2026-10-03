<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$error = null;
$notice = null;
$oldInput = ['topic' => '', 'model' => ALL_MODELS, 'leapos_version' => UNKNOWN_LEAPOS_VERSION, 'category' => '', 'classification' => '', 'suggestion' => ''];
$isAddingLeapOsVersion = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['saved'])) {
  $notice = 'Der Vorschlag wurde eingetragen.';
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
        $error = 'Bitte ein Thema mit maximal 256 Zeichen eingeben.';
      } elseif (!isValidModel($oldInput['model'])) {
        $error = 'Bitte ein gültiges Modell auswählen.';
    } elseif (!isValidLeapOsVersion($oldInput['leapos_version'])) {
        $error = 'Bitte eine gültige LeapOS-Version auswählen.';
    } elseif (!in_array($oldInput['category'], CATEGORIES, true)) {
        $error = 'Bitte eine gültige Kategorie auswählen.';
    } elseif (!in_array($oldInput['classification'], CLASSIFICATIONS, true)) {
        $error = 'Bitte eine gültige Einordnung auswählen.';
    } elseif ($oldInput['suggestion'] === '') {
        $error = 'Bitte einen Vorschlag eingeben.';
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
            $error = 'Der Vorschlag konnte nicht gespeichert werden. Bitte die Schreibrechte prüfen.';
        }
    }
}

$allSuggestions = loadSuggestions();
$availableLeapOsVersions = availableLeapOsVersions($allSuggestions);
$suggestions = array_reverse($allSuggestions);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verbesserungsvorschläge an Leapmotor</title>
  <link rel="icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon" sizes="32x32">
  <link rel="shortcut icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon">
  <link rel="stylesheet" href="style.css?v=<?= e(stylesheetVersion()) ?>">
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div>
        <p class="eyebrow">LeapOS und mehr</p>
        <h1>Verbesserungsvorschläge</h1>
        <p class="intro">Ideen sammeln, strukturieren und kommunizieren.</p>
      </div>
      <div class="area-links"><a class="admin-link" href="admin/?reauth=1">Bearbeitung</a><a class="admin-link" href="export/">Export</a></div>
    </header>

    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <details class="form-panel form-collapsible">
      <summary class="form-toggle"><span class="eyebrow">Neue Meldung</span><span class="toggle-icon" aria-hidden="true"></span></summary>
      <div class="form-content">
        <div class="form-heading"><h2 id="form-title">Was können wir verbessern?</h2><span class="required-note">* Pflichtfeld</span></div>
      <form method="post" action="">
        <div class="form-grid">
          <label for="topic"><span>Thema <span>*</span></span>
            <input id="topic" name="topic" type="text" maxlength="256" value="<?= e($oldInput['topic']) ?>" required>
          </label>
          <label for="model"><span>Modell <span>*</span></span>
            <select id="model" name="model" required>
              <option value="<?= e(ALL_MODELS) ?>"<?= $oldInput['model'] === ALL_MODELS ? ' selected' : '' ?>><?= e(ALL_MODELS) ?></option>
              <?php foreach (MODELS as $model): ?>
                <option value="<?= e($model) ?>"<?= $oldInput['model'] === $model ? ' selected' : '' ?>><?= e($model) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="version-fields">
            <label for="leapos_version"><span>LeapOS-Version <span>*</span></span>
              <select id="leapos_version" name="leapos_version" required>
                <option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>"<?= in_array($oldInput['leapos_version'], [UNKNOWN_LEAPOS_VERSION, LEGACY_UNKNOWN_LEAPOS_VERSION], true) ? ' selected' : '' ?>>alle</option>
              <?php foreach ($availableLeapOsVersions as $version): ?>
                  <option value="<?= e($version) ?>"<?= $oldInput['leapos_version'] === $version ? ' selected' : '' ?>><?= e($version) ?></option>
              <?php endforeach; ?>
                <option value="<?= e(ADD_LEAPOS_VERSION) ?>"<?= $isAddingLeapOsVersion ? ' selected' : '' ?>>Version hinzufügen …</option>
              </select>
            </label>
            <label class="version-custom-field" for="leapos_version_custom"<?= $isAddingLeapOsVersion ? '' : ' hidden' ?>><span>Neue Versionsnummer <span>*</span></span>
              <input id="leapos_version_custom" name="leapos_version_custom" type="text" maxlength="30" pattern="[0-9]+(\.[0-9]+)*" value="<?= $isAddingLeapOsVersion ? e($oldInput['leapos_version']) : '' ?>"<?= $isAddingLeapOsVersion ? ' required' : '' ?>>
            </label>
          </div>
          <label for="category"><span>Kategorie <span>*</span></span>
            <select id="category" name="category" required>
              <option value="" disabled<?= $oldInput['category'] === '' ? ' selected' : '' ?>>Bitte auswählen</option>
              <?php foreach (CATEGORIES as $category): ?>
                <option value="<?= e($category) ?>"<?= $oldInput['category'] === $category ? ' selected' : '' ?>><?= e($category) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label for="classification"><span>Einordnung <span>*</span></span>
            <select id="classification" name="classification" required>
              <option value="" disabled<?= $oldInput['classification'] === '' ? ' selected' : '' ?>>Bitte auswählen</option>
              <?php foreach (CLASSIFICATIONS as $classification): ?>
                <option value="<?= e($classification) ?>"<?= $oldInput['classification'] === $classification ? ' selected' : '' ?>><?= e($classification) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="wide-field" for="suggestion"><span>Vorschlag <span>*</span></span>
            <textarea id="suggestion" name="suggestion" rows="5" required><?= e($oldInput['suggestion']) ?></textarea>
          </label>
        </div>
        <button class="primary-button" type="submit">Eintragen</button>
      </form>
      </div>
    </details>

    <section class="table-section public-table-section" aria-labelledby="list-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Übersicht</p>
          <h2 id="list-title">Erfasste Vorschläge</h2>
        </div>
        <div class="section-actions">
          <span class="count-badge"><?= count($suggestions) ?> Einträge</span>
          <button class="help-button" type="button" id="help-open" aria-haspopup="dialog">Hilfe</button>
        </div>
      </div>
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
        <table>
          <thead><tr><th>Nr.</th><th>Erfasst am</th><th>Status</th><th>Thema</th><th>Modell / LeapOS</th><th>Vorschlag</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?>
            <tr><td class="empty-state" colspan="6">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr data-filter-row data-filter-date="<?= e(substr((string) ($item['created_at'] ?? ''), 0, 10)) ?>" data-filter-status="<?= e($item['status'] ?? 'erfasst') ?>" data-filter-category="<?= e($item['category'] ?? '') ?>" data-filter-classification="<?= e($item['classification'] ?? '') ?>" data-filter-model="<?= e($item['model'] ?? '') ?>" data-filter-version="<?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?>" data-filter-topic="<?= e($item['topic'] ?? '') ?>" data-filter-suggestion="<?= e($item['suggestion'] ?? '') ?>">
              <td data-label="Nr."><?= e($item['number'] ?? '') ?></td>
              <td data-label="Erfasst am"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Status"><div class="record-symbols"><span><?= renderSymbol('status', $item['status'] ?? 'erfasst') ?></span><span><?= renderSymbol('category', $item['category'] ?? 'Nicht angegeben') ?></span><span><?= renderSymbol('classification', $item['classification'] ?? 'Nicht angegeben') ?></span></div></td>
              <td data-label="Thema"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="Modell / LeapOS"><div class="model-version-tags"><span class="model-tag"><?= e($item['model'] ?? '---') ?></span><span class="model-tag"><?= e(displayLeapOsVersion($item['leapos_version'] ?? null)) ?></span></div></td>
              <td data-label="Vorschlag" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          <tr data-filter-empty hidden><td class="empty-state" colspan="6">Keine passenden Einträge gefunden.</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <dialog class="help-dialog" id="help-dialog" aria-labelledby="help-title">
      <div class="help-dialog-content">
        <div class="help-dialog-header">
          <div>
            <p class="eyebrow">Übersicht</p>
            <h2 id="help-title">Hilfe</h2>
          </div>
          <button class="dialog-close" type="button" id="help-close" aria-label="Hilfe schließen">&times;</button>
        </div>
        <div class="help-sections">
          <section class="help-guide" aria-labelledby="help-guide-title">
            <h3 id="help-guide-title">Bedienung</h3>
            <div class="help-guide-grid">
              <div>
                <h4>Benutzer</h4>
                <p>„Neue Meldung“ öffnen, die Angaben ausfüllen und den Vorschlag eintragen. Den Bearbeitungsstand sehen Sie anschließend in der Übersicht.</p>
              </div>
              <div>
                <h4>Administratoren</h4>
                <p>„Bearbeitung“ öffnen und anmelden. Eine ausgewählte Zeile kann bearbeitet werden; mehrere ausgewählte Einträge lassen sich gemeinsam im Status ändern oder löschen.</p>
              </div>
            </div>
          </section>
          <section class="help-section" aria-labelledby="help-categories-title">
            <h3 id="help-categories-title">Kategorie</h3>
            <ul class="help-symbol-list">
              <?php foreach (CATEGORIES as $category): ?>
                <li><?= renderSymbol('category', $category) ?><span><?= e($category) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <section class="help-section" aria-labelledby="help-classifications-title">
            <h3 id="help-classifications-title">Einordnung</h3>
            <ul class="help-symbol-list">
              <?php foreach (CLASSIFICATIONS as $classification): ?>
                <li><?= renderSymbol('classification', $classification) ?><span><?= e($classification) ?></span></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <section class="help-section help-status-section" aria-labelledby="help-status-title">
            <h3 id="help-status-title">Status</h3>
            <dl class="status-help-list">
              <div><dt><?= renderSymbol('status', 'erfasst') ?><span>erfasst</span></dt><dd>Der Vorschlag ist eingegangen und wurde noch nicht geprüft.</dd></div>
              <div><dt><?= renderSymbol('status', 'geprüft') ?><span>geprüft</span></dt><dd>Der Vorschlag wurde geprüft und für die weitere Bearbeitung freigegeben.</dd></div>
              <div><dt><?= renderSymbol('status', 'versendet') ?><span>versendet</span></dt><dd>Der Vorschlag wurde an Leapmotor weitergeleitet.</dd></div>
              <div><dt><?= renderSymbol('status', 'abgelehnt') ?><span>abgelehnt</span></dt><dd>Der Vorschlag wird nicht weiterverfolgt.</dd></div>
              <div><dt><?= renderSymbol('status', 'bestätigt') ?><span>bestätigt</span></dt><dd>Leapmotor hat den Vorschlag aufgenommen und bestätigt.</dd></div>
              <div><dt><?= renderSymbol('status', 'angekündigt') ?><span>angekündigt</span></dt><dd>Die Umsetzung wurde für ein nächstes Release angekündigt.</dd></div>
              <div><dt><?= renderSymbol('status', 'verfügbar') ?><span>verfügbar</span></dt><dd>Die vorgeschlagene Verbesserung ist prinzipiell verfügbar.</dd></div>
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
          highlightText(row.querySelector('[data-label="Thema"]'), searchValue);
          highlightText(row.querySelector('[data-label="Vorschlag"]'), searchValue);
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