<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/polaris.php';

$patronBarcode = $_GET['patronBarcode'] ?? null;
$itemRecordID  = $_GET['itemRecordID'] ?? null;
$pickupBranchID = $_GET['pickupBranchID'] ?? 699;

if (!$patronBarcode || !$itemRecordID) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Missing patronBarcode or itemRecordID']);
    exit;
}

try {
    $api = new PolarisAPI();

    // Lookup patron
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        http_response_code(404);
        echo json_encode(['ok'=>false,'error'=>'Patron not found','details'=>$patronResult]);
        exit;
    }
    $patronID = $patronResult['data']['PatronID'];

    // Place hold using the known itemRecordID
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
