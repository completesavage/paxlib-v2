<?php
/**
 * Sync Movies from Polaris Recordset
 * GET  - Returns current sync status / last sync info
 * POST - Triggers a full sync from the recordset API
 *
 * Recordset: /recordsets/473530/records
 * Replaces dvds.csv as the source of truth for the movie list.
 *
 * Output: data/movies_cache.json  (array of movie objects keyed by barcode)
 *         data/movies_list.json   (flat array sorted A-Z, for list.php / movies.php)
 */

set_time_limit(120);
ini_set('max_execution_time', '120');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/polaris.php';

$dataDir      = __DIR__ . '/../data';
$cacheFile    = "$dataDir/movies_cache.json";  // barcode-keyed map used by continuous checker
$listFile     = "$dataDir/movies_list.json";   // flat sorted array used by movies.php
$syncMetaFile = "$dataDir/sync_meta.json";
$lockFile     = "$dataDir/sync.lock";

if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

// ── GET: status ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $meta = file_exists($syncMetaFile) ? json_decode(file_get_contents($syncMetaFile), true) : null;
    $running = file_exists($lockFile) && (time() - filemtime($lockFile)) < 120;
    echo json_encode([
        'ok'      => true,
        'running' => $running,
        'meta'    => $meta,
    ]);
    exit;
}

// ── POST: run sync ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Prevent concurrent syncs
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 120) {
    echo json_encode(['ok' => false, 'error' => 'Sync already in progress']);
    exit;
}
file_put_contents($lockFile, time());

try {
    $api = PolarisAPI::getInstance();

    // ── 1. Figure out total count first (numRecords=0 gives ObjectTypeID + empty Records)
    error_log("sync-movies: fetching record count");
    $countResult = $api->apiRequest('GET',
        'polaris/699/3073/recordsets/473530/records?startIndex=0&numRecords=0');

    if (!$countResult['ok']) {
        throw new Exception('Failed to contact recordset API: ' . ($countResult['error'] ?? 'unknown'));
    }

    // The API always returns all matching records when numRecords=0 gives count=0,
    // so we page in batches of 100.
    $pageSize  = 100;
    $startIndex = 0;
    $allRecords = [];
    $maxPages   = 100; // safety cap (10,000 items)

    // ── 2. Page through all records ───────────────────────────────────────────
    do {
        error_log("sync-movies: fetching startIndex=$startIndex");
        $result = $api->apiRequest('GET',
            "polaris/699/3073/recordsets/473530/records?startIndex={$startIndex}&numRecords={$pageSize}");

        if (!$result['ok'] || !isset($result['data'])) {
            throw new Exception("Page fetch failed at startIndex=$startIndex");
        }

        $records = $result['data']['Records'] ?? [];
        if (empty($records)) break; // no more data

        $allRecords = array_merge($allRecords, $records);
        $startIndex += $pageSize;
        $maxPages--;

        // Small pause to be polite to the API
        if (!empty($records) && count($records) === $pageSize) usleep(200000);

    } while (count($records) === $pageSize && $maxPages > 0);

    error_log("sync-movies: fetched " . count($allRecords) . " total records");

    // ── 3. Load existing cache so we can preserve cover URLs, overrides, etc.
    $existingCache = [];
    if (file_exists($cacheFile)) {
        $raw = json_decode(file_get_contents($cacheFile), true);
        // movies_cache.json may be the availability cache (has 'statuses' key)
        // or the movie-data cache (flat barcode => movie map)
        // We want the movie-data version, which movies.php also writes.
        if (isset($raw['statuses'])) {
            // That's the availability cache, not movie data
            $existingCache = [];
        } else {
            $existingCache = $raw ?: [];
        }
    }

    // Also load overrides
    $overridesFile = "$dataDir/movies_overrides.json";
    $overrides = file_exists($overridesFile)
        ? (json_decode(file_get_contents($overridesFile), true) ?: [])
        : [];

    // Load cover cache
    $coversFile = __DIR__ . '/../data/covers_cache.json';
    $coverCache = file_exists($coversFile)
        ? (json_decode(file_get_contents($coversFile), true) ?: [])
        : [];

    // ── 4. Build movie objects ─────────────────────────────────────────────────
    $movieMap  = [];   // barcode => movie (for movies_cache.json)
    $movieList = [];   // flat array for movies_list.json

    $dvdCounter = 1;   // sequential #, replaces CSV column 0
    // Sort by barcode to keep numbering stable across syncs
    usort($allRecords, fn($a, $b) => strcmp($a['Barcode'] ?? '', $b['Barcode'] ?? ''));

    foreach ($allRecords as $rec) {
        $barcode = trim($rec['Barcode'] ?? '');
        if (empty($barcode)) continue;

        // Skip non-DVD material types
        $matType = strtolower($rec['MaterialType'] ?? '');
        if ($matType && $matType !== 'dvd') continue;

        // Clean title — strip " [videorecording]" suffix
        $rawTitle = $rec['Title'] ?? 'Unknown';
        $title    = preg_replace('/\s*\[videorecording\]\s*/i', '', $rawTitle);
        $title    = preg_replace('/\s*:\s*[a-z].*$/i', '', $title); // strip sub-titles if desired (optional)
        $title    = trim($title, " \t\n\r\0\x0B/:-");

        // Rating from CallNumber: "PG13 DVD 86" → "PG-13"
        $callNumber = trim($rec['CallNumber'] ?? '');
        $rating     = extractRatingFromCallNumber($callNumber);

        // bibRecordId — Polaris uses RecordID as the item record ID.
        // For hold placement we need the BibRecordID; we'll store RecordID and
        // look it up lazily (existing code in movies.php already handles this).
        $itemRecordId = (int)($rec['RecordID'] ?? 0);

        // Preserve data from existing cache
        $existing = $existingCache[$barcode] ?? [];

        // Cover URL: existing override > covers_cache > no-cover
        $cover = $overrides[$barcode]['cover']
              ?? $existing['cover']
              ?? $coverCache[$barcode]
              ?? null;

        // bibRecordId may have been discovered and cached previously
        $bibRecordId = $overrides[$barcode]['bibRecordId']
                    ?? $existing['bibRecordId']
                    ?? null;

        $movie = [
            'dvdId'        => (string)$dvdCounter,
            'id'           => (string)$dvdCounter,
            'title'        => $title,
            'barcode'      => $barcode,
            'rating'       => $rating,
            'callNumber'   => $callNumber,
            'itemRecordId' => $itemRecordId,
            'bibRecordId'  => $bibRecordId,
            'cover'        => $cover,
            'location'     => 'DVD Section',
            'status'       => $rec['Status'] ?? null,
            'itemStatusId' => (int)($rec['ItemStatusID'] ?? 0),
            'lastActivity' => $rec['LastActivityDate'] ?? null,
            'sortTitle'    => $rec['SortTitle'] ?? '',
        ];

        // Merge any overrides on top
        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
            $movie['barcode'] = $barcode; // never let override stomp the barcode
        }

        $movieMap[$barcode]  = $movie;
        $movieList[]         = $movie;
        $dvdCounter++;
    }

    // Sort list A-Z by title
    usort($movieList, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    // ── 5. Write output files ──────────────────────────────────────────────────
    // movies_list.json: flat sorted array (what movies.php returns for listing)
    file_put_contents($listFile, json_encode($movieList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // movies_cache.json: flat array of movie objects (used by continuous-availability.php)
    // Note: continuous-availability.php uses this as an array (not keyed by barcode)
    file_put_contents($cacheFile, json_encode($movieList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Sync metadata
    $meta = [
        'lastSync'  => date('c'),
        'count'     => count($movieList),
        'source'    => 'recordset/473530',
    ];
    file_put_contents($syncMetaFile, json_encode($meta, JSON_PRETTY_PRINT));

    // Remove lock
    if (file_exists($lockFile)) unlink($lockFile);

    error_log("sync-movies: done — " . count($movieList) . " movies written");

    echo json_encode([
        'ok'    => true,
        'count' => count($movieList),
        'meta'  => $meta,
    ]);

} catch (Exception $e) {
    if (file_exists($lockFile)) unlink($lockFile);
    error_log("sync-movies exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

// ── Helpers ────────────────────────────────────────────────────────────────────

/**
 * Extract MPAA rating from Polaris CallNumber string.
 * Examples: "PG13 DVD 86" → "PG-13"
 *           "NR DVD 90"   → "NR"
 *           "G DVD 7"     → "G"
 *           "R DVD 44"    → "R"
 */
function extractRatingFromCallNumber($callNumber) {
    $cn = strtoupper(trim($callNumber));

    // Try common patterns at start of call number
    $patterns = [
        '/^(PG-13)\b/'  => 'PG-13',
        '/^(PG13)\b/'   => 'PG-13',
        '/^(NC-17)\b/'  => 'NC-17',
        '/^(NC17)\b/'   => 'NC-17',
        '/^(PG)\b/'     => 'PG',
        '/^(NR)\b/'     => 'NR',
        '/^(R)\b/'      => 'R',
        '/^(G)\b/'      => 'G',
    ];

    foreach ($patterns as $pattern => $rating) {
        if (preg_match($pattern, $cn)) return $rating;
    }

    return 'NR'; // default
}
