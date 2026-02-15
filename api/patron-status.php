<?php
/**
 * Patron Status API
 * GET - Get patron's fines and checkout status
 * Query params: ?barcode=123456789
 */

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

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

$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;

if (!$barcode) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing barcode parameter']);
    exit;
}

require_once __DIR__ . '/polaris.php';

try {
    $api = PolarisAPI::getInstance();
    
    // Get patron info
    $patronResult = $api->getPatronByBarcode($barcode);
    
    if (!$patronResult['ok']) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Patron not found'
        ]);
        exit;
    }
    
    $patronData = $patronResult['data'];
    $patronId = $patronData['PatronID'];
    
    // Get fines
    $fines = $api->getPatronFines($patronId);
    
    // Get checkouts
    $checkouts = $api->getPatronCheckouts($patronId);
    
    // Determine if patron can checkout
    $canCheckout = true;
    $blockReasons = [];
    
    if ($fines['ok'] && !$fines['canCheckout']) {
        $canCheckout = false;
        $blockReasons[] = 'Fines over $5.00 - Please visit front desk';
    }
    
    if ($checkouts['ok'] && !$checkouts['canCheckoutDVD']) {
        $canCheckout = false;
        $blockReasons[] = 'DVD checkout limit reached (5 DVDs)';
    }
    
    echo json_encode([
        'ok' => true,
        'patronId' => $patronId,
        'patronName' => $patronData['DisplayName'] ?? 'Unknown',
        'fines' => [
            'total' => $fines['totalOwed'] ?? 0,
            'canCheckout' => $fines['canCheckout'] ?? true
        ],
        'checkouts' => [
            'total' => $checkouts['totalCheckouts'] ?? 0,
            'dvds' => $checkouts['dvdCheckouts'] ?? 0,
            'canCheckoutDVD' => $checkouts['canCheckoutDVD'] ?? true
        ],
        'canCheckout' => $canCheckout,
        'blockReasons' => $blockReasons
    ]);
    
} catch (Exception $e) {
    error_log("Exception in patron-status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
