<?php
/**
 * Trace exactly what continuous-availability.php is doing
 * This is a modified version with extensive logging
 */

set_time_limit(30);
ini_set('max_execution_time', '30');

// Log to both error log and output
function trace($msg) {
    error_log("[TRACE] $msg");
    echo date('H:i:s') . " - $msg\n";
    flush();
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== CONTINUOUS AVAILABILITY TRACE ===\n\n";

trace("Script started");

require_once __DIR__ . '/polaris.php';

$batchSize = 3; // Small batch for testing

try {
    $moviesFile = __DIR__ . '/../data/movies_cache.json';
    $cacheFile = __DIR__ . '/../data/availability_cache.json';
    $progressFile = __DIR__ . '/../data/availability_progress.json';
    
    trace("Files to use:");
    trace("  Movies: $moviesFile");
    trace("  Cache: $cacheFile");
    trace("  Progress: $progressFile");
    
    if (!file_exists($moviesFile)) {
        trace("ERROR: Movies cache not found");
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Movies cache not found']);
        exit;
    }
    
    trace("Loading movies file...");
    $allMovies = json_decode(file_get_contents($moviesFile), true);
    $totalMovies = count($allMovies);
    trace("Loaded $totalMovies movies");
    
    // Load progress tracker
    trace("Checking for progress file...");
    if (file_exists($progressFile)) {
        trace("Progress file exists, loading...");
        $progress = json_decode(file_get_contents($progressFile), true);
        trace("Progress: offset=" . $progress['offset']);
    } else {
        trace("Progress file doesn't exist, creating new");
        $progress = ['offset' => 0, 'cycleStarted' => time()];
    }
    
    $currentOffset = $progress['offset'];
    
    // Load existing cache
    trace("Checking for cache file...");
    if (file_exists($cacheFile)) {
        trace("Cache file exists (size: " . filesize($cacheFile) . " bytes)");
        $cacheContents = file_get_contents($cacheFile);
        trace("Cache contents: " . substr($cacheContents, 0, 100) . "...");
        $cache = json_decode($cacheContents, true);
        trace("Cache decoded, statuses count: " . (isset($cache['statuses']) ? count($cache['statuses']) : 'N/A'));
    } else {
        trace("Cache file doesn't exist, creating new");
        $cache = [
            'statuses' => [],
            'timestamp' => time(),
            'lastUpdated' => date('Y-m-d H:i:s')
        ];
    }
    
    // Get this batch
    trace("Getting batch starting at offset $currentOffset");
    $batch = array_slice($allMovies, $currentOffset, $batchSize);
    
    if (empty($batch)) {
        trace("Batch is empty, resetting to start");
        $currentOffset = 0;
        $batch = array_slice($allMovies, 0, $batchSize);
        $progress['cycleStarted'] = time();
    }
    
    trace("Batch has " . count($batch) . " movies");
    foreach ($batch as $i => $m) {
        trace("  [$i] " . $m['barcode'] . " - " . $m['title']);
    }
    
    // Check this batch
    trace("Calling Polaris API...");
    $api = new PolarisAPI();
    $batchStatuses = $api->bulkItemAvailability($batch, 5);
    
    trace("API result - ok: " . ($batchStatuses['ok'] ? 'true' : 'false'));
    
    if ($batchStatuses['ok']) {
        $batchData = $batchStatuses['data'] ?? [];
        trace("Batch data type: " . gettype($batchData));
        trace("Batch data count: " . count($batchData));
        
        if (!empty($batchData)) {
            $keys = array_keys($batchData);
            trace("First 3 keys: " . json_encode(array_slice($keys, 0, 3)));
            trace("First key type: " . gettype($keys[0]));
            
            // Check if numeric array
            $isNumeric = ($keys === range(0, count($keys) - 1));
            trace("Is numeric array: " . ($isNumeric ? 'YES (BAD!)' : 'NO (good)'));
            
            if ($isNumeric) {
                trace("WARNING: Converting numeric array to empty");
                $batchData = [];
            }
        } else {
            trace("Batch data is empty");
        }
        
        // Merge
        trace("Merging with cache...");
        if (!isset($cache['statuses'])) {
            trace("Cache statuses not set, initializing to []");
            $cache['statuses'] = [];
        }
        
        $beforeCount = count($cache['statuses']);
        
        if (empty($cache['statuses'])) {
            trace("Cache is empty, using batch data directly");
            $cache['statuses'] = $batchData;
        } else {
            trace("Cache has $beforeCount items, merging...");
            $cache['statuses'] = array_merge($cache['statuses'], $batchData);
        }
        
        $afterCount = count($cache['statuses']);
        trace("After merge: $afterCount items (was $beforeCount)");
        
        $cache['lastUpdated'] = date('Y-m-d H:i:s');
        $cache['timestamp'] = time();
        
        // Final check
        if (empty($cache['statuses'])) {
            trace("Cache is still empty, using stdClass");
            $cache['statuses'] = new stdClass();
        }
        
        // Encode
        trace("Encoding to JSON...");
        $jsonData = json_encode($cache);
        trace("JSON length: " . strlen($jsonData));
        trace("JSON first 150 chars: " . substr($jsonData, 0, 150));
        
        // Check format
        if (strpos($jsonData, '"statuses":{') !== false) {
            trace("✓ Statuses encoded as OBJECT");
        } elseif (strpos($jsonData, '"statuses":[') !== false) {
            trace("✗ Statuses encoded as ARRAY");
        }
        
        // Write file
        trace("Writing cache file to: $cacheFile");
        $written = file_put_contents($cacheFile, $jsonData);
        
        if ($written === false) {
            trace("ERROR: Failed to write cache file!");
        } else {
            trace("✓ Wrote $written bytes to cache file");
            
            // Verify it exists
            if (file_exists($cacheFile)) {
                trace("✓ Cache file exists after write (size: " . filesize($cacheFile) . ")");
            } else {
                trace("ERROR: Cache file doesn't exist after write!");
            }
        }
        
        // Update progress
        trace("Writing progress file...");
        $progress['offset'] = $currentOffset + $batchSize;
        $progress['lastCheck'] = time();
        $progressWritten = file_put_contents($progressFile, json_encode($progress));
        trace("✓ Wrote $progressWritten bytes to progress file");
        
        trace("\n=== SUCCESS ===");
        echo json_encode([
            'ok' => true,
            'checked' => count($batch),
            'totalCached' => $afterCount,
            'cacheFileExists' => file_exists($cacheFile),
            'cacheFileSize' => file_exists($cacheFile) ? filesize($cacheFile) : 0
        ], JSON_PRETTY_PRINT);
        
    } else {
        trace("ERROR: API check failed");
        echo json_encode([
            'ok' => false,
            'error' => 'Failed to check batch'
        ]);
    }
    
} catch (Exception $e) {
    trace("EXCEPTION: " . $e->getMessage());
    trace("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

trace("\n=== END TRACE ===");
