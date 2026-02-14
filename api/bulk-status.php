<?php
/**
 * Bulk Item Status API
 * GET - Check status for multiple items efficiently
 * Query params: ?barcodes=123,456,789 or ?all=true
 */

// Increase execution time for this script
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', '300');

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

$all = isset($_GET['all']) && $_GET['all'] === 'true';
$barcodes = isset($_GET['barcodes']) ? explode(',', $_GET['barcodes']) : [];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null; // Optional limit for testing

if (!$all && empty($barcodes)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing barcodes or all parameter']);
    exit;
}

try {
    $cacheFile = __DIR__ . '/../data/availability_cache.json';
    $cacheMaxAge = 600; // 10 minutes
    
    // Always try to use cache first
    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        
        error_log("Cache found, age: {$cacheAge}s, complete: " . ($cachedData['complete'] ? 'yes' : 'no'));
        
        // If cache is less than 10 minutes old, use it
        if ($cacheAge < $cacheMaxAge) {
            echo json_encode([
                'ok' => true,
                'statuses' => $cachedData['statuses'],
                'checked' => count($cachedData['statuses']),
                'timestamp' => $cachedData['timestamp'],
                'cached' => true,
                'cacheAge' => $cacheAge,
                'lastUpdated' => $cachedData['lastUpdated'] ?? null
            ]);
            exit;
        } else {
            // Cache is stale, trigger background refresh but return stale data
            error_log("Cache is stale, returning old data and triggering refresh");
            
            // Trigger refresh in background (non-blocking)
            // Use file_get_contents with async context
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1, // Don't wait for response
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);
            @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/api/refresh-availability.php', false, $context);
            
            echo json_encode([
                'ok' => true,
                'statuses' => $cachedData['statuses'],
                'checked' => count($cachedData['statuses']),
                'timestamp' => $cachedData['timestamp'],
                'cached' => true,
                'stale' => true,
                'cacheAge' => $cacheAge,
                'lastUpdated' => $cachedData['lastUpdated'] ?? null,
                'refreshing' => true
            ]);
            exit;
        }
    }
    
    // No cache exists, return empty and trigger refresh
    error_log("No cache found, triggering initial refresh");
    
    // Trigger refresh
    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 1,
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($opts);
    @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/api/refresh-availability.php', false, $context);
    
    echo json_encode([
        'ok' => true,
        'statuses' => [],
        'checked' => 0,
        'timestamp' => time(),
        'cached' => false,
        'refreshing' => true,
        'message' => 'Initial refresh started'
    ]);
    
} catch (Exception $e) {
    error_log("Exception in bulk-status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
