<?php
/**
 * Patron API (Debug Mode)
 * GET ?barcode=XXX - Look up patron by barcode, return name and ID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/polaris.php';

$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing barcode parameter']);
    exit;
}

try {
    $api = new PolarisAPI();
    $result = $api->getPatronByBarcode($barcode);

    // DEBUG: Log raw API response
    error_log("PolarisAPI response for barcode $barcode: " . print_r($result, true));

    if ($result['ok'] && isset($result['data'])) {
        $patron = $result['data'];

        $firstName = $patron['NameFirst'] ?? '';
        $lastName = $patron['NameLast'] ?? '';
        $displayName = trim("$firstName $lastName");

        if (empty($displayName)) {
            $displayName = $patron['Barcode'] ?? $barcode;
        }

        echo json_encode([
            'ok' => true,
            'patron' => [
                'id' => $patron['PatronID'] ?? null,
                'barcode' => $patron['Barcode'] ?? $barcode,
                'name' => $displayName,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $patron['EmailAddress'] ?? null,
                'phone' => $patron['PhoneVoice1'] ?? null,
                'expirationDate' => $patron['ExpirationDate'] ?? null
            ]
        ]);
    } else {
        // Return raw debug info if patron not found
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Patron not found',
            'details' => $result // <-- raw response from Polaris
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'details' => null
    ]);
}
