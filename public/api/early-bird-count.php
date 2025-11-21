<?php
/**
 * Early Bird Count API Endpoint
 * Returns JSON with current count and remaining slots
 */

declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../../config/bootstrap.php';

$countData = get_early_bird_count();

echo json_encode([
    'count' => $countData['count'],
    'remaining' => $countData['remaining'],
    'is_available' => $countData['is_available'],
    'max_slots' => 1000
]);

