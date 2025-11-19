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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => t('error.invalid_csrf')]);
    exit;
}

$linkId = (int)($_POST['link_id'] ?? 0);
if ($linkId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid link ID']);
    exit;
}

$pdo = db();
$shortLinkRepo = new ShortLinkRepository($pdo);

// Verify ownership
$link = $shortLinkRepo->findById($linkId);
if (!$link || (int)$link['user_id'] !== (int)$user['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Link not found or access denied']);
    exit;
}

// Toggle status
$newStatus = $link['is_active'] == 1 ? 0 : 1;

// If activating, check if another active link with the same short code exists
if ($newStatus == 1) {
    if ($shortLinkRepo->hasActiveLinkWithCode($link['short_code'], $linkId)) {
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => t('link.activation_conflict')
        ]);
        exit;
    }
}

$shortLinkRepo->update($linkId, ['is_active' => $newStatus]);

echo json_encode([
    'success' => true,
    'is_active' => $newStatus,
    'message' => $newStatus == 1 ? t('explore.toggle_activate') : t('explore.toggle_deactivate')
]);

