<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$reportsDir = __DIR__ . '/../../../reports';

if (!is_dir($reportsDir)) {
    echo json_encode([
        'success' => true,
        'message' => 'No reports directory found',
        'deleted_count' => 0
    ]);
    exit;
}

$files = glob($reportsDir . '/*.csv');
$deletedCount = 0;
$errors = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    // Validate filename pattern
    if (preg_match('/^[a-z_]+_\d{8}_\d{6}_\d{4}-\d{2}-\d{2}\.csv$/', $filename)) {
        if (@unlink($file)) {
            $deletedCount++;
        } else {
            $errors[] = $filename;
        }
    }
}

if (empty($errors)) {
    echo json_encode([
        'success' => true,
        'message' => 'All reports deleted successfully',
        'deleted_count' => $deletedCount
    ]);
} else {
    http_response_code(207); // Multi-Status
    echo json_encode([
        'success' => true,
        'message' => 'Most reports deleted, but some errors occurred',
        'deleted_count' => $deletedCount,
        'errors' => $errors
    ]);
}

