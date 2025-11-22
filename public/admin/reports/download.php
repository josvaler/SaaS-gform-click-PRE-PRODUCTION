<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('Filename required');
}

// Security: Prevent directory traversal
$filename = basename($filename);
if (!preg_match('/^[a-z_]+_\d{8}_\d{6}_\d{4}-\d{2}-\d{2}\.csv$/', $filename)) {
    http_response_code(400);
    die('Invalid filename format');
}

$filepath = __DIR__ . '/../../../reports/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Serve file with proper headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;

