<?php
/**
 * Hold API
 * POST - Place a hold in Polaris
 * Body: { patronBarcode, bibRecordId, itemBarcode }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/polaris.php';

$input = json_decode(file_get_contents('php://input'), true);

$patronBarcode = $input['patronBarcode'] ?? null;
$bibRecordId   = $input['bibRecordId'] ?? null;
$itemBarcode   = $input['itemBarcode'] ?? null;

if (empty($patronBarcode)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing patron barcode']);
    exit;
}

if (empty($bibRecordId) && empty($itemBarcode)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing bib record ID or item barcode']);
    exit;
}

try {
    $api = new PolarisAPI();

    // If item barcode is given but no bibRecordId, look it up
    if (empty($bibRecordId) && !empty($itemBarcode)) {

        $itemResult = $api->getItemByBarcode($itemBarcode);

        if ($itemResult['ok'] && isset($itemResult['data']['AssociatedBibRecordID'])) {
            $bibRecordId = $itemResult['data']['AssociatedBibRecordID'];
        } else {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'error' => 'Item not found',
                'details' => $itemResult
            ]);
            exit;
        }
    }

    // Place the hold
    $result = $api->placeHold($patronBarcode, $bibRecordId);

    if ($result['ok']) {
        echo json_encode([
            'ok' => true,
            'message' => 'Hold placed successfully',
            'data' => $result['data'] ?? null
        ]);
        exit;
    }

    // If failed, extract best possible message
    $errorMsg = 'Failed to place hold';

    if (isset($result['data']['Prompt']['Message'])) {
        $errorMsg = $result['data']['Prompt']['Message'];
    } elseif (isset($result['data']['InformationMessages'][0]['Message'])) {
        $errorMsg = $result['data']['InformationMessages'][0]['Message'];
    } elseif (isset($result['error'])) {
        $errorMsg = $result['error'];
    }

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $errorMsg,
        'details' => $result
    ]);
    exit;

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
