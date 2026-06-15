<?php
/**
 * Batch cover sync — fetches Syndetics cover URLs from Polaris item records.
 *
 * GET  - Status (how many covers cached / missing)
 * POST - Process next batch of movies missing covers
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

require_once __DIR__ . '/movie_helpers.php';
require_once __DIR__ . '/polaris.php';
require_once __DIR__ . '/omdb.php';

$dataDir = __DIR__ . '/../data';
$listFile = "$dataDir/movies_list.json";
$coversFile = "$dataDir/covers_cache.json";
$progressFile = "$dataDir/covers_sync_progress.json";
$batchSize = 25;

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function loadMoviesList($listFile) {
    if (!file_exists($listFile)) {
        return [];
    }
    $raw = json_decode(file_get_contents($listFile), true);
    return is_array($raw) ? $raw : [];
}

function saveMoviesList($listFile, array $movies) {
    file_put_contents($listFile, json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function countCoverStats(array $movies, array $coverCache) {
    $total = count($movies);
    $withCover = 0;

    foreach ($movies as $movie) {
        $bc = $movie['barcode'] ?? '';
        $cover = $movie['cover'] ?? ($coverCache[$bc] ?? null);
        if (isUsableCover($cover)) {
            $withCover++;
        }
    }

    return [
        'total' => $total,
        'withCover' => $withCover,
        'missing' => max(0, $total - $withCover),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $movies = loadMoviesList($listFile);
    $coverCache = loadCoverCacheFile($coversFile);
    $stats = countCoverStats($movies, $coverCache);
    $progress = file_exists($progressFile)
        ? (json_decode(file_get_contents($progressFile), true) ?: [])
        : [];

    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'progress' => $progress,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $movies = loadMoviesList($listFile);
    if (empty($movies)) {
        echo json_encode(['ok' => false, 'error' => 'No movies list found. Run sync-movies first.']);
        exit;
    }

    $coverCache = loadCoverCacheFile($coversFile);
    $coverMaps = buildCoverMaps($dataDir);
    $missing = [];

    foreach ($movies as $idx => $movie) {
        $bc = $movie['barcode'] ?? '';
        if ($bc === '') {
            continue;
        }
        $cover = resolveMovieCover($bc, $movie, $coverMaps);
        if (!isUsableCover($cover)) {
            $missing[] = ['index' => $idx, 'barcode' => $bc, 'title' => $movie['title'] ?? ''];
        }
    }

    if (empty($missing)) {
        if (file_exists($progressFile)) {
            unlink($progressFile);
        }
        echo json_encode([
            'ok' => true,
            'done' => true,
            'processed' => 0,
            'updated' => 0,
            'remaining' => 0,
            'stats' => countCoverStats($movies, $coverCache),
        ]);
        exit;
    }

    $api = PolarisAPI::getInstance();
    $batch = array_slice($missing, 0, $batchSize);
    $updated = 0;
    $noCover = noCoverPath();

    foreach ($batch as $entry) {
        $idx = $entry['index'];
        $barcode = $entry['barcode'];
        $result = $api->getItemByBarcode($barcode);

        $cover = $noCover;
        if ($result['ok'] && !empty($result['data'])) {
            $bib = $result['data']['BibInfo'] ?? [];
            $found = coverFromBibInfo($bib);
            if (isUsableCover($found)) {
                $cover = $found;
                $updated++;
            }
            if (!empty($result['data']['AssociatedBibRecordID']) && empty($movies[$idx]['bibRecordId'])) {
                $movies[$idx]['bibRecordId'] = (int)$result['data']['AssociatedBibRecordID'];
            }
        }

        if (!isUsableCover($cover)) {
            $title = $movies[$idx]['title'] ?? $entry['title'] ?? '';
            $omdbResult = fetchOmdbForMovie($barcode, $title);
            if ($omdbResult['ok']) {
                $poster = omdbPosterUrl($omdbResult['data']);
                if (isUsableCover($poster)) {
                    $cover = $poster;
                    $updated++;
                }
            }
        }

        $coverCache[$barcode] = $cover;
        if (isUsableCover($cover)) {
            $movies[$idx]['cover'] = $cover;
        }

        usleep(100000);
    }

    file_put_contents($coversFile, json_encode($coverCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    saveMoviesList($listFile, $movies);

    $remaining = max(0, count($missing) - count($batch));
    file_put_contents($progressFile, json_encode([
        'lastRun' => date('c'),
        'remaining' => $remaining,
        'updatedLastBatch' => $updated,
    ], JSON_PRETTY_PRINT));

    echo json_encode([
        'ok' => true,
        'done' => $remaining === 0,
        'processed' => count($batch),
        'updated' => $updated,
        'remaining' => $remaining,
        'stats' => countCoverStats($movies, $coverCache),
    ]);
} catch (Exception $e) {
    error_log('sync-covers exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
