<?php
/**
 * Local verification for recordset movie pipeline (no Polaris required).
 * Run: php tests/test_movie_pipeline.php
 */

require __DIR__ . '/../api/movie_helpers.php';

$failures = 0;

function assert_true($label, $cond) {
    global $failures;
    if (!$cond) {
        echo "FAIL: $label\n";
        $failures++;
        return;
    }
    echo "OK: $label\n";
}

assert_true('In is available', isItemAvailable('In') === true);
assert_true('Out is not available', isItemAvailable('Out') === false);
assert_true('In Transit is not available', isItemAvailable('In Transit') === false);
assert_true('PG13 rating from call number', extractRatingFromCallNumber('PG13 DVD 86') === 'PG-13');

$enriched = enrichMovieAvailability(['status' => 'In', 'rating' => 'PG13']);
assert_true('enrich sets available', $enriched['available'] === true);
assert_true('enrich normalizes rating', $enriched['rating'] === 'PG-13');

$dataDir = __DIR__ . '/../data';
$listFile = "$dataDir/movies_list.json";
$metaFile = "$dataDir/sync_meta.json";
$backupList = null;
$backupMeta = null;

if (file_exists($listFile)) {
    $backupList = file_get_contents($listFile);
}
if (file_exists($metaFile)) {
    $backupMeta = file_get_contents($metaFile);
}

$sample = [
    [
        'dvdId' => '1',
        'title' => 'Spider-Man',
        'barcode' => '31783000235766',
        'rating' => 'PG-13',
        'callNumber' => 'PG13 DVD 86',
        'status' => 'In',
        'available' => true,
        'cover' => null,
        'location' => 'DVD Section',
    ],
    [
        'dvdId' => '2',
        'title' => 'Ben-Hur',
        'barcode' => '31783000390900',
        'rating' => 'G',
        'callNumber' => 'G DVD 7',
        'status' => 'Out',
        'available' => false,
        'cover' => null,
        'location' => 'DVD Section',
    ],
];

file_put_contents($listFile, json_encode($sample, JSON_PRETTY_PRINT));
file_put_contents($metaFile, json_encode([
    'lastSync' => date('c'),
    'count' => 2,
    'source' => 'recordset/473530',
], JSON_PRETTY_PRINT));

// Invoke movies API via CLI simulation
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_GET['barcode']);
ob_start();
include __DIR__ . '/../api/movies.php';
$out = ob_get_clean();
$data = json_decode($out, true);

assert_true('movies API returns ok', !empty($data['ok']));
assert_true('source is recordset', ($data['source'] ?? '') === 'recordset');
assert_true('count is 2', ($data['count'] ?? 0) === 2);
assert_true('one available', count(array_filter($data['items'], fn($m) => $m['available'])) === 1);

if ($backupList !== null) {
    file_put_contents($listFile, $backupList);
} elseif (file_exists($listFile)) {
    unlink($listFile);
}

if ($backupMeta !== null) {
    file_put_contents($metaFile, $backupMeta);
} elseif (file_exists($metaFile)) {
    unlink($metaFile);
}

if ($failures > 0) {
    echo "\n$failures test(s) failed.\n";
    exit(1);
}

echo "\nAll tests passed.\n";
