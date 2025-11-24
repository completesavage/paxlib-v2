<?php
/**
 * Movie Request API
 * 
 * Endpoints:
 * - GET    /api/requests.php         - List all requests
 * - POST   /api/requests.php         - Create new request
 * - PUT    /api/requests.php         - Update request (mark complete)
 * - DELETE /api/requests.php?id=X    - Delete a request
 * - DELETE /api/requests.php?clearCompleted=true - Clear all completed
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple file-based storage (replace with database in production)
$dataFile = __DIR__ . '/../data/requests.json';
$dataDir = dirname($dataFile);

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Load requests from file
function loadRequests() {
    global $dataFile;
    
    if (!file_exists($dataFile)) {
        return [];
    }
    
    $json = file_get_contents($dataFile);
    $data = json_decode($json, true);
    
    return is_array($data) ? $data : [];
}

// Save requests to file
function saveRequests($requests) {
    global $dataFile;
    
    file_put_contents($dataFile, json_encode($requests, JSON_PRETTY_PRINT));
}

// Generate unique ID
function generateId() {
    return bin2hex(random_bytes(8));
}

// Handle request based on method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all requests
        $requests = loadRequests();
        
        // Sort by timestamp, newest first
        usort($requests, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        echo json_encode([
            'ok' => true,
            'requests' => $requests,
            'count' => count($requests)
        ]);
        break;
        
    case 'POST':
        // Create new request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['movie']) || !isset($input['patron'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing movie or patron data'
            ]);
            exit;
        }
        
        $movie = $input['movie'];
        $patron = $input['patron'];
        
        // Validate required fields
        if (empty($movie['barcode']) || empty($patron['name'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing required fields'
            ]);
            exit;
        }
        
        // Create request object
        $request = [
            'id' => generateId(),
            'movie' => [
                'barcode' => $movie['barcode'],
                'title' => $movie['title'] ?? 'Unknown',
                'callNumber' => $movie['callNumber'] ?? null,
                'cover' => $movie['cover'] ?? null
            ],
            'patron' => [
                'name' => $patron['name'],
                'barcode' => $patron['barcode'] ?? null
            ],
            'timestamp' => date('c'),
            'completed' => false,
            'completedAt' => null
        ];
        
        // Add to list
        $requests = loadRequests();
        $requests[] = $request;
        saveRequests($requests);
        
        echo json_encode([
            'ok' => true,
            'request' => $request
        ]);
        break;
        
    case 'PUT':
        // Update request (mark as complete)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing request ID'
            ]);
            exit;
        }
        
        $requests = loadRequests();
        $found = false;
        
        foreach ($requests as &$req) {
            if ($req['id'] === $input['id']) {
                if (isset($input['completed'])) {
                    $req['completed'] = (bool)$input['completed'];
                    $req['completedAt'] = $req['completed'] ? date('c') : null;
                }
                $found = true;
                break;
            }
        }
        unset($req);
        
        if (!$found) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'error' => 'Request not found'
            ]);
            exit;
        }
        
        saveRequests($requests);
        
        echo json_encode([
            'ok' => true
        ]);
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        $clearCompleted = isset($_GET['clearCompleted']);
        
        $requests = loadRequests();
        
        if ($clearCompleted) {
            // Remove all completed requests
            $requests = array_filter($requests, function($req) {
                return !$req['completed'];
            });
            $requests = array_values($requests); // Re-index
        } elseif ($id) {
            // Remove specific request
            $requests = array_filter($requests, function($req) use ($id) {
                return $req['id'] !== $id;
            });
            $requests = array_values($requests);
        } else {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Missing request ID'
            ]);
            exit;
        }
        
        saveRequests($requests);
        
        echo json_encode([
            'ok' => true
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'ok' => false,
            'error' => 'Method not allowed'
        ]);
}
