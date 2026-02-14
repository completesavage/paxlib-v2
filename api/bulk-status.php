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
        
        // Check if statuses is a sequential numeric array (wrong format)
        // Barcodes stored as integers are OK, but [0,1,2,3] is wrong
        if (isset($cachedData['statuses']) && is_array($cachedData['statuses']) && !empty($cachedData['statuses'])) {
            $keys = array_keys($cachedData['statuses']);
            // Check if it's a sequential array starting at 0
            if ($keys === range(0, count($keys) - 1)) {
                // Wrong format - it's a sequential numeric array [0,1,2,3...]
                error_log("WARNING: Cache has wrong format (sequential numeric array), needs rebuild");
                // Delete bad cache and start fresh
                unlink($cacheFile);
                
                echo json_encode([
                    'ok' => true,
                    'statuses' => new stdClass(),
                    'checked' => 0,
                    'timestamp' => time(),
                    'cached' => false,
                    'message' => 'Cache format error, rebuilding...'
                ]);
                exit;
            }
        }
        
        error_log("Cache found, age: {$cacheAge}s");
        
        // If cache is less than 10 minutes old, use it
        if ($cacheAge < $cacheMaxAge) {
            $statuses = $cachedData['statuses'] ?? [];
            // Ensure empty array becomes empty object for JSON
            if (empty($statuses)) {
                $statuses = new stdClass();
            }
            
            echo json_encode([
                'ok' => true,
                'statuses' => $statuses,
                'checked' => is_array($cachedData['statuses'] ?? []) ? count($cachedData['statuses'] ?? []) : 0,
                'timestamp' => $cachedData['timestamp'] ?? time(),
                'cached' => true,
                'cacheAge' => $cacheAge,
                'lastUpdated' => $cachedData['lastUpdated'] ?? null
            ]);
            exit;
        } else {
            // Cache is stale, trigger background refresh but return stale data
            error_log("Cache is stale, returning old data and triggering refresh");
            
            $statuses = $cachedData['statuses'] ?? [];
            // Ensure empty array becomes empty object for JSON
            if (empty($statuses)) {
                $statuses = new stdClass();
            }
            
            echo json_encode([
                'ok' => true,
                'statuses' => $statuses,
                'checked' => is_array($cachedData['statuses'] ?? []) ? count($cachedData['statuses'] ?? []) : 0,
                'timestamp' => $cachedData['timestamp'] ?? time(),
                'cached' => true,
                'stale' => true,
                'cacheAge' => $cacheAge,
                'lastUpdated' => $cachedData['lastUpdated'] ?? null,
                'refreshing' => true
            ]);
            exit;
        }
    }
    
    // No cache exists, return empty object (not array) and trigger refresh
    error_log("No cache found, returning empty");
    
    echo json_encode([
        'ok' => true,
        'statuses' => new stdClass(), // Empty object, not array
        'checked' => 0,
        'timestamp' => time(),
        'cached' => false,
        'message' => 'No cache, will be built by continuous checker'
    ]);
    
} catch (Exception $e) {
    error_log("Exception in bulk-status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
