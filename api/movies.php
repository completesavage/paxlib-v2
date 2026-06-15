<?php
/**
 * Movies API
 *
 * GET              - List all movies (from Polaris recordset sync, or CSV fallback)
 * GET ?barcode=X   - Get single movie with availability from synced data
 * POST             - Save staff override for a movie
 * PUT ?action=refresh&barcode=X - Refresh cover (and bib ID) from Polaris item record
 */

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/movie_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataDir = __DIR__ . '/../data';
$listFile = "$dataDir/movies_list.json";
$syncMetaFile = "$dataDir/sync_meta.json";
$overridesFile = "$dataDir/movies_overrides.json";
$coversFile = "$dataDir/covers_cache.json";
$csvFile = __DIR__ . '/../dvds.csv';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function loadOverrides() {
    global $overridesFile;
    if (!file_exists($overridesFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($overridesFile), true);
    return $data ?: [];
}

function saveOverrides(array $overrides) {
    global $overridesFile;
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadFromCSV() {
    global $csvFile;
    $movies = [];

    if (!file_exists($csvFile)) {
        return $movies;
    }

    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        return $movies;
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 3) {
            continue;
        }

        $id = trim($row[0]);
        $title = trim($row[1]);
        $barcode = trim($row[2]);
        $rating = isset($row[3]) ? trim($row[3]) : '';

        if (empty($barcode) || empty($title)) {
            continue;
        }

        $movies[] = enrichMovieAvailability([
            'barcode' => $barcode,
            'dvdId' => $id,
            'title' => $title,
            'rating' => normalizeRating($rating),
            'cover' => null,
            'callNumber' => null,
            'bibRecordId' => null,
            'location' => 'DVD Section',
            'status' => null,
        ]);
    }

    fclose($handle);

    usort($movies, fn($a, $b) => strcasecmp($a['title'], $b['title']));
    return $movies;
}

function loadSyncMeta() {
    global $syncMetaFile;
    if (!file_exists($syncMetaFile)) {
        return null;
    }
    return json_decode(file_get_contents($syncMetaFile), true) ?: null;
}

function getMergedMovies() {
    global $listFile, $dataDir;
    $overrides = loadOverrides();
    $source = 'csv';
    $meta = loadSyncMeta();
    $coverMaps = buildCoverMaps($dataDir);

    if (file_exists($listFile)) {
        $raw = json_decode(file_get_contents($listFile), true);
        $movies = is_array($raw) ? $raw : [];
        $source = 'recordset';
    } else {
        $movies = loadFromCSV();
    }

    $merged = [];
    foreach ($movies as $movie) {
        if (empty($movie['barcode'])) {
            continue;
        }

        $barcode = $movie['barcode'];
        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
            $movie['barcode'] = $barcode;
        }

        $movie = applyCoverMapsToMovie($movie, $coverMaps);
        $movie = enrichMovieAvailability($movie);
        $movie = enrichMovieShelfNumber($movie);
        $movie = enrichMovieCatalogFlags($movie);
        $merged[] = $movie;
    }

    usort($merged, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    return [
        'movies' => $merged,
        'source' => $source,
        'meta' => $meta,
    ];
}

function buildListResponse(array $payload) {
    return [
        'ok' => true,
        'count' => count($payload['movies']),
        'source' => $payload['source'],
        'lastSync' => $payload['meta']['lastSync'] ?? null,
        'recordsetId' => $payload['meta']['source'] ?? null,
        'items' => $payload['movies'],
    ];
}

function loadPolaris() {
    static $loaded = false;
    if (!$loaded && file_exists(__DIR__ . '/polaris.php')) {
        require_once __DIR__ . '/polaris.php';
        $loaded = class_exists('PolarisAPI');
    }
    return $loaded;
}

function refreshMovieFromPolaris($barcode) {
    global $coversFile, $listFile;

    if (!loadPolaris()) {
        return ['ok' => false, 'error' => 'Polaris not available'];
    }

    $api = PolarisAPI::getInstance();
    $result = $api->getItemByBarcode($barcode);

    if (!$result['ok'] || empty($result['data'])) {
        return ['ok' => false, 'error' => 'Item not found in Polaris'];
    }

    $item = $result['data'];
    $bib = $item['BibInfo'] ?? [];
    $cover = coverFromBibInfo($bib);

    if (!isUsableCover($cover) && file_exists(__DIR__ . '/omdb.php')) {
        require_once __DIR__ . '/omdb.php';
        $payload = getMergedMovies();
        $title = '';
        foreach ($payload['movies'] as $m) {
            if (($m['barcode'] ?? '') === $barcode) {
                $title = $m['title'] ?? '';
                break;
            }
        }
        $omdbResult = fetchOmdbForMovie($barcode, $title);
        if ($omdbResult['ok']) {
            $poster = omdbPosterUrl($omdbResult['data']);
            if (isUsableCover($poster)) {
                $cover = $poster;
            }
        }
    }

    $noCover = noCoverPath();
    if (!$cover) {
        $cover = $noCover;
    }

    $covers = file_exists($coversFile)
        ? (json_decode(file_get_contents($coversFile), true) ?: [])
        : [];
    $covers[$barcode] = $cover;
    file_put_contents($coversFile, json_encode($covers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $overrides = loadOverrides();
    if (!isset($overrides[$barcode])) {
        $overrides[$barcode] = [];
    }
    if (isUsableCover($cover)) {
        $overrides[$barcode]['cover'] = $cover;
    }
    if (!empty($item['AssociatedBibRecordID'])) {
        $overrides[$barcode]['bibRecordId'] = (int)$item['AssociatedBibRecordID'];
    }
    saveOverrides($overrides);

    if (file_exists($listFile)) {
        $movies = json_decode(file_get_contents($listFile), true) ?: [];
        foreach ($movies as &$movie) {
            if (($movie['barcode'] ?? '') === $barcode) {
                if (isUsableCover($cover)) {
                    $movie['cover'] = $cover;
                }
                if (!empty($item['AssociatedBibRecordID'])) {
                    $movie['bibRecordId'] = (int)$item['AssociatedBibRecordID'];
                }
                break;
            }
        }
        unset($movie);
        file_put_contents($listFile, json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return [
        'ok' => true,
        'barcode' => $barcode,
        'cover' => $cover,
        'bibRecordId' => $overrides[$barcode]['bibRecordId'] ?? null,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;
    $payload = getMergedMovies();

    if ($barcode) {
        $movie = null;
        foreach ($payload['movies'] as $m) {
            if ($m['barcode'] === $barcode) {
                $movie = $m;
                break;
            }
        }

        if (!$movie) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Movie not found']);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'source' => $payload['source'],
            'lastSync' => $payload['meta']['lastSync'] ?? null,
            'movie' => $movie,
        ]);
        exit;
    }

    echo json_encode(buildListResponse($payload));
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['barcode'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode']);
        exit;
    }

    $barcode = trim($input['barcode']);
    $overrides = loadOverrides();
    if (!isset($overrides[$barcode])) {
        $overrides[$barcode] = [];
    }

    foreach (['title', 'rating', 'callNumber', 'location', 'description', 'cover'] as $field) {
        if (array_key_exists($field, $input)) {
            $overrides[$barcode][$field] = $input[$field];
        }
    }

    saveOverrides($overrides);

    echo json_encode(['ok' => true, 'barcode' => $barcode]);
    exit;
}

if ($method === 'PUT') {
    $action = $_GET['action'] ?? '';
    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

    if ($action === 'refresh' && $barcode !== '') {
        echo json_encode(refreshMovieFromPolaris($barcode));
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
