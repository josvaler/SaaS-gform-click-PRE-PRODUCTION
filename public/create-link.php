<?php
declare(strict_types=1);

use App\Models\ShortLinkRepository;
use App\Models\QuotaRepository;
use App\Services\UrlValidationService;
use App\Services\ShortCodeService;
use App\Services\QuotaService;
use App\Services\QrCodeService;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$pdo = db();

$shortLinkRepo = new ShortLinkRepository($pdo);
$quotaRepo = new QuotaRepository($pdo);
$quotaService = new QuotaService($quotaRepo);
$urlValidator = new UrlValidationService();
$shortCodeService = new ShortCodeService($shortLinkRepo);
$qrService = new QrCodeService($appConfig['qr_dir'], $appConfig['base_url']);

$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

$error = null;
$success = null;

// Check quota before allowing creation
$quotaCheck = $quotaService->canCreateLink((int)$user['id'], $currentPlan);
if (!$quotaCheck['can_create']) {
    $error = $quotaCheck['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        $error = t('error.invalid_csrf');
    } else {
        $originalUrl = trim($_POST['original_url'] ?? '');
        $customCode = trim($_POST['custom_code'] ?? '');
        $label = trim($_POST['label'] ?? '');
        $expirationDate = trim($_POST['expiration_date'] ?? '');
        
        // Validate Google Forms URL
        $urlValidation = $urlValidator->validateGoogleFormsUrl($originalUrl);
        if (!$urlValidation['valid']) {
            $error = $urlValidation['error'];
        } else {
            // Check quota again
            $quotaCheck = $quotaService->canCreateLink((int)$user['id'], $currentPlan);
            if (!$quotaCheck['can_create']) {
                $error = $quotaCheck['message'];
            } else {
                // Generate or validate short code
                if (!empty($customCode) && ($isPremium || $isEnterprise)) {
                    $codeValidation = $shortCodeService->validateCustomCode($customCode);
                    if (!$codeValidation['valid']) {
                        // Use translation key if available, otherwise use error message
                        $error = isset($codeValidation['error_key']) 
                            ? t($codeValidation['error_key']) 
                            : $codeValidation['error'];
                    } else {
                        $shortCode = $codeValidation['sanitized'];
                    }
                } else {
                    if (!empty($customCode)) {
                        $error = t('link.custom_code_premium_only');
                    } else {
                        $shortCode = $shortCodeService->generateRandomCode();
                    }
                }
                
                if (!$error) {
                    // Validate expiration date
                    $expiresAt = null;
                    if (!empty($expirationDate) && ($isPremium || $isEnterprise)) {
                        // Parse MM/DD/YYYY HH:MM format
                        $parsedDate = null;
                        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $expirationDate, $matches)) {
                            $month = (int)$matches[1];
                            $day = (int)$matches[2];
                            $year = (int)$matches[3];
                            $hour = (int)$matches[4];
                            $minute = (int)$matches[5];
                            
                            if (checkdate($month, $day, $year) && $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                                $parsedDate = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
                            }
                        }
                        
                        if ($parsedDate === null) {
                            $error = t('link.expiration_invalid_format');
                        } else {
                            $expiresAt = $parsedDate;
                            if (strtotime($expiresAt) < time()) {
                                $error = t('link.expiration_future');
                            }
                        }
                    } elseif (!empty($expirationDate)) {
                        $error = t('link.expiration_premium_only');
                    }
                    
                    if (!$error) {
                        // Generate QR code
                        $qrPath = $qrService->generateQrCode($shortCode);
                        
                        // Create short link
                        try {
                            $link = $shortLinkRepo->create([
                                'user_id' => (int)$user['id'],
                                'original_url' => $urlValidation['normalized_url'],
                                'short_code' => $shortCode,
                                'label' => $label ?: null,
                                'expires_at' => $expiresAt,
                                'is_active' => 1,
                                'has_preview_page' => 0,
                                'qr_code_path' => $qrPath,
                            ]);
                            
                            // Record quota usage
                            $quotaService->recordLinkCreation((int)$user['id']);
                            
                            redirect('/link/' . $shortCode);
                        } catch (\Throwable $e) {
                            error_log('Link creation error: ' . $e->getMessage());
                            $error = t('link.create_error');
                        }
                    }
                }
            }
        }
    }
}

// Generate default random code for Premium/Enterprise users
$defaultCustomCode = '';
if ($isPremium || $isEnterprise) {
    $defaultCustomCode = $shortCodeService->generateRandomCode(6);
}

// Generate default expiration date (today + 7 days) for Premium/Enterprise users
$defaultExpirationDate = '';
if ($isPremium || $isEnterprise) {
    $futureDate = new DateTime('+7 days');
    $defaultExpirationDate = $futureDate->format('m/d/Y H:i');
}

$pageTitle = t('link.create');
$navLinksLeft = [
    ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
    ['label' => t('nav.pricing'), 'href' => '/pricing'],
];
$navLinksRight = [
    ['label' => t('nav.logout'), 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 640px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2><?= t('link.create_short') ?></h2>
                    <p class="text-muted"><?= t('link.shorten_description') ?></p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= html($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= html($success) ?></div>
            <?php endif; ?>

            <form method="POST" style="padding: 1.5rem;">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                <div style="margin-bottom: 1.5rem;">
                    <label for="original_url" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        <?= t('link.original_url') ?> *
                    </label>
                    <input 
                        type="url" 
                        id="original_url" 
                        name="original_url" 
                        required 
                        placeholder="https://docs.google.com/forms/..."
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        value="<?= html($_POST['original_url'] ?? '') ?>"
                    >
                    <small style="color: var(--color-text-muted); display: block; margin-top: 0.5rem;">
                        <?= t('link.original_url_required') ?>
                    </small>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        <?= t('link.label') ?>
                    </label>
                    <input 
                        type="text" 
                        id="label" 
                        name="label" 
                        placeholder="<?= t('link.label_placeholder') ?>"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        value="<?= html($_POST['label'] ?? '') ?>"
                    >
                </div>

                <?php if ($isPremium || $isEnterprise): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="custom_code" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            <?= t('link.custom_code') ?>
                        </label>
                        <input 
                            type="text" 
                            id="custom_code" 
                            name="custom_code" 
                            placeholder="<?= t('link.custom_code_placeholder') ?>"
                            pattern="[a-zA-Z0-9_-]+"
                            maxlength="12"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                            value="<?= html($_POST['custom_code'] ?? $defaultCustomCode) ?>"
                        >
                        <small style="color: var(--color-text-muted); display: block; margin-top: 0.5rem;">
                            <?= t('link.custom_code_premium_only') ?>
                        </small>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="expiration_date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            <?= t('link.expiration_date') ?>
                        </label>
                        <input 
                            type="text" 
                            id="expiration_date" 
                            name="expiration_date" 
                            placeholder="MM/DD/YYYY HH:MM"
                            pattern="\d{1,2}/\d{1,2}/\d{4}\s+\d{1,2}:\d{2}"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                            value="<?= html($_POST['expiration_date'] ?? $defaultExpirationDate) ?>"
                        >
                        <small style="color: var(--color-text-muted); display: block; margin-top: 0.5rem;">
                            <?= t('link.expiration_date_format') ?>
                        </small>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?= t('link.submit') ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

