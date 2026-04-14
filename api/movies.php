<?php
/**
 * Movies API
 *
 * GET              - List all movies (from synced cache)
 * GET ?barcode=X   - Get single movie with real-time availability
 * POST             - Update/override movie data (staff)
 * PUT ?action=X    - Cache operations (rebuild, etc.)
 *
 * Source priority for movie list:
 *   1. data/movies_list.json  (built by api/sync-movies.php from Polaris recordset)
 *   2. dvds.csv               (legacy fallback if sync hasn't run yet)
 */

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$polarisLoaded = false;
function loadPolaris() {
    global $polarisLoaded;
    if (!$polarisLoaded && file_exists(__DIR__ . '/polaris.php')) {
        include_once __DIR__ . '/polaris.php';
        $polarisLoaded = class_exists('PolarisAPI');
    }
    return $polarisLoaded;
}

$dataDir       = __DIR__ . '/../data';
$listFile      = "$dataDir/movies_list.json";   // from sync-movies.php
$cacheFile     = "$dataDir/movies_cache.json";  // same content, used by continuous-availability
$overridesFile = "$dataDir/movies_overrides.json";
$csvFile       = __DIR__ . '/../dvds.csv';

if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

// ── Loaders ────────────────────────────────────────────────────────────────────

function loadMovieList() {
    global $listFile, $cacheFile;

    // Prefer movies_list.json (written by sync-movies.php)
    if (file_exists($listFile)) {
        $data = json_decode(file_get_contents($listFile), true);
        if (is_array($data) && !empty($data)) return $data;
    }

    // Fallback: movies_cache.json (might be the same thing or older format)
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        // Must be a flat array of movie objects (not the availability cache)
        if (is_array($data) && !empty($data) && isset($data[0]['barcode'])) {
            return $data;
        }
    }

    return [];
}

function loadFromCSV() {
    global $csvFile;
    $movies = [];
    if (!file_exists($csvFile)) return $movies;
    $handle = fopen($csvFile, 'r');
    if (!$handle) return $movies;
    $counter = 1;
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 3) continue;
        $barcode = trim($row[2]);
        $title   = trim($row[1]);
        if (empty($barcode) || empty($title)) continue;
        $movies[] = [
            'dvdId'      => trim($row[0]),
            'id'         => trim($row[0]),
            'title'      => $title,
            'barcode'    => $barcode,
            'rating'     => normalizeRating(isset($row[3]) ? trim($row[3]) : ''),
            'callNumber' => null,
            'bibRecordId'=> null,
            'cover'      => null,
            'location'   => 'DVD Section',
        ];
        $counter++;
    }
    fclose($handle);
    return $movies;
}

function loadOverrides() {
    global $overridesFile;
    if (!file_exists($overridesFile)) return [];
    return json_decode(file_get_contents($overridesFile), true) ?: [];
}

function saveOverrides($overrides) {
    global $overridesFile;
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT));
}

function normalizeRating($rating) {
    $rating = strtoupper(trim($rating ?? ''));
    $map = [
        'PG13'      => 'PG-13',
        'PG 13'     => 'PG-13',
        'NC17'      => 'NC-17',
        'NC 17'     => 'NC-17',
        'NOT RATED' => 'NR',
        'UNRATED'   => 'NR',
        ''          => 'NR',
    ];
    return $map[$rating] ?? $rating;
}

/**
 * Return the merged movie list (synced data + overrides).
 * Falls back to CSV if the synced list doesn't exist yet.
 */
function getMergedMovies() {
    $overrides = loadOverrides();

    // Try synced list first
    $movies = loadMovieList();

    // Fallback to CSV
    if (empty($movies)) {
        $movies = loadFromCSV();
    }

    // Apply overrides and normalise
    $result = [];
    foreach ($movies as $movie) {
        $barcode = $movie['barcode'];

        // Ensure required fields exist
        $movie += [
            'dvdId'        => null,
            'id'           => null,
            'title'        => 'Unknown',
            'rating'       => 'NR',
            'callNumber'   => null,
            'bibRecordId'  => null,
            'cover'        => null,
            'location'     => 'DVD Section',
            'description'  => null,
            'itemRecordId' => null,
        ];

        // Normalise rating (in case CSV fallback was used)
        if (!empty($movie['rating'])) {
            $movie['rating'] = normalizeRating($movie['rating']);
        }

        // Apply overrides on top
        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
            $movie['barcode'] = $barcode; // never stomp barcode
        }

        $result[] = $movie;
    }

    // Sort A-Z by title
    usort($result, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    return $result;
}

// ── Route ──────────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ────────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;

    if ($barcode) {
        // Single movie — find in merged list
        $movies = getMergedMovies();
        $movie  = null;
        foreach ($movies as $m) {
            if ($m['barcode'] === $barcode) { $movie = $m; break; }
        }

        if (!$movie) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Movie not found']);
            exit;
        }

        // Real-time availability check
        $movie['status']    = 'Checking...';
        $movie['available'] = false;
        $debug = [];

        if (loadPolaris() && !empty($movie['bibRecordId'])) {
            try {
                $api = PolarisAPI::getInstance();
                $debug['bibRecordId'] = $movie['bibRecordId'];

                $result = $api->getBibAvailability($movie['bibRecordId']);
                $debug['bibAvailResult'] = $result;

                if ($result['ok']) {
                    $movie['status']         = $result['status'];
                    $movie['available']      = $result['available'];
                    $movie['availableCount'] = $result['availableCount'];
                    $movie['totalCount']     = $result['totalCount'];
                } else {
                    $movie['status'] = 'API Error';
                    $debug['error']  = $result['error'];
                }
            } catch (Exception $e) {
                $movie['status'] = 'Exception: ' . $e->getMessage();
                $debug['exception'] = $e->getMessage();
            }
        } elseif (!empty($movie['itemRecordId']) && loadPolaris()) {
            // No bibRecordId yet — try to look it up via the item record
            // and cache it for next time
            try {
                $api = PolarisAPI::getInstance();
                $itemResult = $api->getItemByBarcode($barcode);
                $debug['itemLookup'] = $itemResult['ok'] ?? false;

                if ($itemResult['ok'] && isset($itemResult['data']['AssociatedBibRecordID'])) {
                    $bibId = $itemResult['data']['AssociatedBibRecordID'];
                    $movie['bibRecordId'] = $bibId;

                    // Persist bibRecordId into overrides so future calls skip this lookup
                    $overrides = loadOverrides();
                    if (!isset($overrides[$barcode])) $overrides[$barcode] = ['barcode' => $barcode];
                    $overrides[$barcode]['bibRecordId'] = $bibId;
                    saveOverrides($overrides);

                    $result = $api->getBibAvailability($bibId);
                    if ($result['ok']) {
                        $movie['status']         = $result['status'];
                        $movie['available']      = $result['available'];
                        $movie['availableCount'] = $result['availableCount'];
                        $movie['totalCount']     = $result['totalCount'];
                    } else {
                        $movie['status'] = 'API Error';
                    }
                }
            } catch (Exception $e) {
                $debug['exception'] = $e->getMessage();
                $movie['status'] = 'Unknown';
            }
        } else {
            $movie['status']  = empty($movie['bibRecordId']) ? 'No bibRecordId' : 'Polaris unavailable';
            $debug['polarisLoaded'] = loadPolaris();
            $debug['hasBibId']      = !empty($movie['bibRecordId']);
        }

        $movie['_debug'] = $debug;
        echo json_encode(['ok' => true, 'movie' => $movie]);
        exit;
    }

    // List all movies
    $movies = getMergedMovies();

    // Attach sync metadata so kiosk can show "last synced X ago"
    $syncMetaFile = "$dataDir/sync_meta.json";
    $syncMeta = file_exists($syncMetaFile)
        ? json_decode(file_get_contents($syncMetaFile), true)
        : null;

    echo json_encode([
        'ok'       => true,
        'count'    => count($movies),
        'items'    => $movies,
        'syncMeta' => $syncMeta,
        'source'   => file_exists($listFile) ? 'polaris' : 'csv',
    ]);
    exit;
}

// ── POST — override movie data ─────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['barcode'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode']);
        exit;
    }

    $barcode   = $input['barcode'];
    $overrides = loadOverrides();

    if (!isset($overrides[$barcode])) $overrides[$barcode] = ['barcode' => $barcode];

    $allowed = ['title','rating','callNumber','location','description','cover','bibRecordId'];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $input)) $overrides[$barcode][$f] = $input[$f];
    }
    $overrides[$barcode]['updatedAt'] = date('c');
    saveOverrides($overrides);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
