<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

$error = null;
$notice = null;
$oldInput = ['topic' => '', 'model' => '', 'suggestion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldInput = [
        'topic' => cleanText($_POST['topic'] ?? '', 256),
        'model' => cleanText($_POST['model'] ?? '', 20),
        'suggestion' => cleanText($_POST['suggestion'] ?? '', 10000),
    ];

    if ($oldInput['topic'] === '' || mb_strlen($oldInput['topic']) > 256) {
        $error = 'Bitte ein Thema mit maximal 256 Zeichen eingeben.';
    } elseif (!in_array($oldInput['model'], MODELS, true)) {
        $error = 'Bitte ein gültiges Modell auswählen.';
    } elseif ($oldInput['suggestion'] === '') {
        $error = 'Bitte einen Vorschlag eingeben.';
    } else {
        $suggestions = loadSuggestions();
        $suggestions[] = [
            'id' => bin2hex(random_bytes(8)),
            'created_at' => date(DATE_ATOM),
            'topic' => $oldInput['topic'],
            'model' => $oldInput['model'],
            'suggestion' => $oldInput['suggestion'],
            'status' => 'erfasst',
        ];

        if (saveSuggestions($suggestions)) {
            $notice = 'Der Vorschlag wurde eingetragen.';
            $oldInput = ['topic' => '', 'model' => '', 'suggestion' => ''];
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
  <title>Verbesserungsvorschläge</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <main class="page-shell">
    <header class="page-header">
      <div>
        <p class="eyebrow">LeapOS</p>
        <h1>Verbesserungsvorschläge</h1>
        <p class="intro">Ideen sammeln, sichtbar machen und gemeinsam weiterentwickeln.</p>
      </div>
      <a class="admin-link" href="admin/?reauth=1">Bearbeitung</a>
    </header>

    <?php if ($error !== null): ?><p class="message error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== null): ?><p class="message success"><?= e($notice) ?></p><?php endif; ?>

    <section class="form-panel" aria-labelledby="form-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Neue Meldung</p>
          <h2 id="form-title">Was können wir verbessern?</h2>
        </div>
        <span class="required-note">* Pflichtfeld</span>
      </div>
      <form method="post" action="">
        <div class="form-grid">
          <label for="topic">Thema <span>*</span>
            <input id="topic" name="topic" type="text" maxlength="256" value="<?= e($oldInput['topic']) ?>" required>
          </label>
          <label for="model">Modell <span>*</span>
            <select id="model" name="model" required>
              <option value="">Bitte auswählen</option>
              <?php foreach (MODELS as $model): ?>
                <option value="<?= e($model) ?>"<?= $oldInput['model'] === $model ? ' selected' : '' ?>><?= e($model) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="wide-field" for="suggestion">Vorschlag <span>*</span>
            <textarea id="suggestion" name="suggestion" rows="5" required><?= e($oldInput['suggestion']) ?></textarea>
          </label>
        </div>
        <button class="primary-button" type="submit">Eintragen</button>
      </form>
    </section>

    <section class="table-section" aria-labelledby="list-title">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Übersicht</p>
          <h2 id="list-title">Erfasste Vorschläge</h2>
        </div>
        <span class="count-badge"><?= count($suggestions) ?> Einträge</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Erfasst am</th><th>Thema</th><th>Modell</th><th>Vorschlag</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($suggestions === []): ?>
            <tr><td class="empty-state" colspan="5">Noch keine Vorschläge erfasst.</td></tr>
          <?php else: foreach ($suggestions as $item): ?>
            <tr>
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
</body>
</html>