<?php
/**
 * Movies API
 * 
 * GET              - List all movies (from cache)
 * GET ?barcode=X   - Get single movie with fresh availability
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

/**
 * Load movie cache
 */
function loadCache() {
    global $cacheFile;
    if (!file_exists($cacheFile)) return [];
    $data = json_decode(file_get_contents($cacheFile), true);
    return $data ?: [];
}

/**
 * Save movie cache
 */
function saveCache($cache) {
    global $cacheFile;
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
}

/**
 * Load overrides (staff edits)
 */
function loadOverrides() {
    global $overridesFile;
    if (!file_exists($overridesFile)) return [];
    $data = json_decode(file_get_contents($overridesFile), true);
    return $data ?: [];
}

/**
 * Save overrides
 */
function saveOverrides($overrides) {
    global $overridesFile;
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT));
}

/**
 * Load movies from CSV
 */
function loadFromCSV() {
    global $csvFile;
    $movies = [];
    
    if (!file_exists($csvFile)) return $movies;
    
    $handle = fopen($csvFile, 'r');
    $headers = fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 2) continue;
        
        $movie = [];
        foreach ($headers as $i => $header) {
            $movie[strtolower(trim($header))] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        
        if (!empty($movie['barcode'])) {
            $movies[$movie['barcode']] = $movie;
        }
    }
    
    fclose($handle);
    return $movies;
}

/**
 * Normalize rating
 */
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

/**
 * Merge movie data: CSV -> Cache -> Overrides
 */
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
        
        // Apply cached data
        if (isset($cache[$barcode])) {
            $movie = array_merge($movie, $cache[$barcode]);
        }
        
        // Apply overrides (highest priority)
        if (isset($overrides[$barcode])) {
            $movie = array_merge($movie, $overrides[$barcode]);
        }
        
        $movies[] = $movie;
    }
    
    // Sort by title
    usort($movies, function($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });
    
    return $movies;
}

/**
 * Get movies with on-demand cover fetching (for when no cache exists)
 */
function getMoviesWithCovers() {
    $movies = getMergedMovies();
    
    // If movies have no covers, try to build URLs from existing item data
    foreach ($movies as &$movie) {
        if (empty($movie['cover']) && (!empty($movie['upc']) || !empty($movie['oclc']))) {
            $client = defined('SYNDETICS_CLIENT') ? SYNDETICS_CLIENT : 'ilheartland';
            $base = "https://secure.syndetics.com/index.aspx?client={$client}";
            
            if (!empty($movie['upc'])) {
                $movie['cover'] = "{$base}&upc={$movie['upc']}/MC.GIF";
            } elseif (!empty($movie['oclc'])) {
                $oclc = preg_replace('/[^0-9]/', '', $movie['oclc']);
                $movie['cover'] = "{$base}&oclc={$oclc}/MC.GIF";
            }
        }
    }
    
    return $movies;
}

// ============ HANDLE REQUEST ============

$method = $_SERVER['REQUEST_METHOD'];

// GET - List movies or single movie
if ($method === 'GET') {
    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;
    
    if ($barcode) {
        // Single movie with fresh availability
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
        
        // Fetch fresh availability from Polaris (if available)
        if (loadPolaris()) {
            try {
                $api = new PolarisAPI();
                $result = $api->getItemByBarcode($barcode);
                
                if ($result['ok'] && isset($result['data'])) {
                    $item = $result['data'];
                    $movie['status'] = $item['ItemStatusDescription'] ?? 'Unknown';
                    $movie['dueDate'] = $item['CirculationData']['DueDate'] ?? null;
                    $movie['lastCheckIn'] = $item['CheckInDate'] ?? null;
                }
            } catch (Exception $e) {
                // Keep cached data if API fails
            }
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

// POST - Update movie override
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $barcode = $input['barcode'] ?? null;
    if (!$barcode) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode']);
        exit;
    }
    
    $overrides = loadOverrides();
    
    // Fields that can be overridden
    $allowedFields = ['title', 'cover', 'rating', 'callNumber', 'description', 'location', 'customImage'];
    
    if (!isset($overrides[$barcode])) {
        $overrides[$barcode] = ['barcode' => $barcode];
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $overrides[$barcode][$field] = $input[$field];
        }
    }
    
    $overrides[$barcode]['updatedAt'] = date('c');
    
    saveOverrides($overrides);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Movie updated',
        'override' => $overrides[$barcode]
    ]);
    exit;
}

// PUT - Cache operations
if ($method === 'PUT') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'rebuild') {
        // Rebuild entire cache from Polaris API
        if (!loadPolaris()) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Polaris API not available - check config.php']);
            exit;
        }
        
        $csvMovies = loadFromCSV();
        $cache = [];
        $api = new PolarisAPI();
        $total = count($csvMovies);
        $processed = 0;
        $errors = [];
        
        foreach ($csvMovies as $barcode => $csvData) {
            $processed++;
            
            try {
                $result = $api->getItemByBarcode($barcode);
                
                if ($result['ok'] && isset($result['data'])) {
                    $item = $result['data'];
                    $bib = $item['BibInfo'] ?? [];
                    
                    $upc = $bib['UPCNumber'] ?? null;
                    $oclc = $bib['OCLCNumber'] ?? null;
                    $isbn = $bib['ISBN'] ?? null;
                    
                    $cache[$barcode] = [
                        'barcode' => $barcode,
                        'title' => $bib['BrowseTitle'] ?? $csvData['title'] ?? 'Unknown',
                        'callNumber' => $item['CallNumber'] ?? $bib['CallNumber'] ?? null,
                        'bibRecordId' => $item['AssociatedBibRecordID'] ?? null,
                        'upc' => $upc,
                        'oclc' => $oclc,
                        'isbn' => $isbn,
                        'cover' => $api->buildCoverUrl($upc, $oclc, $isbn),
                        'location' => $bib['AssignedBranch'] ?? 'DVD Section',
                        'materialType' => $bib['MaterialType'] ?? 'DVD',
                        'cachedAt' => date('c')
                    ];
                } else {
                    $errors[] = "Failed to fetch: $barcode";
                }
            } catch (Exception $e) {
                $errors[] = "Error for $barcode: " . $e->getMessage();
            }
            
            // Rate limiting
            usleep(100000); // 100ms delay
        }
        
        saveCache($cache);
        
        echo json_encode([
            'ok' => true,
            'message' => 'Cache rebuilt',
            'processed' => $processed,
            'total' => $total,
            'errors' => $errors
        ]);
        exit;
    }
    
    if ($action === 'refresh') {
        // Refresh single item
        $barcode = $_GET['barcode'] ?? null;
        if (!$barcode) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing barcode']);
            exit;
        }
        
        if (!loadPolaris()) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Polaris API not available']);
            exit;
        }
        
        $cache = loadCache();
        $api = new PolarisAPI();
        
        try {
            $result = $api->getItemByBarcode($barcode);
            
            if ($result['ok'] && isset($result['data'])) {
                $item = $result['data'];
                $bib = $item['BibInfo'] ?? [];
                
                $upc = $bib['UPCNumber'] ?? null;
                $oclc = $bib['OCLCNumber'] ?? null;
                $isbn = $bib['ISBN'] ?? null;
                
                $cache[$barcode] = [
                    'barcode' => $barcode,
                    'title' => $bib['BrowseTitle'] ?? 'Unknown',
                    'callNumber' => $item['CallNumber'] ?? $bib['CallNumber'] ?? null,
                    'bibRecordId' => $item['AssociatedBibRecordID'] ?? null,
                    'upc' => $upc,
                    'oclc' => $oclc,
                    'isbn' => $isbn,
                    'cover' => $api->buildCoverUrl($upc, $oclc, $isbn),
                    'location' => $bib['AssignedBranch'] ?? 'DVD Section',
                    'materialType' => $bib['MaterialType'] ?? 'DVD',
                    'cachedAt' => date('c')
                ];
                
                saveCache($cache);
                
                echo json_encode([
                    'ok' => true,
                    'message' => 'Item refreshed',
                    'item' => $cache[$barcode]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Item not found in Polaris']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
