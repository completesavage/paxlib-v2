<?php
/**
 * Requests API
 * 
 * GET              - List all requests
 * POST             - Create new request
 * PUT              - Update request (mark complete)
 * DELETE ?id=X     - Delete single request
 * DELETE ?clearCompleted=true - Delete all completed
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dataDir = __DIR__ . '/../data';
$requestsFile = "$dataDir/requests.json";

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function loadRequests() {
    global $requestsFile;
    if (!file_exists($requestsFile)) return [];
    $data = json_decode(file_get_contents($requestsFile), true);
    return is_array($data) ? $data : [];
}

function saveRequests($requests) {
    global $requestsFile;
    return file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT)) !== false;
}

function generateId() {
    return bin2hex(random_bytes(8));
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - List all requests
if ($method === 'GET') {
    $requests = loadRequests();
    
    usort($requests, function($a, $b) {
        return strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0);
    });
    
    $pending = array_filter($requests, fn($r) => !($r['completed'] ?? false));
    $pendingNow = array_filter($pending, fn($r) => ($r['type'] ?? 'now') === 'now');
    $pendingHolds = array_filter($pending, fn($r) => ($r['type'] ?? 'now') === 'hold');
    $today = array_filter($requests, fn($r) => 
        date('Y-m-d', strtotime($r['timestamp'] ?? '1970-01-01')) === date('Y-m-d')
    );
    
    echo json_encode([
        'ok' => true,
        'requests' => array_values($requests),
        'stats' => [
            'total' => count($requests),
            'pending' => count($pending),
            'pendingNow' => count($pendingNow),
            'pendingHolds' => count($pendingHolds),
            'today' => count($today)
        ]
    ]);
    exit;
}

// POST - Create new request
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    
    $movie = $input['movie'] ?? null;
    $patron = $input['patron'] ?? null;
    $type = $input['type'] ?? 'now';
    
    if (!$movie || empty($movie['barcode'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing movie barcode']);
        exit;
    }
    
    $request = [
        'id' => generateId(),
        'movie' => [
            'barcode' => $movie['barcode'],
            'title' => $movie['title'] ?? 'Unknown',
            'callNumber' => $movie['callNumber'] ?? null,
            'cover' => $movie['cover'] ?? null,
            'bibRecordId' => $movie['bibRecordId'] ?? null
        ],
        'patron' => [
            'barcode' => $patron['barcode'] ?? null,
            'name' => $patron['name'] ?? 'Guest',
            'id' => $patron['id'] ?? null
        ],
        'type' => $type,
        'timestamp' => date('c'),
        'completed' => false,
        'completedAt' => null
    ];
    
    $requests = loadRequests();
    array_unshift($requests, $request);
    
    if (!saveRequests($requests)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to save']);
        exit;
    }
    
    echo json_encode(['ok' => true, 'request' => $request]);
    exit;
}

// PUT - Update request
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing ID']);
        exit;
    }
    
    $requests = loadRequests();
    $found = false;
    
    foreach ($requests as &$r) {
        if ($r['id'] === $id) {
            if (isset($input['completed'])) {
                $r['completed'] = (bool)$input['completed'];
                $r['completedAt'] = $input['completed'] ? date('c') : null;
            }
            if (isset($input['notes'])) $r['notes'] = $input['notes'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Not found']);
        exit;
    }
    
    saveRequests($requests);
    echo json_encode(['ok' => true]);
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $requests = loadRequests();
    
    if (isset($_GET['clearCompleted']) && $_GET['clearCompleted'] === 'true') {
        $requests = array_values(array_filter($requests, fn($r) => !($r['completed'] ?? false)));
        saveRequests($requests);
        echo json_encode(['ok' => true]);
        exit;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing ID']);
        exit;
    }
    
    $requests = array_values(array_filter($requests, fn($r) => $r['id'] !== $id));
    saveRequests($requests);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
