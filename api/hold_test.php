<?php
/**
 * hold_test.php
 * 
 * Test Polaris Hold API
 * Works via GET (browser) or POST JSON
 * 
 * Usage:
 * GET:  hold_test.php?patronBarcode=123456&bibRecordId=7890
 * POST: JSON {"patronBarcode":"123456","bibRecordId":"7890","itemBarcode":"optional"}
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/polaris.php';

// --- Get parameters from GET for browser testing ---
$patronBarcode = $_GET['patronBarcode'] ?? null;
$bibRecordId   = $_GET['bibRecordId'] ?? null;
$itemBarcode   = $_GET['itemBarcode'] ?? null;

// Override with POST JSON if present
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $patronBarcode = $input['patronBarcode'] ?? $patronBarcode;
    $bibRecordId   = $input['bibRecordId'] ?? $bibRecordId;
    $itemBarcode   = $input['itemBarcode'] ?? $itemBarcode;
}

if (empty($patronBarcode)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Missing patronBarcode']);
    exit;
}

try {
    $api = new PolarisAPI();

    // Lookup item if bibRecordId not provided
    if (empty($bibRecordId) && !empty($itemBarcode)) {
        $itemResult = $api->getItemByBarcode($itemBarcode);
        if ($itemResult['ok'] && !empty($itemResult['data']['AssociatedBibRecordID'])) {
            $bibRecordId = $itemResult['data']['AssociatedBibRecordID'];
        } else {
            http_response_code(404);
            echo json_encode([
                'ok'=>false,
                'error'=>'Item not found or no associated BibRecordID',
                'details'=>$itemResult
            ]);
            exit;
        }
    }

    if (empty($bibRecordId)) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Missing bibRecordId']);
        exit;
    }

    // Lookup patron ID
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    if (!$patronResult['ok'] || empty($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode([
            'ok'=>false,
            'error'=>'Patron not found',
            'details'=>$patronResult
        ]);
        exit;
    }
    $patronId = $patronResult['data']['PatronID'];

    // Default pickup branch (from your settings)
    $pickupBranchId = 699;

    // Place hold
    $holdResult = $api->placeLocalHold($patronId, $bibRecordId, $pickupBranchId);

    // Return hold result
    echo json_encode($holdResult);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok'=>false,
        'error'=>'Server error: '.$e->getMessage()
    ]);
}
