<?php
// api/list.php
require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// path to your csv
$csvPath = __DIR__ . '/../dvds.csv';

if (!file_exists($csvPath)) {
    echo json_encode([
        'ok'    => false,
        'error' => 'dvds.csv not found',
        'items' => []
    ]);
    exit;
}

$fh    = fopen($csvPath, 'r');
$items = [];

if ($fh === false) {
    echo json_encode([
        'ok'    => false,
        'error' => 'unable to open dvds.csv',
        'items' => []
    ]);
    exit;
}

while (($row = fgetcsv($fh)) !== false) {
    // expected columns:
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

    $items[] = [
        'id'         => $id,
        'title'      => $title,
        'barcode'    => $barcode,
        'rating'     => $rating,
        // these can be filled later from leap if you want
        'callNumber' => null,
        'cover'      => defined('NO_COVER_PATH') ? NO_COVER_PATH : null,
    ];
}

fclose($fh);

// sort a–z by title so it is stable
usort($items, function ($a, $b) {
    return strcasecmp($a['title'], $b['title']);
});

echo json_encode([
    'ok'    => true,
    'items' => $items,
], JSON_UNESCAPED_UNICODE);
