<?php
declare(strict_types=1);

use App\Models\UserRepository;

require __DIR__ . '/../config/bootstrap.php';
require_auth();

$user = session_user();
$pdo = db();
$userRepo = new UserRepository($pdo);

// Get fresh user data from database
$dbUser = $userRepo->findByGoogleId($user['google_id'] ?? '');
if ($dbUser) {
    $user = array_merge($user, $dbUser);
}

$currentPlan = ($user['plan'] ?? 'FREE');
$isPremium = ($currentPlan === 'PREMIUM');
$isEnterprise = ($currentPlan === 'ENTERPRISE');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        $error = 'Token de seguridad inválido. Por favor, intenta de nuevo.';
    } else {
        $profileData = [
            'country' => trim($_POST['country'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'bio' => trim($_POST['bio'] ?? ''),
            'locale' => trim($_POST['locale'] ?? ''),
        ];
        
        // Country is required
        if (empty($profileData['country'])) {
            $error = 'El país es obligatorio.';
        } else {
            try {
                $userRepo->updateProfile((int)$user['id'], $profileData);
                $success = 'Perfil actualizado correctamente.';
                // Refresh user data
                $dbUser = $userRepo->findByGoogleId($user['google_id'] ?? '');
                if ($dbUser) {
                    $user = array_merge($user, $dbUser);
                    $_SESSION['user'] = array_merge($_SESSION['user'], $dbUser);
                }
            } catch (\Throwable $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $error = 'Error al actualizar el perfil. Por favor, intenta de nuevo.';
            }
        }
    }
}

// Countries list (simplified - in production use a proper list)
$countries = [
    'ES' => 'España', 'MX' => 'México', 'AR' => 'Argentina', 'CO' => 'Colombia',
    'CL' => 'Chile', 'PE' => 'Perú', 'VE' => 'Venezuela', 'EC' => 'Ecuador',
    'GT' => 'Guatemala', 'CU' => 'Cuba', 'BO' => 'Bolivia', 'DO' => 'República Dominicana',
    'HN' => 'Honduras', 'PY' => 'Paraguay', 'SV' => 'El Salvador', 'NI' => 'Nicaragua',
    'CR' => 'Costa Rica', 'PA' => 'Panamá', 'UY' => 'Uruguay', 'US' => 'Estados Unidos',
    'BR' => 'Brasil', 'CA' => 'Canadá', 'FR' => 'Francia', 'DE' => 'Alemania',
    'IT' => 'Italia', 'GB' => 'Reino Unido', 'PT' => 'Portugal', 'OT' => 'Otro',
];

$pageTitle = 'Mi Perfil';
$navLinksLeft = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Mi Plan', 'href' => '/billing'],
];
$navLinksRight = [
    ['label' => 'Logout', 'href' => '/logout'],
];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 800px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Mi Perfil</h2>
                    <p class="text-muted">Administra tu información personal</p>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <?php if ($user['avatar_url'] ?? null): ?>
                        <img src="<?= html($user['avatar_url']) ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--color-border, #334155);">
                    <?php endif; ?>
                    <span class="badge <?= $isPremium ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : 'free-badge') ?>">
                        <?= $currentPlan ?>
                    </span>
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

                <!-- Google Info (read-only) -->
                <div style="margin-bottom: 2rem; padding: 1rem; background: var(--color-bg-secondary, #1e293b); border-radius: 0.5rem;">
                    <h3 style="margin-bottom: 1rem;">Información de Google</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Nombre:</strong><br>
                            <?= html($user['name'] ?? 'N/A') ?>
                        </div>
                        <div>
                            <strong>Email:</strong><br>
                            <?= html($user['email'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <!-- Editable Fields -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="country" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        País *
                    </label>
                    <select 
                        id="country" 
                        name="country" 
                        required
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                    >
                        <option value="">Selecciona un país</option>
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?= html($code) ?>" <?= ($user['country'] ?? '') === $code ? 'selected' : '' ?>>
                                <?= html($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="city" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Ciudad
                    </label>
                    <input 
                        type="text" 
                        id="city" 
                        name="city" 
                        value="<?= html($user['city'] ?? '') ?>"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                    >
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="address" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Dirección
                    </label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        value="<?= html($user['address'] ?? '') ?>"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                    >
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label for="postal_code" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            Código Postal
                        </label>
                        <input 
                            type="text" 
                            id="postal_code" 
                            name="postal_code" 
                            value="<?= html($user['postal_code'] ?? '') ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                    <div>
                        <label for="phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                            Teléfono
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?= html($user['phone'] ?? '') ?>"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                        >
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="company" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Empresa
                    </label>
                    <input 
                        type="text" 
                        id="company" 
                        name="company" 
                        value="<?= html($user['company'] ?? '') ?>"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                    >
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="website" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Sitio Web
                    </label>
                    <input 
                        type="url" 
                        id="website" 
                        name="website" 
                        value="<?= html($user['website'] ?? '') ?>"
                        placeholder="https://ejemplo.com"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9);"
                    >
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="bio" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Biografía
                    </label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        rows="4"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--color-border, #334155); background: var(--color-bg, #0f172a); color: var(--color-text, #f1f5f9); resize: vertical;"
                    ><?= html($user['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

