<?php
// api/item.php
require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// get barcode from query
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
if ($barcode === '') {
    echo json_encode([
        'ok'    => false,
        'error' => 'missing barcode'
    ]);
    exit;
}

// prefix after /api/v1/eng/20/
// this matches the test you showed in index.html:
//   polaris/699/3073/itemrecords/7033196/
$prefix = 'polaris/699/3073';

// build polaris path using barcode instead of item id
// we tell polaris that the "id" is actually a barcode with isBarcode=true
$barcodeEnc = rawurlencode($barcode);
$polarisPath = $prefix . '/itemrecords/' . $barcodeEnc . '/?isBarcode=true';

// build url to our local leap proxy
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host   = $_SERVER['HTTP_HOST'];

// script name is like /api/item.php, so /api/../leap_proxy.php → /leap_proxy.php
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$proxyUrl  = $scheme . $host . $scriptDir . '/../leap_proxy.php?path=' . urlencode($polarisPath);

// call leap_proxy.php
$ch = curl_init($proxyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_TIMEOUT        => 12,
]);

$body   = curl_exec($ch);
$curlErr = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false) {
    echo json_encode([
        'ok'    => false,
        'error' => 'curl error calling leap proxy',
        'detail'=> $curlErr,
    ]);
    exit;
}

if ($status < 200 || $status >= 300) {
    echo json_encode([
        'ok'    => false,
        'error' => 'leap http status ' . $status,
        'raw'   => $body,
    ]);
    exit;
}

// decode polaris json
$data = json_decode($body, true);
if ($data === null) {
    echo json_encode([
        'ok'    => false,
        'error' => 'invalid json from leap',
        'raw'   => $body,
    ]);
    exit;
}

// later you can add cover logic here if you want
// for now, just send null so the front end falls back to NO_COVER
$cover = null;

echo json_encode([
    'ok'      => true,
    'barcode' => $barcode,
    'cover'   => $cover,
    'data'    => $data,   // full item record with BibInfo, CirculationData etc
], JSON_UNESCAPED_UNICODE);
