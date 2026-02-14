<?php
/**
 * Hold API
 * POST - Place a hold in Polaris
 * Body: { patronBarcode, bibRecordId, itemBarcode }
 */

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Hold API called at " . date('Y-m-d H:i:s'));

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

// Try to load polaris.php
if (!file_exists(__DIR__ . '/polaris.php')) {
    error_log("ERROR: polaris.php not found at " . __DIR__ . '/polaris.php');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Configuration error: polaris.php not found']);
    exit;
}

require_once __DIR__ . '/polaris.php';

// Check if the class exists
if (!class_exists('PolarisAPI')) {
    error_log("ERROR: PolarisAPI class not found");
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Configuration error: PolarisAPI class not found']);
    exit;
}

// Get and log the raw input
$rawInput = file_get_contents('php://input');
error_log("Raw input: " . $rawInput);

$input = json_decode($rawInput, true);
error_log("Decoded input: " . print_r($input, true));

$patronBarcode = $input['patronBarcode'] ?? null;
$bibRecordId   = $input['bibRecordId'] ?? null;
$itemBarcode   = $input['itemBarcode'] ?? null;
$pickupBranchId = 699; // Replace with your branch ID

error_log("Patron: $patronBarcode, Bib: $bibRecordId, Item: $itemBarcode");

if (empty($patronBarcode)) {
    error_log("ERROR: Missing patron barcode");
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing patron barcode']);
    exit;
}

if (empty($bibRecordId) && empty($itemBarcode)) {
    error_log("ERROR: Missing both bib record ID and item barcode");
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing bib record ID or item barcode']);
    exit;
}

try {
    $api = new PolarisAPI();

    // If item barcode is given but no bibRecordId, look it up
    if (empty($bibRecordId) && !empty($itemBarcode)) {
        error_log("Looking up item by barcode: $itemBarcode");
        $itemResult = $api->getItemByBarcode($itemBarcode);
        error_log("Item lookup result: " . print_r($itemResult, true));

        if ($itemResult['ok'] && isset($itemResult['data']['AssociatedBibRecordID'])) {
            $bibRecordId = $itemResult['data']['AssociatedBibRecordID'];
            error_log("Found bib record ID: $bibRecordId");
        } else {
            error_log("ERROR: Item not found");
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'error' => 'Item not found',
                'details' => $itemResult
            ]);
            exit;
        }
    }
    
    // Lookup patron ID from barcode
    error_log("Looking up patron by barcode: $patronBarcode");
    $patronResult = $api->getPatronByBarcode($patronBarcode);
    error_log("Patron lookup result: " . print_r($patronResult, true));
    
    if (!$patronResult['ok'] || !isset($patronResult['data']['PatronID'])) {
        error_log("ERROR: Patron not found");
        http_response_code(404);
        echo json_encode([
            'ok' => false, 
            'error' => 'Patron not found',
            'details' => $patronResult
        ]);
        exit;
    }
    $patronId = $patronResult['data']['PatronID'];
    error_log("Found patron ID: $patronId");

    // Place the hold
    error_log("Placing hold: Patron=$patronId, Bib=$bibRecordId, Pickup=$pickupBranchId");
    $result = $api->placeLocalHold($patronId, $bibRecordId, $pickupBranchId);
    error_log("Hold result: " . print_r($result, true));

    // Check if hold was successful
    if ($result['ok'] && isset($result['data']['Success']) && $result['data']['Success'] === true) {
        error_log("SUCCESS: Hold placed successfully");
        echo json_encode([
            'ok' => true,
            'message' => 'Hold placed successfully',
            'HoldRequestID' => $result['data']['HoldRequestID'] ?? null,
            'data' => $result['data']
        ]);
        exit;
    }

    // If we got here, the hold failed - extract the error message
    $errorMsg = 'Failed to place hold';
    
    if (isset($result['data']['Message'])) {
        $errorMsg = $result['data']['Message'];
    } elseif (isset($result['data']['Prompt']['Message'])) {
        $errorMsg = $result['data']['Prompt']['Message'];
    } elseif (isset($result['data']['InformationMessages'][0]['Message'])) {
        $errorMsg = $result['data']['InformationMessages'][0]['Message'];
    } elseif (isset($result['error'])) {
        $errorMsg = $result['error'];
    }

    error_log("ERROR: Hold failed - $errorMsg");
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $errorMsg,
        'details' => $result
    ]);
    exit;

} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
