<?php
/**
 * api/list.php
 * 
 * Returns the list of DVDs from the CSV file.
 * Optionally loads cached cover URLs if available.
 */

require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Path to your CSV and cache file
$csvPath = __DIR__ . '/../dvds.csv';
$cachePath = __DIR__ . '/../data/covers_cache.json';

if (!file_exists($csvPath)) {
    echo json_encode([
        'ok'    => false,
        'error' => 'dvds.csv not found',
        'items' => []
    ]);
    exit;
}

$fh = fopen($csvPath, 'r');
$items = [];

if ($fh === false) {
    echo json_encode([
        'ok'    => false,
        'error' => 'unable to open dvds.csv',
        'items' => []
    ]);
    exit;
}

// Load cover cache if exists
$coverCache = [];
if (file_exists($cachePath)) {
    $cacheJson = file_get_contents($cachePath);
    $coverCache = json_decode($cacheJson, true) ?: [];
}

$noCover = defined('NO_COVER_PATH') ? NO_COVER_PATH : '/img/no-cover.svg';

while (($row = fgetcsv($fh)) !== false) {
    // Expected columns:
    // 0 = id
    // 1 = title
    // 2 = barcode
    // 3 = rating (optional)
    // 4 = sort title (unused)
    // 5 = short sort title (unused)

    if (count($row) < 3) {
        continue;
    }

    $id      = trim($row[0]);
    $title   = trim($row[1]);
    $barcode = trim($row[2]);
    $rating  = isset($row[3]) ? trim($row[3]) : '';

    if ($barcode === '' || $title === '') {
        continue;
    }

    // Check cache for cover URL
    $cover = isset($coverCache[$barcode]) ? $coverCache[$barcode] : $noCover;

    $items[] = [
        'id'         => $id,
        'title'      => $title,
        'barcode'    => $barcode,
        'rating'     => normalizeRating($rating),
        'callNumber' => null,
        'cover'      => $cover,
    ];
}

fclose($fh);

// Sort A-Z by title (stable sort)
usort($items, function ($a, $b) {
    return strcasecmp($a['title'], $b['title']);
});

echo json_encode([
    'ok'    => true,
    'items' => $items,
    'count' => count($items)
], JSON_UNESCAPED_UNICODE);

/**
 * Normalize rating strings to consistent format
 */
function normalizeRating($rating) {
    $rating = strtoupper(trim($rating));
    
    // Map common variations
    $map = [
        'PG13' => 'PG-13',
        'PG 13' => 'PG-13',
        'NC17' => 'NC-17',
        'NC 17' => 'NC-17',
        'NR' => 'NR',
        'NOT RATED' => 'NR',
        'UNRATED' => 'NR',
    ];
    
    return isset($map[$rating]) ? $map[$rating] : $rating;
}
