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
    
    error_log("patron-status: getPatronByBarcode result: " . print_r($patronResult, true));
    
    if (!$patronResult['ok'] || !isset($patronResult['data'])) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Patron not found'
        ]);
        exit;
    }
    
    $patronData = $patronResult['data'];
    $patronId = $patronData['PatronID'];
    
    // Get registration data for name
    $reg = $patronData['Registration'] ?? [];
    $firstName = $reg['NameFirst'] ?? '';
    $lastName = $reg['NameLast'] ?? '';
    $displayName = trim("$firstName $lastName");
    if (empty($displayName)) {
        $displayName = $reg['PatronFullName'] ?? $patronData['Barcode'] ?? $barcode;
    }
    
    // Get fines from the patron data itself
    $chargesAmount = floatval($patronData['ChargesAmount'] ?? 0);
    $creditsAmount = floatval($patronData['CreditsAmount'] ?? 0);
    $totalOwed = $chargesAmount - $creditsAmount;
    
    error_log("patron-status: Fines - Charges: $chargesAmount, Credits: $creditsAmount, Total: $totalOwed");
    
    // Get checkouts (will still make API call for this)
    $checkouts = $api->getPatronCheckouts($patronId);
    
    error_log("patron-status: Checkouts result: " . print_r($checkouts, true));
    
    // Determine if patron can checkout
    $canCheckout = true;
    $blockReasons = [];
    
    if ($totalOwed > 5.00) {
        $canCheckout = false;
        $blockReasons[] = 'Fines over $5.00 - Please visit front desk';
    }
    
    $dvdCount = 0;
    if ($checkouts['ok']) {
        $dvdCount = $checkouts['dvdCheckouts'];
        if ($dvdCount >= 5) {
            $canCheckout = false;
            $blockReasons[] = 'DVD checkout limit reached (5 DVDs)';
        }
    }
    
    echo json_encode([
        'ok' => true,
        'patronId' => $patronId,
        'patronName' => $displayName,
        'fines' => [
            'total' => $totalOwed,
            'canCheckout' => $totalOwed <= 5.00
        ],
        'checkouts' => [
            'total' => $checkouts['totalCheckouts'] ?? 0,
            'dvds' => $dvdCount,
            'canCheckoutDVD' => $dvdCount < 5
        ],
        'canCheckout' => $canCheckout,
        'blockReasons' => $blockReasons
    ]);
    
} catch (Exception $e) {
    error_log("Exception in patron-status: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
