<?php
/**
 * Bulk Item Status API
 * GET - Check status for multiple items efficiently
 * Query params: ?barcodes=123,456,789 or ?all=true
 */

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

if (!$all && empty($barcodes)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing barcodes or all parameter']);
    exit;
}

try {
    $api = new PolarisAPI();
    
    // If "all" is requested, load from movies cache
    if ($all) {
        $moviesFile = __DIR__ . '/../data/movies_cache.json';
        if (!file_exists($moviesFile)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Movies cache not found']);
            exit;
        }
        
        $moviesData = json_decode(file_get_contents($moviesFile), true);
        
        // Use the new efficient bib availability method
        $statuses = $api->bulkItemAvailability($moviesData);
    } else {
        // For specific barcodes, use the old method
        $statuses = $api->bulkItemStatus($barcodes);
    }
    
    if (!$statuses['ok']) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Failed to check statuses',
            'details' => $statuses
        ]);
        exit;
    }
    
    echo json_encode([
        'ok' => true,
        'statuses' => $statuses['data'],
        'checked' => count($statuses['data']),
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
