<?php
/**
 * Movies API
 * 
 * GET              - List all movies (from cache)
 * GET ?barcode=X   - Get single movie with availability from cache
 * POST             - Update/override movie data (staff)
 * PUT ?action=X    - Cache operations (rebuild, etc.)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only load polaris.php for operations that need it
$polarisLoaded = false;
function loadPolaris() {
    global $polarisLoaded;
    if (!$polarisLoaded && file_exists(__DIR__ . '/polaris.php')) {
        @include_once __DIR__ . '/polaris.php';
        $polarisLoaded = class_exists('PolarisAPI');
    }
    return $polarisLoaded;
}

$dataDir = __DIR__ . '/../data';
$cacheFile = "$dataDir/movies_cache.json";
$overridesFile = "$dataDir/movies_overrides.json";
$csvFile = __DIR__ . '/../dvds.csv';

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function loadCache() {
    global $cacheFile;
    if (!file_exists($cacheFile)) return [];
    $data = json_decode(file_get_contents($cacheFile), true);
    return $data ?: [];
}

function saveCache($cache) {
    global $cacheFile;
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
}

function loadOverrides() {
    global $overridesFile;
    if (!file_exists($overridesFile)) return [];
    $data = json_decode(file_get_contents($overridesFile), true);
    return $data ?: [];
}

function saveOverrides($overrides) {
    global $overridesFile;
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT));
}

function loadFromCSV() {
    global $csvFile;
    $movies = [];
    
    if (!file_exists($csvFile)) return $movies;
    
    $handle = fopen($csvFile, 'r');
    if (!$handle) return $movies;
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 3) continue;
        
        $id = trim($row[0]);
        $title = trim($row[1]);
        $barcode = trim($row[2]);
        $rating = isset($row[3]) ? trim($row[3]) : '';
        
        if (empty($barcode) || empty($title)) continue;
        
        $movies[$barcode] = [
            'id' => $id,
            'title' => $title,
            'barcode' => $barcode,
            'rating' => $rating
        ];
    }
    
    fclose($handle);
    return $movies;
}

function normalizeRating($rating) {
    $rating = strtoupper(trim($rating ?? ''));
    $map = [
        'PG13' => 'PG-13',
        'PG 13' => 'PG-13',
        'NC17' => 'NC-17',
        'NC 17' => 'NC-17',
        'NOT RATED' => 'NR',
        'UNRATED' => 'NR',
        '' => 'NR'
    ];
    return $map[$rating] ?? $rating;
}

function getMergedMovies() {
    $csvMovies = loadFromCSV();
    $cache = loadCache();
    $overrides = loadOverrides();
    
    $movies = [];
    
    foreach ($csvMovies as $barcode => $csvData) {
        $movie = [
            'barcode' => $barcode,
            'title' => $csvData['title'] ?? 'Unknown',
            'rating' => normalizeRating($csvData['rating'] ?? ''),
            'cover' => null,
            'callNumber' => null,
            'description' => null,
            'bibRecordId' => null,
            'upc' => null,
            'oclc' => null,
            'location' => 'DVD Section'
        ];
        
        if (isset($cache[$barcode])) {
            $movie = array_merge($movie, $cache[$barcode]);
        }
        
        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
        }
        
        $movies[] = $movie;
    }
    
    usort($movies, function($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });
    
    return $movies;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - List movies or single movie
if ($method === 'GET') {
    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;
    
    if ($barcode) {
        // Single movie - get from merged data
        $movies = getMergedMovies();
        $movie = null;
        foreach ($movies as $m) {
            if ($m['barcode'] === $barcode) {
                $movie = $m;
                break;
            }
        }
        
        if (!$movie) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Movie not found']);
            exit;
        }
        
        // ALWAYS check real-time availability from Polaris when movie is clicked
        $movie['status'] = 'Checking...';
        $movie['available'] = false;
        
        if (loadPolaris()) {
            try {
                $api = new PolarisAPI();
                
                // Use the efficient bib-level check if we have bibRecordId
                if (!empty($movie['bibRecordId'])) {
                    // Use the bulkItemAvailability method which already works
                    $result = $api->bulkItemAvailability([$movie], 1);
                    
                    if ($result['ok'] && isset($result['data'][$barcode])) {
                        $statusData = $result['data'][$barcode];
                        $movie['status'] = $statusData['status'];
                        $movie['available'] = $statusData['available'];
                        $movie['availableCount'] = $statusData['availableCount'] ?? 0;
                        $movie['totalCount'] = $statusData['totalCount'] ?? 0;
                    } else {
                        // Fallback to item-level check
                        $itemResult = $api->getItemByBarcode($barcode);
                        
                        if ($itemResult['ok'] && isset($itemResult['data'])) {
                            $item = $itemResult['data'];
                            $statusDesc = $item['ItemStatusDescription'] ?? 'Unknown';
                            
                            // Map Polaris status to user-friendly text
                            $isAvailable = (stripos($statusDesc, 'in') !== false) && 
                                          (stripos($statusDesc, 'transit') === false) &&
                                          (stripos($statusDesc, 'hold') === false);
                            
                            $movie['status'] = $isAvailable ? 'Available' : 'Checked Out';
                            $movie['available'] = $isAvailable;
                        } else {
                            $movie['status'] = 'Status unavailable';
                        }
                    }
                } else {
                    // No bibRecordId, use item-level check
                    $result = $api->getItemByBarcode($barcode);
                    
                    if ($result['ok'] && isset($result['data'])) {
                        $item = $result['data'];
                        $statusDesc = $item['ItemStatusDescription'] ?? 'Unknown';
                        
                        // Map Polaris status to user-friendly text
                        $isAvailable = (stripos($statusDesc, 'in') !== false) && 
                                      (stripos($statusDesc, 'transit') === false) &&
                                      (stripos($statusDesc, 'hold') === false);
                        
                        $movie['status'] = $isAvailable ? 'Available' : 'Checked Out';
                        $movie['available'] = $isAvailable;
                    } else {
                        $movie['status'] = 'Status unavailable';
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking availability for $barcode: " . $e->getMessage());
                $movie['status'] = 'Error checking status';
            }
        } else {
            $movie['status'] = 'API unavailable';
        }
        
        echo json_encode(['ok' => true, 'movie' => $movie]);
        exit;
    }
    
    // List all movies
    $movies = getMergedMovies();
    echo json_encode([
        'ok' => true,
        'count' => count($movies),
        'items' => $movies
    ]);
    exit;
}

// POST and PUT handlers from original file...
http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
