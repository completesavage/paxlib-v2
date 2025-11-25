<?php
/**
 * Image Upload API
 * POST - Upload custom cover image for a movie
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
$coversDir = "$uploadsDir/covers";

// Ensure directories exist
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
if (!is_dir($coversDir)) mkdir($coversDir, 0755, true);

// Check if this is a file upload or base64 data
if (isset($_FILES['image'])) {
    // File upload
    $file = $_FILES['image'];
    $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
    
    if (empty($barcode)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing barcode']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Upload failed: ' . $file['error']]);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'File too large. Max 5MB']);
        exit;
    }
    
    // Determine extension
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $ext = $extensions[$mimeType];
    
    // Save file
    $filename = $barcode . '.' . $ext;
    $filepath = "$coversDir/$filename";
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
        exit;
    }
    
    // Return URL
    $url = '/uploads/covers/' . $filename;
    
    // Update movie override
    $overridesFile = __DIR__ . '/../data/movies_overrides.json';
    $overrides = file_exists($overridesFile) ? json_decode(file_get_contents($overridesFile), true) : [];
    
    if (!isset($overrides[$barcode])) {
        $overrides[$barcode] = ['barcode' => $barcode];
    }
    $overrides[$barcode]['customImage'] = $url;
    $overrides[$barcode]['cover'] = $url;
    $overrides[$barcode]['updatedAt'] = date('c');
    
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'ok' => true,
        'url' => $url,
        'message' => 'Image uploaded successfully'
    ]);
    exit;
}

// Base64 upload
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['image']) || !isset($input['barcode'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing image data or barcode']);
    exit;
}

$barcode = trim($input['barcode']);
$imageData = $input['image'];

// Parse data URL
if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
    $ext = $matches[1];
    $data = base64_decode($matches[2]);
    
    if ($data === false) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid base64 data']);
        exit;
    }
    
    // Validate extension
    $allowedExts = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($ext), $allowedExts)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid image type']);
        exit;
    }
    
    if ($ext === 'jpeg') $ext = 'jpg';
    
    // Save file
    $filename = $barcode . '.' . $ext;
    $filepath = "$coversDir/$filename";
    
    if (file_put_contents($filepath, $data) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
        exit;
    }
    
    // Return URL
    $url = '/uploads/covers/' . $filename;
    
    // Update movie override
    $overridesFile = __DIR__ . '/../data/movies_overrides.json';
    $overrides = file_exists($overridesFile) ? json_decode(file_get_contents($overridesFile), true) : [];
    
    if (!isset($overrides[$barcode])) {
        $overrides[$barcode] = ['barcode' => $barcode];
    }
    $overrides[$barcode]['customImage'] = $url;
    $overrides[$barcode]['cover'] = $url;
    $overrides[$barcode]['updatedAt'] = date('c');
    
    file_put_contents($overridesFile, json_encode($overrides, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'ok' => true,
        'url' => $url,
        'message' => 'Image uploaded successfully'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Invalid image format']);
