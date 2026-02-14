
<?php
/**
 * Reset Availability Cache
 * Deletes cache files and restarts the checking process
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cacheFile = __DIR__ . '/../data/availability_cache.json';
$progressFile = __DIR__ . '/../data/availability_progress.json';
$lockFile = __DIR__ . '/../data/availability_refresh.lock';

$deleted = [];

if (file_exists($cacheFile)) {
    unlink($cacheFile);
    $deleted[] = 'availability_cache.json';
}

if (file_exists($progressFile)) {
    unlink($progressFile);
    $deleted[] = 'availability_progress.json';
}

if (file_exists($lockFile)) {
    unlink($lockFile);
    $deleted[] = 'availability_refresh.lock';
}

echo json_encode([
    'ok' => true,
    'message' => 'Cache reset successfully',
    'deleted' => $deleted,
    'instruction' => 'Reload the kiosk page to start fresh availability checking'
]);
