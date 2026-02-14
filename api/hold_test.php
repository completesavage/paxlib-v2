<?php
/**
 * Fully working Hold Test API
 * Usage:
 * 1. GET in browser:
 *    hold_test.php?patronBarcode=123456&bibRecordId=7890
 *    OR
 *    hold_test.php?patronBarcode=123456&itemRecordID=9876
 * 2. Optional pickup branch:
 *    &pickupBranchID=699
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/polaris.php';

// --- Get parameters from GET (quick test) ---
$patronBarcode = $_GET['patronBarcode'] ?? null;
$bibRecordId   = $_GET['bibRecordId'] ?? null;
$itemRecordID  = $_GET['itemRecordID'] ?? null;
$pickupBranchID = $_GET['pickupBranchID'] ?? 699; // default to your branch

// Override with POST JSON if provided
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $patronBarcode = $input['patronBarcode'] ?? $patronBarcode;
    $bibRecordId   = $input['bibRecordId'] ?? $bibRecordId;
    $itemRecordID  = $input['itemRecordID'] ?? $itemRecordID;
    $pickupBranchID = $input['pickupBranchID'] ?? $pickupBranchID;
}

// Validate patronBarcode
if (empty($patronBarcode)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Missing patronBarcode']);
    exit;
}

try {
    $api = new PolarisAPI();

    // Lookup patron ID
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Patron not found','details'=>$patronResult]);
        exit;
    }
    $patronID = $patronResult['data']['PatronID'];

    // If ItemRecordID not provided, look up holdable item via BibRecordID
    if (empty($itemRecordID)) {
        if (empty($bibRecordId)) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'error'=>'Missing bibRecordId and itemRecordID']);
            exit;
        }

        // Get all items for the bib
        $itemsResult = $api->getItemsByBib($bibRecordId);
        if (!$itemsResult['ok'] || empty($itemsResult['data'])) {
            http_response_code(404);
            echo json_encode(['ok'=>false,'error'=>'No items found for this bib','details'=>$itemsResult]);
            exit;
        }

        // Find first holdable item
        foreach ($itemsResult['data'] as $item) {
            if (!empty($item['Holdable'])) {
                $itemRecordID = $item['ItemRecordID'];
                break;
            }
        }

        if (empty($itemRecordID)) {
            http_response_code(404);
            echo json_encode(['ok'=>false,'error'=>'No holdable item found for this bib']);
            exit;
        }
    }

    // Place the hold
    $holdResult = $api->placeLocalHoldByItem($patronID, $itemRecordID, $pickupBranchID);

    echo json_encode([
        'ok' => true,
        'patron' => $patronResult['data'],
        'holdResult' => $holdResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error: '.$e->getMessage()]);
}
