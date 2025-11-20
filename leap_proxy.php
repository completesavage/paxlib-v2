<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json');

// ===== config =====
$baseUrl  = 'https://leap.illinoisheartland.org/Polaris.ApplicationServices';
$langCode = 'eng';
$siteId   = '20';


// which api path to hit, e.g. "polaris/699/3073/itemrecords/7033196/"
$path = isset($_GET['path']) ? trim($_GET['path']) : '';
if ($path === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing path param']);
    exit;
}

// optional method, default GET
$method = isset($_GET['method']) ? strtoupper($_GET['method']) : 'GET';
$allowedMethods = ['GET']; // add POST etc later if you need
if (!in_array($method, $allowedMethods, true)) {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

// ===== helper: curl request =====
function do_request($method, $url, $headers = [], $body = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    // verify ssl (fine from your house)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $responseBody   = curl_exec($ch);
    $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($responseBody === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return [0, null, 'curl error: ' . $err];
    }

    curl_close($ch);
    return [$responseStatus, $responseBody, null];
}

// ===== step 1: staff authentication =====
$authUrl = $baseUrl . "/api/v1/$langCode/$siteId/authentication/staffuser";

$basicToken = base64_encode($username . ':' . $password);

list($status, $body, $err) = do_request(
    'POST',
    $authUrl,
    [
        "Authorization: Basic $basicToken",
        "Accept: application/json"
    ]
);

if ($err !== null) {
    http_response_code(500);
    echo json_encode(['error' => $err]);
    exit;
}

$authData = json_decode($body, true);
if ($authData === null) {
    http_response_code($status ?: 500);
    echo $body; // show raw html if not json
    exit;
}

if (
    !isset($authData['SiteDomain']) ||
    !isset($authData['AccessToken']) ||
    !isset($authData['AccessSecret'])
) {
    http_response_code(500);
    echo json_encode(['error' => 'missing fields in auth response', 'auth' => $authData]);
    exit;
}

$siteDomain   = $authData['SiteDomain'];
$accessToken  = $authData['AccessToken'];
$accessSecret = $authData['AccessSecret'];

// ===== step 2: call target api path =====

// final url: base/api/v1/eng/20/{path}
$url = $baseUrl . "/api/v1/$langCode/$siteId/" . ltrim($path, '/');

$pasHeader = "Authorization: PAS $siteDomain:$accessToken:$accessSecret";

list($status2, $body2, $err2) = do_request(
    $method,
    $url,
    [
        $pasHeader,
        "Accept: application/json"
    ]
);

if ($err2 !== null) {
    http_response_code(500);
    echo json_encode(['error' => $err2]);
    exit;
}

http_response_code($status2);
echo $body2;
