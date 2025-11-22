<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$filename = $_GET['file'] ?? $_POST['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Filename is required'
    ]);
    exit;
}

// Validate filename to prevent directory traversal
// Pattern: {type}_{timestamp}_{date}.csv
if (!preg_match('/^[a-z_]+_\d{8}_\d{6}_\d{4}-\d{2}-\d{2}\.csv$/', $filename)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid filename format'
    ]);
    exit;
}

$reportsDir = __DIR__ . '/../../../reports';
$filepath = $reportsDir . '/' . $filename;

// Security check: ensure file is in reports directory (prevent directory traversal)
$realReportsDir = realpath($reportsDir);
$realFilePath = realpath($filepath);

if ($realFilePath === false || strpos($realFilePath, $realReportsDir) !== 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied'
    ]);
    exit;
}

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'File not found'
    ]);
    exit;
}

// Delete the file
if (@unlink($filepath)) {
    echo json_encode([
        'success' => true,
        'message' => 'Report deleted successfully',
        'filename' => $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete file'
    ]);
}

