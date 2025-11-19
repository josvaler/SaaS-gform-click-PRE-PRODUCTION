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
        $error = 'Token de seguridad inválido. Por favor, intenta de nuevo.';
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
                        $error = $codeValidation['error'];
                    } else {
                        $shortCode = $codeValidation['sanitized'];
                    }
                } else {
                    if (!empty($customCode)) {
                        $error = 'Los códigos personalizados solo están disponibles para usuarios PREMIUM o ENTERPRISE.';
                    } else {
                        $shortCode = $shortCodeService->generateRandomCode();
                    }
                }
                
                if (!$error) {
                    // Validate expiration date
                    $expiresAt = null;
                    if (!empty($expirationDate) && ($isPremium || $isEnterprise)) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime($expirationDate));
                        if ($expiresAt === false || strtotime($expiresAt) < time()) {
                            $error = 'La fecha de expiración debe ser en el futuro.';
                        }
                    } elseif (!empty($expirationDate)) {
                        $error = 'Las fechas de expiración solo están disponibles para usuarios PREMIUM o ENTERPRISE.';
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
                            $error = 'Error al crear el enlace. Por favor, intenta de nuevo.';
                        }
                    }
                }
            }
        }
    }
}

$pageTitle = 'Crear Enlace';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Precios', 'href' => '/pricing'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 640px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Crear Enlace Corto</h2>
                    <p class="text-muted">Acorta tu enlace de Google Forms</p>
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
                        URL de Google Forms *
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
                        Solo se permiten URLs de Google Forms (docs.google.com/forms/ o forms.gle/)
                    </small>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="label" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Etiqueta (opcional)
                    </label>
                    <input 
                        type="text" 
                        id="label" 
                        name="label" 
                        placeholder="Mi formulario de registro"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        value="<?= html($_POST['label'] ?? '') ?>"
                    >
                </div>

                <?php if ($isPremium || $isEnterprise): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="custom_code" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            Código Personalizado (opcional)
                        </label>
                        <input 
                            type="text" 
                            id="custom_code" 
                            name="custom_code" 
                            placeholder="mi-formulario"
                            pattern="[a-zA-Z0-9_-]+"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                            value="<?= html($_POST['custom_code'] ?? '') ?>"
                        >
                        <small style="color: var(--color-text-muted); display: block; margin-top: 0.5rem;">
                            Solo letras, números, guiones y guiones bajos. Mínimo 3 caracteres.
                        </small>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="expiration_date" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            Fecha de Expiración (opcional)
                        </label>
                        <input 
                            type="datetime-local" 
                            id="expiration_date" 
                            name="expiration_date" 
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                            value="<?= html($_POST['expiration_date'] ?? '') ?>"
                        >
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Crear Enlace
                </button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

