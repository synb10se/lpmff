<?php
session_start();

const PYTHON_BIN = 'python3';
const SCRIPT_PATH = __DIR__ . '/csv2html.py';
const OUTPUT_FILENAME = 'lm-kba-statistik.html';
const OUTPUT_PATH = __DIR__ . '/' . OUTPUT_FILENAME;

$error = null;
$generatedHtml = is_readable(OUTPUT_PATH)
  ? file_get_contents(OUTPUT_PATH)
  : (isset($_SESSION['generated_html']) ? $_SESSION['generated_html'] : '');
$generatedHtml = $generatedHtml === false ? '' : $generatedHtml;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file1']) || $_FILES['file1']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Bitte eine CSV-Datei auswählen.';
    } elseif (!is_uploaded_file($_FILES['file1']['tmp_name'])) {
        $error = 'Die hochgeladene Datei konnte nicht geprüft werden.';
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
                if (!copy($temporaryOutput, OUTPUT_PATH)) {
                    $error = 'Die erzeugte HTML-Datei konnte nicht gespeichert werden.';
                } else {
                    $generatedHtml = file_get_contents(OUTPUT_PATH);
                    if ($generatedHtml === false) {
                        $error = 'Die erzeugte HTML-Datei konnte nicht gelesen werden.';
                    } else {
                        $_SESSION['generated_html'] = $generatedHtml;
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
    iframe {
      width: 100%;
      min-height: 700px;
      border: 1px solid #ccc;
      background: #fff;
    }
  </style>
</head>
<body>
  <h1>KBA-Statistik auswerten</h1>
  <?php if ($error !== null): ?>
    <p class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
  <form action="" method="post" enctype="multipart/form-data">
    <label for="file1">KBA-CSV-Datei:</label>
    <input id="file1" type="file" name="file1" accept=".csv,text/csv" required>
    <button type="submit">Statistik erzeugen</button>
  </form>
  <section class="result">
    <h2>Ergebnis</h2>
    <iframe title="Generierte KBA-Statistik" srcdoc="<?= htmlspecialchars($generatedHtml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></iframe>
  </section>
</body>
</html>
