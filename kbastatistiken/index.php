<?php
session_start();

const PYTHON_BIN = 'python3';
const SCRIPT_PATH = __DIR__ . '/csv2html.py';
const OUTPUT_FILENAME = 'kba-statistiken.html';
const OUTPUT_PATH = __DIR__ . '/' . OUTPUT_FILENAME;
const SAVE_PASSWORD_HASH_ENV = 'KBA_SAVE_PASSWORD_HASH';

$error = null;
$notice = null;
$generatedHtml = isset($_SESSION['pending_html'])
  ? $_SESSION['pending_html']
  : (is_readable(OUTPUT_PATH)
  ? file_get_contents(OUTPUT_PATH)
  : (isset($_SESSION['generated_html']) ? $_SESSION['generated_html'] : ''));
$generatedHtml = $generatedHtml === false ? '' : $generatedHtml;
$hasPendingHtml = isset($_SESSION['pending_html']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'download') {
  if (!$hasPendingHtml) {
    http_response_code(404);
    exit('Kein aktuelles Ergebnis zum Herunterladen vorhanden.');
  }

  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . OUTPUT_FILENAME . '"');
  header('Content-Length: ' . strlen($generatedHtml));
  echo $generatedHtml;
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'generate';

  if ($action === 'reset') {
    unset($_SESSION['pending_html'], $_SESSION['generated_html']);
    $generatedHtml = is_readable(OUTPUT_PATH) ? file_get_contents(OUTPUT_PATH) : '';
    $generatedHtml = $generatedHtml === false ? '' : $generatedHtml;
    $hasPendingHtml = false;
    $notice = 'Die Ansicht wurde zurückgesetzt.';
  } elseif ($action === 'save') {
    $savePasswordHash = getenv(SAVE_PASSWORD_HASH_ENV);
    if (!isset($_SESSION['pending_html'])) {
      $error = 'Es liegt keine ungespeicherte Statistik vor.';
    } elseif (!is_string($savePasswordHash) || $savePasswordHash === ''
      || !isset($_POST['save_password'])
      || !is_string($_POST['save_password'])
      || !password_verify($_POST['save_password'], $savePasswordHash)) {
      $error = 'Das Passwort ist nicht korrekt. Die Datei wurde nicht verändert.';
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
    .action-row {
      display: flex;
      gap: 12px;
      align-items: center;
    }
    .action-row form {
      margin: 0;
    }
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }
    .reset-form {
      margin: 0;
      padding: 0;
      background: transparent;
    }
    .save-form {
      background: tomato;
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
  <div class="page-header">
    <h1>KBA-Statistik auswerten</h1>
    <form class="reset-form" action="" method="post">
      <input type="hidden" name="action" value="reset">
      <button type="submit" title="Alle aktuellen Eingaben und Ergebnisse zurücksetzen">Zurücksetzen</button>
    </form>
  </div>
  <?php if ($error !== null): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if ($notice !== null): ?>
    <p class="notice"><?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
  <div class="action-row">
    <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="generate">
      <label for="file1">KBA-CSV-Datei:</label>
      <input id="file1" type="file" name="file1" accept=".csv,text/csv" required>
      <button type="submit" title="CSV-Datei verarbeiten und eine neue Statistik erzeugen">Statistik erzeugen</button>
    </form>
    <?php if ($hasPendingHtml): ?>
      <form action="" method="get">
        <input type="hidden" name="action" value="download">
        <button type="submit" title="Die neu erzeugte Statistik als HTML-Datei herunterladen">HTML-Datei herunterladen</button>
      </form>
    <?php endif; ?>
  </div>
  <?php if ($hasPendingHtml): ?>
    <form class="save-form" action="" method="post">
      <input type="hidden" name="action" value="save">
      <p>Die neue Statistik wurde erzeugt. Soll <?= htmlspecialchars(OUTPUT_FILENAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> jetzt überschrieben werden?</p>
      <label for="save_password">Passwort:</label>
      <input id="save_password" type="password" name="save_password" required autocomplete="current-password">
      <button type="submit" title="Die neue Statistik nach Passwortprüfung lokal speichern">Lokales Speichern bestätigen</button>
    </form>
  <?php endif; ?>
  <section class="result">
    <h2>Ergebnis</h2>
    <iframe title="Generierte KBA-Statistik" srcdoc="<?= htmlspecialchars($generatedHtml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></iframe>
  </section>
</body>
</html>
