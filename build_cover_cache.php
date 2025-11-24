<?php
/**
 * Cover Cache Builder
 * 
 * This script fetches cover URLs for all DVDs and caches them locally.
 * Run this periodically (e.g., daily via cron) to keep covers up to date.
 * 
 * Usage: php build_cover_cache.php
 */

set_time_limit(0);
ini_set('memory_limit', '256M');

require __DIR__ . '/config.php';

echo "=== Cover Cache Builder ===\n\n";

// Paths
$csvPath = __DIR__ . '/dvds.csv';
$cachePath = __DIR__ . '/data/covers_cache.json';
$dataDir = __DIR__ . '/data';

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "Created data directory\n";
}

// Load existing cache
$cache = [];
if (file_exists($cachePath)) {
    $cache = json_decode(file_get_contents($cachePath), true) ?: [];
    echo "Loaded " . count($cache) . " cached covers\n";
}

// Load CSV
if (!file_exists($csvPath)) {
    die("ERROR: dvds.csv not found\n");
}

$fh = fopen($csvPath, 'r');
$barcodes = [];

while (($row = fgetcsv($fh)) !== false) {
    if (count($row) >= 3) {
        $barcode = trim($row[2]);
        $title = trim($row[1]);
        if ($barcode && $title) {
            $barcodes[$barcode] = $title;
        }
    }
}
fclose($fh);

echo "Found " . count($barcodes) . " DVDs to process\n\n";

// Polaris API config
$baseUrl = 'https://leap.illinoisheartland.org/Polaris.ApplicationServices';
$langCode = 'eng';
$siteId = '20';

// Authenticate
echo "Authenticating with Polaris API...\n";

$authUrl = "$baseUrl/api/v1/$langCode/$siteId/authentication/staffuser";
$basicToken = base64_encode($username . ':' . $password);

$ch = curl_init($authUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '',
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic $basicToken",
        "Accept: application/json",
        "Content-Length: 0"
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$authBody = curl_exec($ch);
$authStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($authStatus !== 200) {
    die("ERROR: Authentication failed (HTTP $authStatus)\n");
}

$authData = json_decode($authBody, true);
if (!isset($authData['SiteDomain']) || !isset($authData['AccessToken'])) {
    die("ERROR: Invalid auth response\n");
}

$siteDomain = $authData['SiteDomain'];
$accessToken = $authData['AccessToken'];
$accessSecret = $authData['AccessSecret'];

echo "Authenticated successfully!\n\n";

// Process each barcode
$processed = 0;
$updated = 0;
$errors = 0;

foreach ($barcodes as $barcode => $title) {
    $processed++;
    
    // Skip if already cached and not too old
    if (isset($cache[$barcode]) && $cache[$barcode] !== '/img/no-cover.svg') {
        echo "[$processed/" . count($barcodes) . "] SKIP: $title (cached)\n";
        continue;
    }
    
    echo "[$processed/" . count($barcodes) . "] Fetching: $title... ";
    
    // Build API URL
    $prefix = 'polaris/699/3073';
    $apiUrl = "$baseUrl/api/v1/$langCode/$siteId/$prefix/itemrecords/" 
            . rawurlencode($barcode) . "/?isBarcode=true";
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: PAS $siteDomain:$accessToken:$accessSecret",
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status !== 200) {
        echo "ERROR (HTTP $status)\n";
        $errors++;
        continue;
    }
    
    $data = json_decode($body, true);
    if (!$data || !isset($data['BibInfo'])) {
        echo "NO DATA\n";
        $cache[$barcode] = '/img/no-cover.svg';
        continue;
    }
    
    // Build cover URL
    $bib = $data['BibInfo'];
    $coverUrl = null;
    
    $syndeticsBase = 'https://secure.syndetics.com/index.aspx?isbn=/MC.GIF&client=' 
                   . (defined('SYNDETICS_CLIENT') ? SYNDETICS_CLIENT : 'ilheartland');
    
    if (!empty($bib['UPCNumber'])) {
        $upc = preg_replace('/[^0-9]/', '', $bib['UPCNumber']);
        if ($upc) {
            $coverUrl = $syndeticsBase . '&upc=' . rawurlencode($upc);
        }
    }
    
    if (!$coverUrl && !empty($bib['OCLCNumber'])) {
        $coverUrl = $syndeticsBase . '&oclc=' . rawurlencode($bib['OCLCNumber']);
    }
    
    if ($coverUrl) {
        $cache[$barcode] = $coverUrl;
        $updated++;
        echo "OK\n";
    } else {
        $cache[$barcode] = '/img/no-cover.svg';
        echo "NO COVER\n";
    }
    
    // Be nice to the API
    usleep(100000); // 100ms delay
}

// Save cache
file_put_contents($cachePath, json_encode($cache, JSON_PRETTY_PRINT));

echo "\n=== Complete ===\n";
echo "Processed: $processed\n";
echo "Updated: $updated\n";
echo "Errors: $errors\n";
echo "Cache saved to: $cachePath\n";
