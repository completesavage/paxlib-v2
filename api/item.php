<?php
// api/item.php
require __DIR__ . '/../config.php';
require_once __DIR__ . '/movie_helpers.php';

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

// this is the prefix after /api/v1/eng/20/
// adjust if your leap_proxy expects something different
$prefix = 'polaris/699/3073';

// tell polaris the "id" is a barcode
$barcodeEnc  = rawurlencode($barcode);
$polarisPath = $prefix . '/itemrecords/' . $barcodeEnc . '/?isBarcode=true';

// build url to leap_proxy.php on this same host
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host      = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$proxyUrl  = $scheme . $host . $scriptDir . '/../leap_proxy.php?path=' . urlencode($polarisPath);

// call leap_proxy.php
$ch = curl_init($proxyUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_TIMEOUT        => 12,
]);
$body    = curl_exec($ch);
$curlErr = curl_error($ch);
$status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

$data = json_decode($body, true);
if ($data === null) {
    echo json_encode([
        'ok'    => false,
        'error' => 'invalid json from leap',
        'raw'   => $body,
    ]);
    exit;
}

// Build a complete Syndetics URL from every valid identifier Polaris returned.
$cover = !empty($data['BibInfo']) && is_array($data['BibInfo'])
    ? coverFromBibInfo($data['BibInfo'])
    : null;

echo json_encode([
    'ok'      => true,
    'barcode' => $barcode,
    'cover'   => $cover,
    'data'    => $data,
], JSON_UNESCAPED_UNICODE);
