<?php
/**
 * Patron API (Debug Mode)
 * GET ?barcode=XXX - Look up patron by barcode, return name and ID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/polaris.php';

$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
$name    = isset($_GET['name'])    ? trim($_GET['name'])    : '';

if (empty($barcode) && empty($name)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing barcode or name parameter']);
    exit;
}

try {
    $api = new PolarisAPI();

    // Name search
    if (!empty($name)) {
        $result = $api->searchPatronsByName($name);
        if (!$result['ok']) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'No patron found', 'details' => $result]);
            exit;
        }

        $patrons = [];
        foreach ($result['patrons'] as $p) {
            $reg = $p['Registration'] ?? $p;
            $firstName = $reg['NameFirst'] ?? $p['NameFirst'] ?? '';
            $lastName  = $reg['NameLast']  ?? $p['NameLast']  ?? '';
            $displayName = trim("$firstName $lastName") ?: ($reg['PatronFullName'] ?? '');
            $patrons[] = [
                'id'      => $p['PatronID'] ?? null,
                'barcode' => $p['Barcode']  ?? null,
                'name'    => $displayName,
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'email'     => $reg['EmailAddress'] ?? null,
            ];
        }

        // Return first match as `patron` too, for backwards compat with doLogin
        echo json_encode(['ok' => true, 'patron' => $patrons[0], 'patrons' => $patrons]);
        exit;
    }

    // Barcode lookup
    $result = $api->getPatronByBarcode($barcode);

    // DEBUG: Log raw API response
    error_log("PolarisAPI response for barcode $barcode: " . print_r($result, true));

   if ($result['ok'] && isset($result['data']['Registration'])) {
        $patron = $result['data'];
        $reg = $patron['Registration'];
    
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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Patron not found',
            'details' => $result
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
