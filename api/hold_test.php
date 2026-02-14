<?php
/**
 * Test Hold API
 * Usage:
 * 1. Visit in browser:
 *    hold_test.php?patronBarcode=123456&bibRecordId=7890
 * 2. Or POST JSON same as original hold.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/polaris.php';

// --- Get parameters from GET (for quick testing) ---
$patronBarcode = $_GET['patronBarcode'] ?? null;
$bibRecordId   = $_GET['bibRecordId'] ?? null;
$itemBarcode   = $_GET['itemBarcode'] ?? null;

// If POST JSON is used, override
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
        if ($itemResult['ok'] && isset($itemResult['data']['AssociatedBibRecordID'])) {
            $bibRecordId = $itemResult['data']['AssociatedBibRecordID'];
        } else {
            http_response_code(404);
            echo json_encode(['ok'=>false,'error'=>'Item not found','details'=>$itemResult]);
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
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Patron not found','details'=>$patronResult]);
        exit;
    }
    $patronId = $patronResult['data']['PatronID'];

    // Default pickup branch
    $pickupBranchId = 699; // Replace with your branch ID if different

    // Place hold
    $result = $api->placeLocalHold($patronId, $bibRecordId, $pickupBranchId);

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
}
