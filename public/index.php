<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$user = session_user();
$isLoggedIn = $user !== null && !empty($user);

$pageTitle = 'GForms ShortLinks - Acortador de Enlaces para Google Forms';
$navLinksLeft = [
    ['label' => 'Precios', 'href' => '/pricing'],
];
$navLinksRight = $isLoggedIn
    ? [
        ['label' => 'Dashboard', 'href' => '/dashboard'],
        ['label' => 'Mi Plan', 'href' => '/billing'],
        ['label' => 'Logout', 'href' => '/logout'],
    ]
    : [
        ['label' => 'Precios', 'href' => '/pricing'],
        ['label' => 'Login', 'href' => '/login'],
    ];

require __DIR__ . '/../views/partials/header.php';
?>

<section class="hero" style="padding: 4rem 0; background: linear-gradient(to bottom right, #0f172a, #1e293b);">
    <div class="container" style="max-width: 800px; text-align: center;">
        <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1rem; color: white;">
            Acorta tus Enlaces de Google Forms
        </h1>
        <p style="font-size: 1.25rem; color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem;">
            Crea enlaces cortos y memorables para tus formularios de Google. Solo aceptamos URLs de Google Forms.
        </p>
        <?php if (!$isLoggedIn): ?>
            <a href="/login" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.75rem 2rem;">
                Comenzar Gratis
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if ($isLoggedIn): ?>
<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 800px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Crear Enlace Corto</h2>
                    <p class="text-muted">Pega tu URL de Google Forms</p>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                <a href="/create-link" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">
                    Crear Nuevo Enlace
                </a>
            </div>
        </div>

        <!-- Examples -->
        <div style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem;">Ejemplos de URLs válidas:</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem 0;">✓ https://docs.google.com/forms/d/e/...</li>
                <li style="padding: 0.5rem 0;">✓ https://forms.gle/...</li>
            </ul>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Ads for FREE users -->
<?php if ($isLoggedIn && ($user['plan'] ?? 'FREE') === 'FREE' && ($appConfig['ads']['enabled'] ?? true)): ?>
<section style="padding: 2rem 0;">
    <div class="container" style="max-width: 800px;">
        <div class="ad-container" style="text-align: center; padding: 2rem; background: var(--color-bg-secondary, #1e293b); border-radius: 0.5rem;">
            <!-- Ad space - replace with actual ad code -->
            <p style="color: var(--color-text-muted);">Espacio publicitario</p>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

