<?php
/**
 * Continuous Availability Checker
 * Checks one batch of movies and tracks position
 * Call repeatedly to continuously update availability
 */

set_time_limit(30);
ini_set('max_execution_time', '30');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/polaris.php';

$batchSize = 20; // Check 20 movies per call (~15-20 seconds)

try {
    $moviesFile = __DIR__ . '/../data/movies_cache.json';
    $cacheFile = __DIR__ . '/../data/availability_cache.json';
    $progressFile = __DIR__ . '/../data/availability_progress.json';
    
    if (!file_exists($moviesFile)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Movies cache not found']);
        exit;
    }
    
    $allMovies = json_decode(file_get_contents($moviesFile), true);
    $totalMovies = count($allMovies);
    
    // Load progress tracker
    $progress = file_exists($progressFile) 
        ? json_decode(file_get_contents($progressFile), true) 
        : ['offset' => 0, 'cycleStarted' => time()];
    
    $currentOffset = $progress['offset'];
    
    // Load existing cache
    $cache = file_exists($cacheFile)
        ? json_decode(file_get_contents($cacheFile), true)
        : ['statuses' => [], 'timestamp' => time(), 'lastUpdated' => date('Y-m-d H:i:s')];
    
    // Get this batch
    $batch = array_slice($allMovies, $currentOffset, $batchSize);
    
    if (empty($batch)) {
        // End of list, start over
        $currentOffset = 0;
        $batch = array_slice($allMovies, 0, $batchSize);
        $progress['cycleStarted'] = time();
        error_log("Completed full cycle, starting over");
    }
    
    error_log("Continuous check: offset=$currentOffset, checking " . count($batch) . " movies");
    
    // Check this batch
    $api = new PolarisAPI();
    $batchStatuses = $api->bulkItemAvailability($batch, 5);
    
    if ($batchStatuses['ok']) {
        // Merge with existing cache
        $cache['statuses'] = array_merge($cache['statuses'], $batchStatuses['data']);
        $cache['lastUpdated'] = date('Y-m-d H:i:s');
        $cache['timestamp'] = time();
        
        // Save cache
        file_put_contents($cacheFile, json_encode($cache));
        
        // Update progress
        $progress['offset'] = $currentOffset + $batchSize;
        $progress['lastCheck'] = time();
        file_put_contents($progressFile, json_encode($progress));
        
        $percentComplete = round(($currentOffset / $totalMovies) * 100, 1);
        $cycleAge = time() - $progress['cycleStarted'];
        
        error_log("Updated cache: offset now " . $progress['offset'] . " ($percentComplete% of current cycle)");
        
        echo json_encode([
            'ok' => true,
            'checked' => count($batch),
            'offset' => $currentOffset,
            'nextOffset' => $progress['offset'],
            'total' => $totalMovies,
            'percentComplete' => $percentComplete,
            'totalCached' => count($cache['statuses']),
            'cycleAge' => $cycleAge,
            'continuing' => true
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'error' => 'Failed to check batch'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Exception in continuous checker: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
