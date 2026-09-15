<?php
session_start();

const PYTHON_BIN = 'python3';
const SCRIPT_PATH = __DIR__ . '/csv2html.py';
const OUTPUT_FILENAME = 'kba-statistiken.html';
const OUTPUT_PATH = __DIR__ . '/' . OUTPUT_FILENAME;

$error = null;
$notice = null;
$generatedHtml = isset($_SESSION['pending_html'])
  ? $_SESSION['pending_html']
  : (is_readable(OUTPUT_PATH)
  ? file_get_contents(OUTPUT_PATH)
  : (isset($_SESSION['generated_html']) ? $_SESSION['generated_html'] : ''));
$generatedHtml = $generatedHtml === false ? '' : $generatedHtml;
$hasPendingHtml = isset($_SESSION['pending_html']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'generate';

  if ($action === 'save') {
    if (!isset($_SESSION['pending_html'])) {
      $error = 'Es liegt keine ungespeicherte Statistik vor.';
    } elseif (file_put_contents(OUTPUT_PATH, $_SESSION['pending_html'], LOCK_EX) === false) {
      $error = 'Die erzeugte HTML-Datei konnte nicht gespeichert werden.';
    } else {
      $_SESSION['generated_html'] = $_SESSION['pending_html'];
      $generatedHtml = $_SESSION['generated_html'];
      unset($_SESSION['pending_html']);
      $hasPendingHtml = false;
      $notice = 'Die Statistik wurde als ' . OUTPUT_FILENAME . ' gespeichert.';
    }
  } else {
    if (!isset($_FILES['file1']) || $_FILES['file1']['error'] !== UPLOAD_ERR_OK) {
      $error = 'Bitte eine CSV-Datei auswählen.';
    } elseif (!is_uploaded_file($_FILES['file1']['tmp_name'])) {
      $error = 'Die CSV-Datei konnte nicht geprüft werden.';
    } else {
      $temporaryInput = tempnam(sys_get_temp_dir(), 'kba-input-');
      $temporaryOutput = tempnam(sys_get_temp_dir(), 'kba-output-');

      if ($temporaryInput === false || $temporaryOutput === false) {
        $error = 'Temporäre Dateien konnten nicht angelegt werden.';
      } elseif (!move_uploaded_file($_FILES['file1']['tmp_name'], $temporaryInput)) {
        $error = 'Die CSV-Datei konnte nicht gespeichert werden.';
      } else {
        $command = implode(' ', [
          escapeshellarg(PYTHON_BIN),
          escapeshellarg(SCRIPT_PATH),
          '--input', escapeshellarg($temporaryInput),
          '--output', escapeshellarg($temporaryOutput),
          '--quiet',
        ]);
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || !is_readable($temporaryOutput)) {
          $details = trim(implode("\n", $output));
          $error = 'CSV konnte nicht verarbeitet werden.' . ($details ? " $details" : '');
        } else {
          $generatedHtml = file_get_contents($temporaryOutput);
          if ($generatedHtml === false) {
            $error = 'Die erzeugte HTML-Datei konnte nicht gelesen werden.';
          } else {
            $_SESSION['pending_html'] = $generatedHtml;
            $hasPendingHtml = true;
            $notice = 'Die Statistik wurde erzeugt. Bitte bestätige das lokale Speichern.';
          }
        }
      }

      if ($temporaryInput !== false && is_file($temporaryInput)) {
        unlink($temporaryInput);
      }
      if ($temporaryOutput !== false && is_file($temporaryOutput)) {
        unlink($temporaryOutput);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KBA-Statistik auswerten</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background: #f5f5f5;
    }
    form, .result {
      background: #fff;
      padding: 16px;
      margin-bottom: 20px;
    }
    .error {
      color: #a00;
      white-space: pre-wrap;
      margin: 0 0 16px;
    }
    .notice {
      color: #174d2a;
      margin: 0 0 16px;
    }
    iframe {
      width: 100%;
      height: calc(100vh - 220px);
      min-height: 800px;
      border: 1px solid #ccc;
      background: #fff;
    }
    @media (max-width: 700px) {
      iframe {
        height: calc(100vh - 260px);
        min-height: 620px;
      }
    }
  </style>
</head>
<body>
  <h1>KBA-Statistik auswerten</h1>
  <?php if ($error !== null): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if ($notice !== null): ?>
    <p class="notice"><?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
  <form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="generate">
    <label for="file1">KBA-CSV-Datei:</label>
    <input id="file1" type="file" name="file1" accept=".csv,text/csv" required>
    <button type="submit">Statistik erzeugen</button>
  </form>
  <?php if ($hasPendingHtml): ?>
    <form action="" method="post">
      <input type="hidden" name="action" value="save">
      <p>Die neue Statistik wurde erzeugt. Soll <?= htmlspecialchars(OUTPUT_FILENAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> jetzt überschrieben werden?</p>
      <button type="submit">Lokales Speichern bestätigen</button>
    </form>
  <?php endif; ?>
  <section class="result">
    <h2>Ergebnis</h2>
    <iframe title="Generierte KBA-Statistik" srcdoc="<?= htmlspecialchars($generatedHtml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></iframe>
  </section>
</body>
</html>
