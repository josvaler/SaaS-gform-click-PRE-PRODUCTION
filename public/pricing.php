<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$user = session_user();
$currentPlan = $user ? ($user['plan'] ?? 'FREE') : 'FREE';
$isLoggedIn = $user !== null;

$pageTitle = 'Precios';
$navLinksLeft = [
    ['label' => 'Inicio', 'href' => '/'],
];
$navLinksRight = $isLoggedIn
    ? [
        ['label' => 'Dashboard', 'href' => '/dashboard'],
        ['label' => 'Mi Plan', 'href' => '/billing'],
        ['label' => 'Logout', 'href' => '/logout'],
    ]
    : [
        ['label' => 'Login', 'href' => '/login'],
    ];

require __DIR__ . '/../views/partials/header.php';
?>

<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 1200px;">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 3rem; margin-bottom: 1rem;">Elige tu Plan</h1>
            <p style="font-size: 1.25rem; color: var(--color-text-muted);">Planes diseñados para todas tus necesidades</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- FREE Plan -->
            <div class="card" style="<?= $currentPlan === 'FREE' ? 'border: 2px solid #60a5fa;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2>FREE</h2>
                        <p class="text-muted">Para empezar</p>
                    </div>
                    <span class="badge free-badge">Gratis</span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        $0<span style="font-size: 1rem; color: var(--color-text-muted);">/mes</span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ 10 enlaces por día</li>
                        <li style="padding: 0.5rem 0;">✓ 200 enlaces por mes</li>
                        <li style="padding: 0.5rem 0;">✓ Códigos aleatorios</li>
                        <li style="padding: 0.5rem 0;">✓ Estadísticas básicas</li>
                        <li style="padding: 0.5rem 0;">✗ Sin códigos personalizados</li>
                        <li style="padding: 0.5rem 0;">✗ Sin fechas de expiración</li>
                    </ul>
                    <?php if ($currentPlan === 'FREE'): ?>
                        <div class="alert alert-info">Plan Actual</div>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline" style="width: 100%;">Empezar Gratis</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PREMIUM Plan -->
            <div class="card" style="<?= $currentPlan === 'PREMIUM' ? 'border: 2px solid #22d3ee;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2>PREMIUM</h2>
                        <p class="text-muted">Para profesionales</p>
                    </div>
                    <span class="badge premium-badge">Popular</span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        $4.99<span style="font-size: 1rem; color: var(--color-text-muted);">/mes</span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ 600 enlaces por mes</li>
                        <li style="padding: 0.5rem 0;">✓ Sin límite diario</li>
                        <li style="padding: 0.5rem 0;">✓ Códigos personalizados</li>
                        <li style="padding: 0.5rem 0;">✓ Fechas de expiración</li>
                        <li style="padding: 0.5rem 0;">✓ Estadísticas avanzadas</li>
                        <li style="padding: 0.5rem 0;">✓ Gestión de enlaces</li>
                    </ul>
                    <?php if ($currentPlan === 'PREMIUM'): ?>
                        <div class="alert alert-success">Plan Actual</div>
                    <?php elseif ($isLoggedIn): ?>
                        <form action="/stripe/checkout" method="POST" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Actualizar a Premium</button>
                        </form>
                    <?php else: ?>
                        <a href="/login" class="btn btn-primary" style="width: 100%;">Comenzar Premium</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ENTERPRISE Plan -->
            <div class="card" style="<?= $currentPlan === 'ENTERPRISE' ? 'border: 2px solid #a78bfa;' : '' ?>">
                <div class="card-header">
                    <div>
                        <h2>ENTERPRISE</h2>
                        <p class="text-muted">Para empresas</p>
                    </div>
                    <span class="badge enterprise-badge">Enterprise</span>
                </div>
                <div style="padding: 1.5rem;">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                        <span style="font-size: 1rem; color: var(--color-text-muted);">A medida</span>
                    </div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem;">
                        <li style="padding: 0.5rem 0;">✓ Enlaces ilimitados</li>
                        <li style="padding: 0.5rem 0;">✓ Sin límites</li>
                        <li style="padding: 0.5rem 0;">✓ Todas las funciones</li>
                        <li style="padding: 0.5rem 0;">✓ Soporte prioritario</li>
                        <li style="padding: 0.5rem 0;">✓ Dominios personalizados</li>
                        <li style="padding: 0.5rem 0;">✓ Facturación empresarial</li>
                    </ul>
                    <?php if ($currentPlan === 'ENTERPRISE'): ?>
                        <div class="alert alert-success">Plan Actual</div>
                    <?php else: ?>
                        <a href="mailto:support@gformus.link?subject=Solicitud Enterprise" class="btn btn-outline" style="width: 100%;">Contactar Ventas</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

