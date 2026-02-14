<?php
/**
 * Progressive Availability Check
 * Checks a batch of movies and returns results
 * Query params: ?offset=0&limit=50
 */

set_time_limit(60);
ini_set('max_execution_time', '60');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/polaris.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

try {
    $moviesFile = __DIR__ . '/../data/movies_cache.json';
    if (!file_exists($moviesFile)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Movies cache not found']);
        exit;
    }
    
    $allMovies = json_decode(file_get_contents($moviesFile), true);
    $totalMovies = count($allMovies);
    
    // Get this batch
    $batch = array_slice($allMovies, $offset, $limit);
    
    error_log("Progressive check: offset=$offset, limit=$limit, batch size=" . count($batch));
    
    if (empty($batch)) {
        echo json_encode([
            'ok' => true,
            'statuses' => [],
            'offset' => $offset,
            'limit' => $limit,
            'total' => $totalMovies,
            'hasMore' => false
        ]);
        exit;
    }
    
    // Check this batch
    $api = new PolarisAPI();
    $statuses = $api->bulkItemAvailability($batch, 10);
    
    if (!$statuses['ok']) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Failed to check statuses'
        ]);
        exit;
    }
    
    $nextOffset = $offset + $limit;
    $hasMore = $nextOffset < $totalMovies;
    
    echo json_encode([
        'ok' => true,
        'statuses' => $statuses['data'],
        'offset' => $offset,
        'limit' => $limit,
        'total' => $totalMovies,
        'hasMore' => $hasMore,
        'nextOffset' => $hasMore ? $nextOffset : null,
        'progress' => round(min(100, ($nextOffset / $totalMovies) * 100), 1)
    ]);
    
} catch (Exception $e) {
    error_log("Exception in progressive-availability: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
