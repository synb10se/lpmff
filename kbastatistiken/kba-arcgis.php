<?php
/**
 * kba-arcgis.php
 *
 * Lädt die KBA-Modellreihen-Daten direkt aus dem ArcGIS-FeatureServer des
 * KBA-Statistikportals und schreibt sie als CSV im gleichen Format wie der
 * manuelle Export aus dem Portal (gleiche Spaltenköpfe, UTF-8 mit BOM).
 *
 * Wird von index.php beim Klick auf "Statistik erzeugen" verwendet.
 */

const KBA_FEATURE_LAYER = 'https://services-eu1.arcgis.com/U09msXRZoxesNntH/arcgis/rest/services/SP_Modellreihen/FeatureServer/0';
const KBA_PAGE_SIZE = 1000;   // maxRecordCount des Layers
const KBA_PARALLEL = 6;       // gleichzeitige Abfragen
const KBA_TIMEOUT = 60;       // Sekunden pro Abfrage

/**
 * Baut die Abfrage-URL für den FeatureServer.
 */
function kba_url(string $path, array $params): string
{
  return KBA_FEATURE_LAYER . $path . '?' . http_build_query($params + ['f' => 'json']);
}

/**
 * Wertet eine JSON-Antwort von ArcGIS aus und wirft bei Fehlern eine Exception.
 */
function kba_decode($raw, string $url): array
{
  if (!is_string($raw) || $raw === '') {
    throw new RuntimeException('Keine Antwort vom KBA-Datendienst: ' . $url);
  }
  $json = json_decode($raw, true);
  if (!is_array($json)) {
    throw new RuntimeException('Ungültige Antwort vom KBA-Datendienst: ' . substr($raw, 0, 300));
  }
  if (isset($json['error'])) {
    $message = $json['error']['message'] ?? 'unbekannter Fehler';
    throw new RuntimeException('Fehler vom KBA-Datendienst: ' . $message);
  }
  return $json;
}

/**
 * Einzelne Abfrage an den FeatureServer.
 */
function kba_get(string $path, array $params): array
{
  $url = kba_url($path, $params);
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => KBA_TIMEOUT,
    CURLOPT_USERAGENT => 'LPMFF-KBA-Statistik',
  ]);
  $raw = curl_exec($ch);
  $error = curl_error($ch);
  curl_close($ch);
  if ($raw === false) {
    throw new RuntimeException('Abruf fehlgeschlagen: ' . $error);
  }
  return kba_decode($raw, $url);
}

/**
 * Mehrere Abfragen parallel ausführen. Jede Antwort wird in Reihenfolge der URLs
 * an $onPage übergeben und danach verworfen, damit nie alle Seiten gleichzeitig
 * im Speicher liegen.
 */
function kba_get_parallel(array $urls, callable $onPage): void
{
  foreach (array_chunk($urls, KBA_PARALLEL, true) as $chunk) {
    $multi = curl_multi_init();
    $handles = [];
    foreach ($chunk as $index => $url) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => KBA_TIMEOUT,
        CURLOPT_USERAGENT => 'LPMFF-KBA-Statistik',
      ]);
      curl_multi_add_handle($multi, $ch);
      $handles[$index] = $ch;
    }
    do {
      $status = curl_multi_exec($multi, $running);
      if ($running) {
        curl_multi_select($multi);
      }
    } while ($running && $status === CURLM_OK);

    ksort($handles);
    foreach ($handles as $index => $ch) {
      $page = kba_decode(curl_multi_getcontent($ch), $chunk[$index]);
      curl_multi_remove_handle($multi, $ch);
      curl_close($ch);
      $onPage($page);
      unset($page);
    }
    curl_multi_close($multi);
  }
}

/**
 * Lädt alle Datensätze und schreibt sie als CSV nach $csvPath.
 *
 * @return array{rows:int, stand:string} Anzahl Datensätze und neuester Berichtsmonat (z. B. "September 2026")
 */
function kba_fetch_csv(string $csvPath): array
{
  if (!function_exists('curl_multi_init')) {
    throw new RuntimeException('Die PHP-Erweiterung cURL ist nicht verfügbar.');
  }

  // Feldnamen und Aliase (= Spaltenköpfe der CSV)
  $meta = kba_get('', []);
  $fields = [];
  foreach ($meta['fields'] ?? [] as $field) {
    $fields[$field['name']] = $field['alias'];
  }
  if ($fields === []) {
    throw new RuntimeException('Der KBA-Datendienst liefert keine Felder.');
  }

  // Anzahl Datensätze und neuester Stand
  $count = (int) (kba_get('/query', ['where' => '1=1', 'returnCountOnly' => 'true'])['count'] ?? 0);
  if ($count === 0) {
    throw new RuntimeException('Der KBA-Datendienst liefert keine Datensätze.');
  }
  $latest = kba_get('/query', [
    'where' => '1=1',
    'outFields' => 'Berichtsjahr,Berichtsmonat,Monat_Sortierung',
    'orderByFields' => 'Berichtsjahr DESC,Monat_Sortierung DESC',
    'resultRecordCount' => 1,
    'returnGeometry' => 'false',
  ]);
  $attr = $latest['features'][0]['attributes'] ?? [];
  $stand = trim(($attr['Berichtsmonat'] ?? '') . ' ' . ($attr['Berichtsjahr'] ?? ''));

  // Alle Seiten parallel abrufen
  $urls = [];
  for ($offset = 0; $offset < $count; $offset += KBA_PAGE_SIZE) {
    $urls[] = kba_url('/query', [
      'where' => '1=1',
      'outFields' => '*',
      'orderByFields' => 'ObjectId ASC',
      'resultOffset' => $offset,
      'resultRecordCount' => KBA_PAGE_SIZE,
      'returnGeometry' => 'false',
    ]);
  }

  // CSV schreiben
  $out = fopen($csvPath, 'w');
  if ($out === false) {
    throw new RuntimeException('Die CSV-Datei konnte nicht angelegt werden.');
  }
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, array_values($fields), ',', '"', '');
  $rows = 0;
  kba_get_parallel($urls, function (array $page) use ($out, $fields, &$rows): void {
    foreach ($page['features'] ?? [] as $feature) {
      $row = [];
      foreach (array_keys($fields) as $name) {
        $row[] = $feature['attributes'][$name] ?? '';
      }
      fputcsv($out, $row, ',', '"', '');
      $rows++;
    }
  });
  fclose($out);

  if ($rows !== $count) {
    throw new RuntimeException("Unvollständiger Abruf: $rows von $count Datensätzen erhalten.");
  }

  return ['rows' => $rows, 'stand' => $stand];
}
