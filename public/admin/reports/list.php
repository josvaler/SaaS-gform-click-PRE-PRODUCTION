<?php
declare(strict_types=1);

require __DIR__ . '/../../../config/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$reportsDir = __DIR__ . '/../../../reports';
$reports = [];

if (is_dir($reportsDir)) {
    $files = glob($reportsDir . '/*.csv');
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Parse filename: {type}_{timestamp}_{date}.csv
        if (preg_match('/^([a-z_]+)_(\d{8})_(\d{6})_(\d{4}-\d{2}-\d{2})\.csv$/', $filename, $matches)) {
            $type = $matches[1];
            $date = $matches[4];
            $timestamp = $matches[2] . '_' . $matches[3];
            
            $reports[] = [
                'filename' => $filename,
                'type' => $type,
                'date' => $date,
                'timestamp' => $timestamp,
                'size' => filesize($file),
                'size_formatted' => formatBytes(filesize($file)),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
    
    // Sort by date (newest first)
    usort($reports, function($a, $b) {
        return strcmp($b['created'], $a['created']);
    });
}

echo json_encode([
    'success' => true,
    'reports' => array_slice($reports, 0, 20) // Last 20 reports
]);

