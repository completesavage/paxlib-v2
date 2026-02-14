<?php
/**
 * Polaris Hold Test
 * Usage: visit in browser
 * hold_test.php?patronBarcode=21783000087144&bibRecordId=6805386
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/polaris.php';

// --- GET parameters ---
$patronBarcode = $_GET['patronBarcode'] ?? null;
$bibRecordId   = $_GET['bibRecordId'] ?? null;

if (!$patronBarcode || !$bibRecordId) {
    echo json_encode([
        'ok'=>false,
        'error'=>'Missing patronBarcode or bibRecordId'
    ]);
    exit;
}

try {
    $api = new PolarisAPI();

    // Lookup patron
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        echo json_encode([
            'ok'=>false,
            'error'=>'Patron not found',
            'details'=>$patronResult
        ]);
        exit;
    }
    $patronId = $patronResult['data']['PatronID'];

    // Default pickup branch
    $pickupBranchId = 699; // replace if different

    // Place hold
    $holdResult = $api->placeLocalHold($patronId, $bibRecordId, $pickupBranchId);

    // Output
    echo json_encode([
        'ok'=>true,
        'patron'=>$patronResult['data'],
        'holdResult'=>$holdResult
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'ok'=>false,
        'error'=>'Server error: '.$e->getMessage()
    ]);
}
