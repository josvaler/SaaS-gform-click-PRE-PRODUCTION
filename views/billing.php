<?php
/**
 * Billing Page Template
 * 
 * Expected variables:
 * @var array $user - User session data
 * @var string $currentPlan - Current plan (FREE/PREMIUM/ENTERPRISE)
 * @var bool $isPremium - Whether user has premium plan
 * @var bool $isEnterprise - Whether user has enterprise plan
 * @var string|null $status - Status message (success/cancelled/error/etc)
 * @var bool $hasScheduledCancellation - Whether cancellation is scheduled
 * @var string|null $cancelDateFormatted - Formatted cancellation date
 */
?>
<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 640px;">
        <!-- Current Plan Display -->
        <div class="card" style="margin-bottom: 2rem; <?php echo $isEnterprise ? 'border: 2px solid rgba(167, 139, 250, 0.5);' : ($isPremium ? 'border: 2px solid rgba(34, 211, 238, 0.5);' : 'border: 2px solid rgba(148, 163, 184, 0.3);'); ?>">
            <div style="text-align: center; padding: 1.5rem 0;">
                <div style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">Plan Actual</div>
                <div style="font-size: 3.5rem; font-weight: 700; margin-bottom: 0.5rem; background: <?php echo $isEnterprise ? 'linear-gradient(135deg, #a78bfa, #8b5cf6);' : ($isPremium ? 'linear-gradient(135deg, #6366f1, #22d3ee);' : 'linear-gradient(135deg, #94a3b8, #64748b);'); ?> -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    <?php echo $isEnterprise ? 'ENTERPRISE' : ($isPremium ? 'PREMIUM' : 'FREE'); ?>
                </div>
                <div style="font-size: 1.1rem; color: var(--color-text-muted);">
                    <?php echo $isEnterprise ? '🏢 Acceso Ilimitado' : ($isPremium ? '🎉 Acceso Premium' : 'Acceso Limitado'); ?>
                </div>
                <?php if ($hasScheduledCancellation): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem; margin-left: 1rem; margin-right: 1rem;">
                        <strong>Subscription Cancellation Scheduled</strong><br>
                        Your subscription will cancel on <?= html($cancelDateFormatted) ?>. You'll continue to have access until then.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Premium Plan</h2>
                    <p class="text-muted">Premium features and unlimited access</p>
                </div>
                <span class="badge">Stripe Secure</span>
            </div>

            <?php if ($status === 'success'): ?>
                <div class="alert alert-success">Suscripción confirmada. ¡Disfruta del acceso ilimitado!</div>
            <?php elseif ($status === 'cancelled'): ?>
                <div class="alert alert-error">Checkout cancelado. No se realizó ningún cargo.</div>
            <?php elseif ($status === 'error'): ?>
                <div class="alert alert-error">No pudimos contactar con Stripe. Por favor, intenta de nuevo en un momento.</div>
            <?php elseif ($status === 'portal_error'): ?>
                <div class="alert alert-error">No se pudo acceder al portal de facturación. Por favor, contacta con soporte.</div>
            <?php elseif ($status === 'portal_missing_customer'): ?>
                <div class="alert alert-error">No pudimos localizar tu suscripción en Stripe. Por favor, completa el checkout primero o contacta con soporte para sincronizar tu cuenta.</div>
            <?php elseif ($status === 'customer_missing'): ?>
                <div class="alert alert-error">No encontramos tu suscripción existente en Stripe. Por favor, contacta con soporte antes de intentar actualizar de nuevo.</div>
            <?php endif; ?>

            <?php if ($isEnterprise): ?>
                <div class="alert alert-info">
                    <strong>Plan Enterprise</strong><br>
                    Tienes acceso ilimitado a todas las funciones. Para gestionar tu contrato, contacta con nuestro equipo de ventas.
                </div>
                <a href="mailto:support@gformus.link?subject=Gestión Enterprise" class="btn btn-outline" style="width: 100%;">Contactar Soporte</a>
            <?php else: ?>
                <p style="margin-bottom: 1.5rem;" class="text-muted">
                    Premium desbloquea acceso ilimitado a todas las funciones, procesamiento prioritario y soporte personalizado.
                </p>

                <?php if (!$isPremium): ?>
                    <form action="/stripe/checkout" method="POST" style="margin-bottom: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <button class="btn btn-primary" type="submit" style="width: 100%;">Actualizar a Premium con Stripe</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">Actualmente estás en el plan Premium.</div>
                <?php endif; ?>

                <?php if ($isPremium): ?>
                    <form action="/stripe/portal" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <button class="btn btn-outline" type="submit" style="width: 100%;">Gestionar Suscripción</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

