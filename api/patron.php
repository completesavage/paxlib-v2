<?php
/**
 * Patron API (Debug Mode)
 * GET ?barcode=XXX - Look up patron by barcode, return name and ID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', '0');

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

    if ($result['ok'] && !empty($result['data']) && is_array($result['data'])) {
        $patron = $result['data'];
        // Some Polaris versions return registration fields under Registration;
        // others expose some fields at the top level.
        $reg = isset($patron['Registration']) && is_array($patron['Registration'])
            ? $patron['Registration']
            : $patron;
    
        $firstName = $reg['NameFirst'] ?? '';
        $lastName = $reg['NameLast'] ?? '';
        $displayName = trim("$firstName $lastName");
        if (empty($displayName)) {
            $displayName = $reg['PatronFullName'] ?? $patron['Barcode'] ?? $barcode;
        }
    
        echo json_encode([
            'ok' => true,
            'patron' => [
                'id' => $patron['PatronID'] ?? null,
                'barcode' => $patron['Barcode'] ?? $barcode,
                'name' => $displayName,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $reg['EmailAddress'] ?? null,
                'phone' => $reg['PhoneVoice1'] ?? null,
                'expirationDate' => $reg['ExpirationDate'] ?? null
            ]
        ]);
    } else {
        $resultError = $result['error'] ?? 'Patron not found';
        $isNotFound = $resultError === 'Patron not found';
        $isAuthFailure = stripos($resultError, 'authentication') !== false ||
            in_array(($result['status'] ?? null), [401, 403], true);

        if ($isNotFound) {
            http_response_code(404);
        } elseif ($isAuthFailure) {
            http_response_code(503);
        } else {
            http_response_code(502);
        }

        echo json_encode([
            'ok' => false,
            'error' => $resultError,
            'details' => $result
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'details' => null
    ]);
}
