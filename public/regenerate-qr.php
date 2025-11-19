<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;
use App\Services\QrCodeService;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
if (!$user) {
    redirect('/login');
}

$shortCode = $_GET['code'] ?? '';

if (empty($shortCode)) {
    redirect('/dashboard');
}

try {
    $pdo = db();
    $shortLinkRepo = new ShortLinkRepository($pdo);
    $link = $shortLinkRepo->findByShortCode($shortCode);

    if (!$link || !isset($link['user_id']) || !isset($link['id']) || $link['user_id'] != $user['id']) {
        redirect('/dashboard');
    }

    // Regenerate QR code
    $qrService = new QrCodeService($appConfig['qr_dir'], $appConfig['base_url']);
    $qrPath = $qrService->generateQrCode($shortCode);

    if ($qrPath) {
        // Update the link with the new QR path
        $shortLinkRepo->update((int)$link['id'], ['qr_code_path' => $qrPath]);
        redirect('/link/' . $shortCode);
    } else {
        redirect('/link/' . $shortCode . '?error=qr_generation_failed');
    }
} catch (\Throwable $e) {
    error_log('Regenerate QR error: ' . $e->getMessage());
    redirect('/link/' . $shortCode . '?error=qr_generation_failed');
}

