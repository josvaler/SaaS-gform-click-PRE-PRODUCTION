<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;

require __DIR__ . '/../../../config/bootstrap.php';
require_auth();

header('Content-Type: application/json');

$user = session_user();
if (!$user || empty($user['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$pdo = db();
$shortLinkRepo = new ShortLinkRepository($pdo);

// Get parameters
$query = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20))); // Limit to 100 max
$offset = ($page - 1) * $perPage;

// Normalize empty strings to null
$query = empty($query) ? null : $query;
$status = ($status === 'all' || empty($status)) ? null : $status;
$dateFrom = empty($dateFrom) ? null : $dateFrom;
$dateTo = empty($dateTo) ? null : $dateTo;

// Get links with filters
$links = $shortLinkRepo->searchByUserWithFilters(
    (int)$user['id'],
    $query,
    $status,
    $dateFrom,
    $dateTo,
    $perPage,
    $offset
);

// Get total count
$total = $shortLinkRepo->countByUserWithFilters(
    (int)$user['id'],
    $query,
    $status,
    $dateFrom,
    $dateTo
);

$totalPages = (int)ceil($total / $perPage);

echo json_encode([
    'success' => true,
    'links' => $links,
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage,
    'total_pages' => $totalPages
]);

