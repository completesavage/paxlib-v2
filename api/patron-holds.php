<?php
/**
 * Patron Holds API
 * GET - Get all holds for a patron
 * Query params: ?patronBarcode=xxx
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

$patronBarcode = $_GET['patronBarcode'] ?? null;

if (empty($patronBarcode)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing patron barcode']);
    exit;
}

try {
    $api = new PolarisAPI();
    
    // Get patron ID from barcode
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Patron not found']);
        exit;
    }
    
    $patronId = $patronResult['data']['PatronID'];
    
    // Get holds for this patron
    $holds = $api->getPatronHolds($patronId);
    
    if (!$holds['ok']) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Failed to retrieve holds',
            'details' => $holds
        ]);
        exit;
    }
    
    echo json_encode([
        'ok' => true,
        'holds' => $holds['data']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
