<?php
/**
 * Settings API
 * 
 * GET  - Load settings
 * POST - Save settings (merges with existing)
 */

header('Content-Type: application/json; charset=utf-8');

$settingsFile = __DIR__ . '/../data/settings.json';
$dataDir = dirname($settingsFile);

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Load existing settings
function loadSettings() {
    global $settingsFile;
    if (!file_exists($settingsFile)) {
        return [];
    }
    $json = file_get_contents($settingsFile);
    return json_decode($json, true) ?: [];
}

// Save settings
function saveSettings($settings) {
    global $settingsFile;
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode([
        'ok' => true,
        'settings' => loadSettings()
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    
    // Merge with existing settings
    $current = loadSettings();
    $merged = array_merge($current, $input);
    saveSettings($merged);
    
    echo json_encode([
        'ok' => true,
        'settings' => $merged
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
