<?php
/**
 * Cancel Hold API
 * DELETE - Cancel a hold request
 * Body: { holdRequestId, patronBarcode }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/polaris.php';

// Enable error logging
error_log("Cancel hold API called at " . date('Y-m-d H:i:s'));

$input = json_decode(file_get_contents('php://input'), true);
error_log("Cancel hold input: " . print_r($input, true));

$holdRequestId = $input['holdRequestId'] ?? null;
$patronBarcode = $input['patronBarcode'] ?? null;

error_log("Hold Request ID: $holdRequestId, Patron Barcode: $patronBarcode");

if (empty($holdRequestId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing hold request ID']);
    exit;
}

if (empty($patronBarcode)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing patron barcode']);
    exit;
}

try {
    $api = new PolarisAPI();
    
    // Verify patron owns this hold
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Patron not found']);
        exit;
    }
    
    $patronId = $patronResult['data']['PatronID'];
    
    // Cancel the hold
    $result = $api->cancelHold($holdRequestId, $patronId);
    error_log("Cancel hold result: " . print_r($result, true));
    
    if ($result['ok']) {
        error_log("Hold cancelled successfully");
        echo json_encode([
            'ok' => true,
            'message' => 'Hold cancelled successfully'
        ]);
    } else {
        error_log("Cancel hold failed: " . ($result['error'] ?? 'Unknown'));
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => $result['error'] ?? 'Failed to cancel hold',
            'details' => $result
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
