<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$error = null;
$notice = null;
$oldInput = ['topic' => '', 'model' => ALL_MODELS, 'leapos_version' => UNKNOWN_LEAPOS_VERSION, 'category' => '', 'classification' => '', 'suggestion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['saved'])) {
  $notice = 'Der Vorschlag wurde eingetragen.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldInput = [
        'topic' => cleanText($_POST['topic'] ?? '', 256),
        'model' => cleanText($_POST['model'] ?? '', 20),
        'leapos_version' => cleanText($_POST['leapos_version'] ?? '', 30),
        'category' => cleanText($_POST['category'] ?? '', 40),
        'classification' => cleanText($_POST['classification'] ?? '', 40),
        'suggestion' => cleanText($_POST['suggestion'] ?? '', 10000),
    ];

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

$suggestions = array_reverse(loadSuggestions());
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verbesserungsvorschläge an Leapmotor</title>
  <link rel="icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon" sizes="32x32">
  <link rel="shortcut icon" href="/sfi_LeapOS/favicon.ico?v=4" type="image/x-icon">
  <link rel="stylesheet" href="style.css">
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
          <label for="leapos_version"><span>LeapOS-Version <span>*</span></span>
            <select id="leapos_version" name="leapos_version" required>
              <option value="<?= e(UNKNOWN_LEAPOS_VERSION) ?>"<?= $oldInput['leapos_version'] === UNKNOWN_LEAPOS_VERSION ? ' selected' : '' ?>>Nicht bekannt</option>
              <?php foreach (LEAPOS_VERSIONS as $version): ?>
                <option value="<?= e($version) ?>"<?= $oldInput['leapos_version'] === $version ? ' selected' : '' ?>><?= e($version) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
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
          <button class="help-button" type="button" id="status-help-open" aria-haspopup="dialog">Status-Hilfe</button>
        </div>
      </div>
      <div class="symbol-legend" aria-label="Symbolerklärung">
        <div><strong>Kategorie</strong><span><?php foreach (CATEGORIES as $category): ?><?= renderSymbol('category', $category) ?> <?= e($category) ?><?php endforeach; ?></span></div>
        <div><strong>Einordnung</strong><span><?php foreach (CLASSIFICATIONS as $classification): ?><?= renderSymbol('classification', $classification) ?> <?= e($classification) ?><?php endforeach; ?></span></div>
        <div><strong>Status</strong><span><?php foreach (STATUSES as $status): ?><?= renderSymbol('status', $status) ?> <?= e($status) ?><?php endforeach; ?></span></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nr.</th><th>Erfasst am</th><th>Thema</th><th>Modell</th><th>LeapOS-Version</th><th>Kategorie</th><th>Einordnung</th><th>Vorschlag</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?>
            <tr><td class="empty-state" colspan="9">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr>
              <td data-label="Nr."><?= e($item['number'] ?? '') ?></td>
              <td data-label="Erfasst am"><?= e(formatDate($item['created_at'] ?? '')) ?></td>
              <td data-label="Thema"><?= e($item['topic'] ?? '') ?></td>
              <td data-label="Modell"><span class="model-tag"><?= e($item['model'] ?? '') ?></span></td>
              <td data-label="LeapOS-Version"><?= e(($item['leapos_version'] ?? '') === UNKNOWN_LEAPOS_VERSION ? 'Nicht bekannt' : ($item['leapos_version'] ?? 'Nicht angegeben')) ?></td>
              <td data-label="Kategorie"><?= renderSymbol('category', $item['category'] ?? 'Nicht angegeben') ?></td>
              <td data-label="Einordnung"><?= renderSymbol('classification', $item['classification'] ?? 'Nicht angegeben') ?></td>
              <td data-label="Vorschlag" class="suggestion-cell"><?= nl2br(e($item['suggestion'] ?? '')) ?></td>
              <td data-label="Status"><?= renderSymbol('status', $item['status'] ?? 'erfasst') ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <dialog class="help-dialog" id="status-help" aria-labelledby="status-help-title">
      <div class="help-dialog-content">
        <div class="help-dialog-header">
          <div>
            <p class="eyebrow">Übersicht</p>
            <h2 id="status-help-title">Bedeutung der Status</h2>
          </div>
          <button class="dialog-close" type="button" id="status-help-close" aria-label="Status-Hilfe schließen">&times;</button>
        </div>
        <dl class="status-help-list">
          <div><dt><span class="status status-erfasst">erfasst</span></dt><dd>Der Vorschlag ist eingegangen und wurde noch nicht geprüft.</dd></div>
          <div><dt><span class="status status-geprüft">geprüft</span></dt><dd>Der Vorschlag wurde inhaltlich geprüft und für die weitere Bearbeitung freigegeben.</dd></div>
          <div><dt><span class="status status-versendet">versendet</span></dt><dd>Der Vorschlag wurde an Leapmotor weitergeleitet.</dd></div>
          <div><dt><span class="status status-abgelehnt">abgelehnt</span></dt><dd>Der Vorschlag wird von Leapmotor leider nicht weiterverfolgt.</dd></div>
          <div><dt><span class="status status-bestätigt">bestätigt</span></dt><dd>Leapmotor hat den Vorschlag aufgenommen und bestätigt.</dd></div>
          <div><dt><span class="status status-angekündigt">angekündigt</span></dt><dd>Die Umsetzung des Vorschlags wurde für das nächste Release angekündigt.</dd></div>
          <div><dt><span class="status status-verfügbar">verfügbar</span></dt><dd>Die vorgeschlagene Verbesserung ist jetzt prinzipiell verfügbar.</dd></div>
        </dl>
      </div>
    </dialog>
  </main>
  <script>
    const statusHelp = document.getElementById('status-help');
    const openStatusHelp = document.getElementById('status-help-open');
    const closeStatusHelp = document.getElementById('status-help-close');

    openStatusHelp.addEventListener('click', () => statusHelp.showModal());
    closeStatusHelp.addEventListener('click', () => statusHelp.close());
    statusHelp.addEventListener('click', (event) => {
      if (event.target === statusHelp) {
        statusHelp.close();
      }
    });
  </script>
</body>
</html>