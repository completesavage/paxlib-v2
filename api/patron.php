<?php
/**
 * Patron API
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

    if ($result['ok'] && isset($result['data']['PatronID'])) {
        $patron = $result['data'];

        // Build display name
        $firstName = $patron['NameFirst'] ?? '';
        $lastName = $patron['NameLast'] ?? '';
        $displayName = trim("$firstName $lastName") ?: ($patron['Barcode'] ?? $barcode);

        echo json_encode([
            'ok' => true,
            'patron' => [
                'id' => $patron['PatronID'],
                'barcode' => $patron['Barcode'] ?? $barcode,
                'name' => $displayName,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $patron['EmailAddress'] ?? '',
                'phone' => $patron['PhoneVoice1'] ?? '',
                'expirationDate' => $patron['ExpirationDate'] ?? ''
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Patron not found',
            'details' => $result['error'] ?? null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
