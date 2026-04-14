<?php
/**
 * api/list.php
 *
 * Returns the list of DVDs.
 * Source priority:
 *   1. data/movies_list.json  (built by sync-movies.php from Polaris recordset)
 *   2. dvds.csv               (legacy fallback)
 */

require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$dataDir   = __DIR__ . '/../data';
$listFile  = "$dataDir/movies_list.json";
$csvPath   = __DIR__ . '/../dvds.csv';
$cachePath = "$dataDir/covers_cache.json";
$noCover   = defined('NO_COVER_PATH') ? NO_COVER_PATH : '/img/no-cover.svg';

// ── Try synced list first ──────────────────────────────────────────────────────
if (file_exists($listFile)) {
    $movies = json_decode(file_get_contents($listFile), true);

    if (is_array($movies) && !empty($movies) && isset($movies[0]['barcode'])) {
        // Apply cover cache for any movies missing a cover
        $coverCache = file_exists($cachePath)
            ? (json_decode(file_get_contents($cachePath), true) ?: [])
            : [];

        foreach ($movies as &$m) {
            if (empty($m['cover'])) {
                $m['cover'] = $coverCache[$m['barcode']] ?? $noCover;
            }
        }
        unset($m);

        echo json_encode([
            'ok'     => true,
            'items'  => $movies,
            'count'  => count($movies),
            'source' => 'polaris',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── Fallback: read from dvds.csv ───────────────────────────────────────────────
if (!file_exists($csvPath)) {
    echo json_encode(['ok' => false, 'error' => 'No movie data found. Please run a sync from the Staff Dashboard.', 'items' => []]);
    exit;
}

$fh = fopen($csvPath, 'r');
if ($fh === false) {
    echo json_encode(['ok' => false, 'error' => 'Unable to open dvds.csv', 'items' => []]);
    exit;
}

$coverCache = file_exists($cachePath)
    ? (json_decode(file_get_contents($cachePath), true) ?: [])
    : [];

$items = [];
while (($row = fgetcsv($fh)) !== false) {
    if (count($row) < 3) continue;
    $id      = trim($row[0]);
    $title   = trim($row[1]);
    $barcode = trim($row[2]);
    $rating  = isset($row[3]) ? trim($row[3]) : '';
    if ($barcode === '' || $title === '') continue;
    $cover = $coverCache[$barcode] ?? $noCover;
    $items[] = [
        'id'         => $id,
        'dvdId'      => $id,
        'title'      => $title,
        'barcode'    => $barcode,
        'rating'     => normalizeRating($rating),
        'callNumber' => null,
        'cover'      => $cover,
    ];
}
fclose($fh);

usort($items, fn($a, $b) => strcasecmp($a['title'], $b['title']));

echo json_encode([
    'ok'     => true,
    'items'  => $items,
    'count'  => count($items),
    'source' => 'csv',
], JSON_UNESCAPED_UNICODE);

function normalizeRating($rating) {
    $rating = strtoupper(trim($rating));
    $map = ['PG13'=>'PG-13','PG 13'=>'PG-13','NC17'=>'NC-17','NC 17'=>'NC-17','NR'=>'NR','NOT RATED'=>'NR','UNRATED'=>'NR'];
    return $map[$rating] ?? $rating;
}
