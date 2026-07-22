<?php
/**
 * Sync Movies from Polaris Recordset
 * GET  - Returns current sync status / last sync info
 * POST - Triggers a full sync from the recordset API
 *
 * Output: data/movies_list.json
 *         data/sync_meta.json
 */

set_time_limit(120);
ini_set('max_execution_time', '120');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/polaris.php';
require_once __DIR__ . '/movie_helpers.php';

$dataDir = __DIR__ . '/../data';
$listFile = "$dataDir/movies_list.json";
$syncMetaFile = "$dataDir/sync_meta.json";
$lockFile = "$dataDir/sync.lock";
$recordSetId = defined('DVD_RECORDSET_ID') ? DVD_RECORDSET_ID : 473530;

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $meta = file_exists($syncMetaFile) ? json_decode(file_get_contents($syncMetaFile), true) : null;
    $running = file_exists($lockFile) && (time() - filemtime($lockFile)) < 120;
    echo json_encode([
        'ok' => true,
        'running' => $running,
        'meta' => $meta,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 120) {
    echo json_encode(['ok' => false, 'error' => 'Sync already in progress']);
    exit;
}
file_put_contents($lockFile, time());

try {
    $api = PolarisAPI::getInstance();
    $allRecords = [];

    // Try full fetch first (numRecords=0 returns entire record set)
    error_log("sync-movies: fetching recordset $recordSetId (full list)");
    $fullResult = $api->getRecordSetContents($recordSetId, 0, 0);

    if (!$fullResult['ok']) {
        throw new Exception('Failed to contact recordset API: ' . ($fullResult['error'] ?? 'unknown'));
    }

    $allRecords = $fullResult['data']['Records'] ?? [];

    // Fall back to paging if the full fetch returned nothing
    if (empty($allRecords)) {
        $pageSize = 100;
        $startIndex = 0;
        $maxPages = 100;

        do {
            error_log("sync-movies: fetching startIndex=$startIndex");
            $result = $api->getRecordSetContents($recordSetId, $startIndex, $pageSize);

            if (!$result['ok'] || !isset($result['data'])) {
                throw new Exception("Page fetch failed at startIndex=$startIndex");
            }

            $records = $result['data']['Records'] ?? [];
            if (empty($records)) {
                break;
            }

            $allRecords = array_merge($allRecords, $records);
            $startIndex += $pageSize;
            $maxPages--;

            if (count($records) === $pageSize) {
                usleep(200000);
            }
        } while (count($records) === $pageSize && $maxPages > 0);
    }

    error_log('sync-movies: fetched ' . count($allRecords) . ' total records');

    $existingByBarcode = [];
    if (file_exists($listFile)) {
        $raw = json_decode(file_get_contents($listFile), true) ?: [];
        foreach ($raw as $movie) {
            if (!empty($movie['barcode'])) {
                $existingByBarcode[$movie['barcode']] = $movie;
            }
        }
    }

    $overridesFile = "$dataDir/movies_overrides.json";
    $overrides = file_exists($overridesFile)
        ? (json_decode(file_get_contents($overridesFile), true) ?: [])
        : [];

    $coverMaps = buildCoverMaps($dataDir);

    $movieList = [];
    $dvdCounter = 1;

    usort($allRecords, fn($a, $b) => strcmp($a['Barcode'] ?? '', $b['Barcode'] ?? ''));

    foreach ($allRecords as $rec) {
        $barcode = trim($rec['Barcode'] ?? '');
        if ($barcode === '') {
            continue;
        }

        $matType = strtolower($rec['MaterialType'] ?? '');
        if ($matType && strpos($matType, 'dvd') === false) {
            continue;
        }

        $rawTitle = $rec['Title'] ?? 'Unknown';
        $title = preg_replace('/\s*\[videorecording\]\s*/i', '', $rawTitle);
        $title = trim($title, " \t\n\r\0\x0B/:-");

        $callNumber = trim($rec['CallNumber'] ?? '');
        $rating = extractRatingFromCallNumber($callNumber);
        $status = $rec['Status'] ?? null;
        $existing = $existingByBarcode[$barcode] ?? [];

        // A physical barcode can be reassigned to a replacement/new DVD.
        // Never carry bibliographic metadata or dates across a changed record.
        $sameItem = !empty($existing)
            && (int)($existing['itemRecordId'] ?? 0) === (int)($rec['RecordID'] ?? 0)
            && normalizeMovieIdentityText($existing['title'] ?? '') === normalizeMovieIdentityText($title);

        $bibRecordId = $overrides[$barcode]['bibRecordId']
            ?? ($sameItem ? ($existing['bibRecordId'] ?? null) : null);

        // New-arrival tracking is independent from cover/bib identity.
        // Preserve the original first-seen date for every barcode already in
        // the synced list; only brand-new barcodes receive today's date.
        $dateAdded = null;
        if (!empty($existing['dateAdded'])) {
            $dateAdded = $existing['dateAdded'];
        } elseif (!isset($existingByBarcode[$barcode])) {
            $dateAdded = date('c');
        }

        $movie = enrichMovieAvailability([
            'dvdId' => (string)$dvdCounter,
            'id' => (string)$dvdCounter,
            'title' => $title,
            'barcode' => $barcode,
            'rating' => $rating,
            'callNumber' => $callNumber,
            'itemRecordId' => (int)($rec['RecordID'] ?? 0),
            'bibRecordId' => $bibRecordId,
            'cover' => null,
            'location' => 'DVD Section',
            'shelfLocation' => $rec['ShelfLocation'] ?? ($existing['shelfLocation'] ?? null),
            'status' => $status,
            'itemStatusId' => (int)($rec['ItemStatusID'] ?? 0),
            'lastActivity' => $rec['LastActivityDate'] ?? ($existing['lastActivity'] ?? null),
            'dateAdded' => $dateAdded,
            'sortTitle' => $rec['SortTitle'] ?? '',
        ]);

        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
            $movie['barcode'] = $barcode;
        }

        $movie = applyCoverMapsToMovie($movie, $coverMaps);
        $movie = enrichMovieAvailability($movie);
        $movie = enrichMovieShelfNumber($movie);

        $movieList[] = $movie;
        $dvdCounter++;
    }

    usort($movieList, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    file_put_contents($listFile, json_encode($movieList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $meta = [
        'lastSync' => date('c'),
        'count' => count($movieList),
        'source' => "recordset/{$recordSetId}",
    ];
    file_put_contents($syncMetaFile, json_encode($meta, JSON_PRETTY_PRINT));

    if (file_exists($lockFile)) {
        unlink($lockFile);
    }

    error_log('sync-movies: done — ' . count($movieList) . ' movies written');

    echo json_encode([
        'ok' => true,
        'count' => count($movieList),
        'meta' => $meta,
    ]);
} catch (Exception $e) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    error_log('sync-movies exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
