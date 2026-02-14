<?php
/**
 * Availability Refresh Service
 * Checks all movie availability and caches results
 * Should be called periodically (every 10 minutes) via cron or browser ping
 */

set_time_limit(600); // 10 minutes
ini_set('max_execution_time', '600');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/polaris.php';

$cacheFile = __DIR__ . '/../data/availability_cache.json';
$lockFile = __DIR__ . '/../data/availability_refresh.lock';

// Check if refresh is already running
if (file_exists($lockFile)) {
    $lockAge = time() - filemtime($lockFile);
    if ($lockAge < 600) { // Lock is less than 10 minutes old
        echo json_encode([
            'ok' => true,
            'message' => 'Refresh already in progress',
            'lockAge' => $lockAge
        ]);
        exit;
    } else {
        // Stale lock, remove it
        unlink($lockFile);
    }
}

// Create lock file
file_put_contents($lockFile, time());

try {
    $moviesFile = __DIR__ . '/../data/movies_cache.json';
    if (!file_exists($moviesFile)) {
        unlink($lockFile);
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Movies cache not found']);
        exit;
    }
    
    $allMovies = json_decode(file_get_contents($moviesFile), true);
    $totalMovies = count($allMovies);
    
    error_log("Starting full availability refresh for $totalMovies movies");
    
    $api = new PolarisAPI();
    $allStatuses = [];
    
    // Process in batches of 50
    $batchSize = 50;
    $offset = 0;
    
    while ($offset < $totalMovies) {
        $batch = array_slice($allMovies, $offset, $batchSize);
        error_log("Processing batch: offset=$offset, size=" . count($batch));
        
        $statuses = $api->bulkItemAvailability($batch, 10);
        
        if ($statuses['ok']) {
            $allStatuses = array_merge($allStatuses, $statuses['data']);
        }
        
        $offset += $batchSize;
        
        // Save partial progress
        $cacheData = [
            'statuses' => $allStatuses,
            'timestamp' => time(),
            'lastUpdated' => date('Y-m-d H:i:s'),
            'totalChecked' => count($allStatuses),
            'totalMovies' => $totalMovies,
            'complete' => false
        ];
        file_put_contents($cacheFile, json_encode($cacheData));
    }
    
    // Mark as complete
    $cacheData['complete'] = true;
    file_put_contents($cacheFile, json_encode($cacheData));
    
    error_log("Completed full availability refresh: " . count($allStatuses) . " items");
    
    // Remove lock
    unlink($lockFile);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Refresh completed',
        'checked' => count($allStatuses),
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Exception in refresh service: " . $e->getMessage());
    if (file_exists($lockFile)) unlink($lockFile);
    
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
