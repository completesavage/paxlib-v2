<?php
/**
 * Submit a movie request
 * This is a simple wrapper that forwards to requests.php
 */

header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Forward to requests.php via internal include
$_SERVER['REQUEST_METHOD'] = 'POST';

// Include the requests handler
ob_start();
include __DIR__ . '/requests.php';
$output = ob_get_clean();

echo $output;
