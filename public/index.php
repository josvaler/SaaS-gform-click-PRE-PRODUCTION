<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$user = session_user();
$isLoggedIn = $user !== null && !empty($user);

$pageTitle = t('landing.title');
$navLinksLeft = [
    ['label' => t('nav.pricing'), 'href' => '/pricing'],
];
$navLinksRight = $isLoggedIn
    ? [
        ['label' => t('nav.dashboard'), 'href' => '/dashboard'],
        ['label' => t('nav.my_plan'), 'href' => '/billing'],
        ['label' => t('nav.logout'), 'href' => '/logout'],
    ]
    : [
        ['label' => t('nav.pricing'), 'href' => '/pricing'],
        ['label' => t('nav.login'), 'href' => '/login'],
    ];

require __DIR__ . '/../views/partials/header.php';

// Get Early Bird promotion data
$earlyBirdData = get_early_bird_count();
$promotionsEnabled = env('GFORMS_PROMOTIONS', 'false') === 'true';
$currentPlan = $user ? ($user['plan'] ?? 'FREE') : 'FREE';
$showPromotion = $promotionsEnabled && $earlyBirdData['is_available'] && $currentPlan !== 'PREMIUM';
?>

<?php if ($showPromotion): ?>
<!-- Early Bird Sticky Banner -->
<div id="early-bird-banner" class="early-bird-banner" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1f2937; padding: 0.75rem 1rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 0.9rem; font-weight: 600;">
    <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; justify-content: center; max-width: 1200px;">
        <span style="font-size: 1.2rem;">🔥</span>
        <span><?= t('promo.banner_text') ?></span>
        <span style="background: rgba(31, 41, 55, 0.1); padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-weight: 700; white-space: nowrap;"><?= t('promo.banner_badge') ?></span>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <a href="/pricing" class="btn" style="background: #1f2937; color: white; padding: 0.5rem 1.25rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.85rem; font-weight: 600; white-space: nowrap; transition: all 0.2s;"><?= t('promo.view_offer') ?></a>
        <button id="close-banner" style="background: transparent; border: none; color: #1f2937; font-size: 1.5rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
    </div>
</div>

<!-- Early Bird Floating Badge -->
<div id="early-bird-badge" class="early-bird-badge" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 999; cursor: pointer;">
    <div class="badge-minimized" style="width: 60px; height: 60px; background: linear-gradient(135deg, #fbbf24, #f59e0b); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(251, 191, 36, 0.4); transition: all 0.3s ease; animation: pulse-glow 3s ease-in-out infinite;">
        <span style="font-size: 1.5rem;">🔥</span>
    </div>
    <div class="badge-expanded" style="display: none; background: linear-gradient(135deg, #fbbf24, #f59e0b); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 8px 30px rgba(251, 191, 36, 0.4); min-width: 200px; max-width: 280px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <span style="font-size: 1.2rem;">🔥</span>
            <button id="close-badge" style="background: transparent; border: none; color: #1f2937; font-size: 1.25rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
        </div>
        <div style="color: #1f2937; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem;"><?= t('promo.early_bird_title') ?></div>
        <div style="color: #374151; font-size: 0.9rem; margin-bottom: 0.75rem;"><?= t('promo.early_bird_desc') ?></div>
        <div id="badge-counter" style="background: rgba(31, 41, 55, 0.1); padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-weight: 700; font-size: 0.95rem; color: #1f2937; text-align: center;">
            <span id="remaining-count"><?= $earlyBirdData['remaining'] ?></span> <?= t('promo.slots_remaining') ?>
        </div>
        <a href="/pricing" style="display: block; margin-top: 0.75rem; background: #1f2937; color: white; padding: 0.625rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; font-weight: 600; text-align: center; transition: all 0.2s;"><?= t('promo.claim_offer') ?></a>
    </div>
</div>
<?php endif; ?>

<section class="hero" style="padding: 4rem 0; background: linear-gradient(to bottom right, #0f172a, #1e293b); <?= $showPromotion ? 'margin-top: 60px;' : '' ?>">
    <div class="container" style="max-width: 1400px;">
        <div class="hero-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center;">
            <!-- Left: Text Content -->
            <div style="text-align: left;">
                <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1rem; color: white; line-height: 1.2;">
                    <?= t('landing.hero_title') ?>
                </h1>
                <p style="font-size: 1.25rem; color: rgba(255, 255, 255, 0.8); margin-bottom: 2.5rem; line-height: 1.6;">
                    <?= t('landing.hero_subtitle') ?>
                </p>
                <?php if (!$isLoggedIn): ?>
                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 1rem;">
                        <a href="/login" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 3rem; font-weight: 600; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3); transition: all 0.3s ease;">
                            <?= t('landing.start_free') ?>
                        </a>
                        <p style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7); margin: 0;">
                            <?= t('landing.hero_cta_subtext') ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right: Animation -->
            <div class="hero-animation-container" style="display: flex; justify-content: center; align-items: center; min-height: 400px;">
                <div class="link-transformation-animation" id="linkAnimation">
                    <!-- Long URL -->
                    <div class="animation-step step-1" style="position: absolute; width: 100%;">
                        <div class="long-url-box" style="background: rgba(17, 24, 39, 0.8); border: 2px solid rgba(148, 163, 184, 0.3); border-radius: 0.75rem; padding: 1.5rem; font-family: 'Courier New', monospace; font-size: 0.85rem; color: var(--text-primary); word-break: break-all; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
                            https://docs.google.com/forms/d/e/1FAIpQLSdXyZ1234567890...
                        </div>
                    </div>
                    
                    <!-- Transformation Arrow/Effect -->
                    <div class="animation-step step-2" style="position: absolute; width: 100%; opacity: 0;">
                        <div class="transformation-effect" style="text-align: center; padding: 2rem 0;">
                            <div class="processing-spinner" style="width: 60px; height: 60px; margin: 0 auto; border: 4px solid rgba(14, 165, 233, 0.2); border-top-color: var(--accent-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <p style="color: var(--accent-primary); margin-top: 1rem; font-weight: 600;">Processing...</p>
                        </div>
                    </div>
                    
                    <!-- Short Link + QR Code -->
                    <div class="animation-step step-3" style="position: absolute; width: 100%; opacity: 0;">
                        <div class="result-container" style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                            <div class="short-link-box" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(45, 212, 191, 0.2)); border: 2px solid var(--accent-primary); border-radius: 0.75rem; padding: 1.5rem; font-family: 'Courier New', monospace; font-size: 1.1rem; color: var(--accent-primary); font-weight: 600; box-shadow: 0 8px 30px rgba(14, 165, 233, 0.3);">
                                gforms.click/abc123
                            </div>
                            <div class="qr-code-box" style="background: white; padding: 1rem; border-radius: 0.75rem; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);">
                                <svg width="120" height="120" viewBox="0 0 120 120" style="display: block;">
                                    <!-- QR Code Pattern (simplified representation) -->
                                    <rect width="120" height="120" fill="white"/>
                                    <!-- Corner squares -->
                                    <rect x="10" y="10" width="30" height="30" fill="black"/>
                                    <rect x="12" y="12" width="26" height="26" fill="white"/>
                                    <rect x="16" y="16" width="18" height="18" fill="black"/>
                                    <rect x="80" y="10" width="30" height="30" fill="black"/>
                                    <rect x="82" y="12" width="26" height="26" fill="white"/>
                                    <rect x="86" y="16" width="18" height="18" fill="black"/>
                                    <rect x="10" y="80" width="30" height="30" fill="black"/>
                                    <rect x="12" y="82" width="26" height="26" fill="white"/>
                                    <rect x="16" y="86" width="18" height="18" fill="black"/>
                                    <!-- Data pattern (simplified) -->
                                    <rect x="50" y="10" width="8" height="8" fill="black"/>
                                    <rect x="70" y="10" width="8" height="8" fill="black"/>
                                    <rect x="10" y="50" width="8" height="8" fill="black"/>
                                    <rect x="30" y="50" width="8" height="8" fill="black"/>
                                    <rect x="50" y="50" width="8" height="8" fill="black"/>
                                    <rect x="70" y="50" width="8" height="8" fill="black"/>
                                    <rect x="90" y="50" width="8" height="8" fill="black"/>
                                    <rect x="50" y="70" width="8" height="8" fill="black"/>
                                    <rect x="90" y="70" width="8" height="8" fill="black"/>
                                    <rect x="30" y="90" width="8" height="8" fill="black"/>
                                    <rect x="50" y="90" width="8" height="8" fill="black"/>
                                    <rect x="70" y="90" width="8" height="8" fill="black"/>
                                    <rect x="90" y="90" width="8" height="8" fill="black"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section (only for non-logged-in users) -->
<?php if (!$isLoggedIn): ?>
<section class="how-it-works-section" style="padding: 5rem 0; background: var(--color-bg, #0b0f19);">
    <div class="container" style="max-width: 1200px;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <?= t('landing.how_it_works_title') ?>
            </h2>
            <p style="font-size: 1.1rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                <?= t('landing.how_it_works_subtitle') ?>
            </p>
        </div>
        
        <div class="how-it-works-steps" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
            <!-- Step 1 -->
            <div class="how-it-works-step" style="text-align: center; padding: 2rem; background: rgba(17, 24, 39, 0.4); border-radius: 1rem; border: 1px solid rgba(148, 163, 184, 0.1); transition: all 0.3s ease;">
                <div class="step-number" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: white; box-shadow: 0 4px 20px rgba(14, 165, 233, 0.3);">
                    1
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">
                    <?= t('landing.step_1_title') ?>
                </h3>
                <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                    <?= t('landing.step_1_description') ?>
                </p>
            </div>
            
            <!-- Step 2 -->
            <div class="how-it-works-step" style="text-align: center; padding: 2rem; background: rgba(17, 24, 39, 0.4); border-radius: 1rem; border: 1px solid rgba(148, 163, 184, 0.1); transition: all 0.3s ease;">
                <div class="step-number" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: white; box-shadow: 0 4px 20px rgba(45, 212, 191, 0.3);">
                    2
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">
                    <?= t('landing.step_2_title') ?>
                </h3>
                <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                    <?= t('landing.step_2_description') ?>
                </p>
            </div>
            
            <!-- Step 3 -->
            <div class="how-it-works-step" style="text-align: center; padding: 2rem; background: rgba(17, 24, 39, 0.4); border-radius: 1rem; border: 1px solid rgba(148, 163, 184, 0.1); transition: all 0.3s ease;">
                <div class="step-number" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #a855f7, var(--accent-primary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: white; box-shadow: 0 4px 20px rgba(168, 85, 247, 0.3);">
                    3
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">
                    <?= t('landing.step_3_title') ?>
                </h3>
                <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                    <?= t('landing.step_3_description') ?>
                </p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="/login" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.875rem 2.5rem; font-weight: 600; box-shadow: 0 6px 20px rgba(14, 165, 233, 0.25);">
                <?= t('landing.try_it_now') ?>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($isLoggedIn): ?>
<section style="padding: 4rem 0;">
    <div class="container" style="max-width: 800px;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2><?= t('landing.create_short_link') ?></h2>
                    <p class="text-muted"><?= t('landing.paste_url') ?></p>
                </div>
            </div>
            <div style="padding: 1.5rem;">
                <a href="/create-link" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">
                    <?= t('landing.create_new_link') ?>
                </a>
            </div>
        </div>

        <!-- Examples -->
        <div style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem;"><?= t('landing.valid_urls_examples') ?></h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem 0;">✓ https://docs.google.com/forms/d/e/...</li>
                <li style="padding: 0.5rem 0;">✓ https://forms.gle/...</li>
            </ul>
        </div>
    </div>
</section>

<!-- Key Features Section (only for non-logged-in users) -->
<section class="key-features-section" style="padding: 5rem 0; background: linear-gradient(to bottom, var(--color-bg, #0b0f19), rgba(17, 24, 39, 0.5));">
    <div class="container" style="max-width: 1200px;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <?= t('landing.features_title') ?>
            </h2>
        </div>
        
        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
            <!-- Feature 1: Centralized Management -->
            <div class="feature-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2.5rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="feature-icon" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);">
                    📊
                </div>
                <div class="feature-badge" style="position: absolute; top: 1rem; right: 1rem; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-primary)); color: white; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600;">
                    <?= t('landing.feature_1_badge') ?>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; text-align: center;">
                    <?= t('landing.feature_1_title') ?>
                </h3>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; text-align: center; margin: 0;">
                    <?= t('landing.feature_1_description') ?>
                </p>
            </div>
            
            <!-- Feature 2: Advanced Analytics -->
            <div class="feature-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2.5rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="feature-icon" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-secondary)); border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 8px 24px rgba(45, 212, 191, 0.3);">
                    📈
                </div>
                <div class="feature-badge" style="position: absolute; top: 1rem; right: 1rem; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-primary)); color: white; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600;">
                    <?= t('landing.feature_2_badge') ?>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; text-align: center;">
                    <?= t('landing.feature_2_title') ?>
                </h3>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; text-align: center; margin: 0;">
                    <?= t('landing.feature_2_description') ?>
                </p>
            </div>
            
            <!-- Feature 3: Automatic QR Codes -->
            <div class="feature-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2.5rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="feature-icon" style="width: 64px; height: 64px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #a855f7, var(--accent-primary)); border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 8px 24px rgba(168, 85, 247, 0.3);">
                    📱
                </div>
                <div class="feature-badge" style="position: absolute; top: 1rem; right: 1rem; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-secondary)); color: white; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600;">
                    <?= t('landing.feature_3_badge') ?>
                </div>
                <h3 style="font-size: 1.4rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; text-align: center;">
                    <?= t('landing.feature_3_title') ?>
                </h3>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; text-align: center; margin: 0;">
                    <?= t('landing.feature_3_description') ?>
                </p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="/pricing" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.875rem 2.5rem; font-weight: 600; box-shadow: 0 6px 20px rgba(14, 165, 233, 0.25);">
                <?= t('landing.view_premium_plans') ?>
            </a>
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
            <p style="color: var(--color-text-muted);"><?= t('landing.ad_space') ?></p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials Section (only for non-logged-in users) -->
<?php if (!$isLoggedIn): ?>
<section class="testimonials-section" style="padding: 5rem 0; background: linear-gradient(to bottom, transparent, rgba(14, 165, 233, 0.05));">
    <div class="container" style="max-width: 1200px;">
        <h2 style="text-align: center; font-size: 2.5rem; font-weight: 700; margin-bottom: 3rem; color: var(--text-primary); background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            <?= t('landing.testimonials_title') ?>
        </h2>
        <div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
            <!-- Testimonial 1 -->
            <div class="testimonial-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="testimonial-quote-icon" style="position: absolute; top: 1rem; right: 1rem; font-size: 3rem; color: rgba(14, 165, 233, 0.2); line-height: 1;">"</div>
                <p class="testimonial-quote" style="font-size: 1rem; line-height: 1.7; color: var(--text-primary); margin-bottom: 1.5rem; position: relative; z-index: 1;">
                    <?= t('landing.testimonial_1_quote') ?>
                </p>
                <div class="testimonial-author" style="display: flex; align-items: center; gap: 0.75rem; position: relative; z-index: 1;">
                    <div class="testimonial-avatar" style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.1rem; flex-shrink: 0;">
                        <?= substr(t('landing.testimonial_1_author'), 0, 1) ?>
                    </div>
                    <div>
                        <div class="testimonial-name" style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">
                            <?= t('landing.testimonial_1_author') ?>
                        </div>
                        <div class="testimonial-role" style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                            <?= t('landing.testimonial_1_role') ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="testimonial-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="testimonial-quote-icon" style="position: absolute; top: 1rem; right: 1rem; font-size: 3rem; color: rgba(14, 165, 233, 0.2); line-height: 1;">"</div>
                <p class="testimonial-quote" style="font-size: 1rem; line-height: 1.7; color: var(--text-primary); margin-bottom: 1.5rem; position: relative; z-index: 1;">
                    <?= t('landing.testimonial_2_quote') ?>
                </p>
                <div class="testimonial-author" style="display: flex; align-items: center; gap: 0.75rem; position: relative; z-index: 1;">
                    <div class="testimonial-avatar" style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-highlight), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.1rem; flex-shrink: 0;">
                        <?= substr(t('landing.testimonial_2_author'), 0, 1) ?>
                    </div>
                    <div>
                        <div class="testimonial-name" style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">
                            <?= t('landing.testimonial_2_author') ?>
                        </div>
                        <div class="testimonial-role" style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                            <?= t('landing.testimonial_2_role') ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 3 -->
            <div class="testimonial-card" style="background: rgba(17, 24, 39, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(148, 163, 184, 0.15); border-radius: 1rem; padding: 2rem; transition: all 0.3s ease; position: relative; overflow: hidden;">
                <div class="testimonial-quote-icon" style="position: absolute; top: 1rem; right: 1rem; font-size: 3rem; color: rgba(14, 165, 233, 0.2); line-height: 1;">"</div>
                <p class="testimonial-quote" style="font-size: 1rem; line-height: 1.7; color: var(--text-primary); margin-bottom: 1.5rem; position: relative; z-index: 1;">
                    <?= t('landing.testimonial_3_quote') ?>
                </p>
                <div class="testimonial-author" style="display: flex; align-items: center; gap: 0.75rem; position: relative; z-index: 1;">
                    <div class="testimonial-avatar" style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #a855f7, var(--accent-primary)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.1rem; flex-shrink: 0;">
                        <?= substr(t('landing.testimonial_3_author'), 0, 1) ?>
                    </div>
                    <div>
                        <div class="testimonial-name" style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;">
                            <?= t('landing.testimonial_3_author') ?>
                        </div>
                        <div class="testimonial-role" style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                            <?= t('landing.testimonial_3_role') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Section (only for non-logged-in users) -->
<section class="final-cta-section" style="padding: 5rem 0; background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(45, 212, 191, 0.1)); position: relative; overflow: hidden;">
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 50% 50%, rgba(14, 165, 233, 0.15) 0%, transparent 70%); pointer-events: none;"></div>
    <div class="container" style="max-width: 800px; text-align: center; position: relative; z-index: 1;">
        <h2 style="font-size: 2.75rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary); line-height: 1.2;">
            <?= t('landing.final_cta_title') ?>
        </h2>
        <p style="font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">
            <?= t('landing.final_cta_subtitle') ?>
        </p>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; align-items: center;">
                <a href="/login" class="btn btn-primary final-cta-button" style="font-size: 1.3rem; padding: 1.25rem 4rem; font-weight: 700; box-shadow: 0 12px 40px rgba(14, 165, 233, 0.4); transition: all 0.3s ease; border-radius: 0.75rem;">
                    <?= t('landing.final_cta_button') ?>
                </a>
                <a href="/pricing" class="btn btn-secondary final-cta-button-secondary" style="font-size: 1.1rem; padding: 1rem 2.5rem; font-weight: 600; background: rgba(17, 24, 39, 0.8); border: 2px solid var(--accent-primary); color: var(--accent-primary); transition: all 0.3s ease; border-radius: 0.75rem; text-decoration: none;">
                    <?= t('landing.final_cta_button_secondary') ?>
                </a>
            </div>
            <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;">
                <?= t('landing.final_cta_footer') ?>
            </p>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($showPromotion): ?>
<script>
(function() {
    'use strict';
    
    // Banner management
    const banner = document.getElementById('early-bird-banner');
    const closeBannerBtn = document.getElementById('close-banner');
    const badge = document.getElementById('early-bird-badge');
    const badgeMinimized = badge?.querySelector('.badge-minimized');
    const badgeExpanded = badge?.querySelector('.badge-expanded');
    const closeBadgeBtn = document.getElementById('close-badge');
    const remainingCount = document.getElementById('remaining-count');
    
    // Check localStorage for dismissed state
    const bannerDismissed = localStorage.getItem('earlyBirdBannerDismissed') === 'true';
    const badgeDismissed = localStorage.getItem('earlyBirdBadgeDismissed') === 'true';
    
    // Auto-collapse banner after 5 seconds if not dismissed
    if (banner && !bannerDismissed) {
        setTimeout(() => {
            if (banner && !localStorage.getItem('earlyBirdBannerDismissed')) {
                banner.style.transform = 'translateY(-100%)';
                banner.style.transition = 'transform 0.3s ease';
            }
        }, 5000);
    } else if (banner && bannerDismissed) {
        banner.style.display = 'none';
    }
    
    // Hide badge if dismissed
    if (badge && badgeDismissed) {
        badge.style.display = 'none';
    }
    
    // Close banner
    if (closeBannerBtn) {
        closeBannerBtn.addEventListener('click', function() {
            if (banner) {
                banner.style.transform = 'translateY(-100%)';
                banner.style.transition = 'transform 0.3s ease';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 300);
                localStorage.setItem('earlyBirdBannerDismissed', 'true');
            }
        });
    }
    
    // Badge expand/collapse
    if (badgeMinimized && badgeExpanded) {
        let isExpanded = false;
        
        badgeMinimized.addEventListener('click', function() {
            if (!isExpanded) {
                badgeMinimized.style.display = 'none';
                badgeExpanded.style.display = 'block';
                isExpanded = true;
            }
        });
        
        if (closeBadgeBtn) {
            closeBadgeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                badgeExpanded.style.display = 'none';
                badgeMinimized.style.display = 'flex';
                isExpanded = false;
                localStorage.setItem('earlyBirdBadgeDismissed', 'true');
                setTimeout(() => {
                    if (badge) badge.style.display = 'none';
                }, 300);
            });
        }
    }
    
    // Update counter every 30 seconds
    if (remainingCount) {
        function updateCounter() {
            fetch('/api/early-bird-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.is_available && remainingCount) {
                        remainingCount.textContent = data.remaining;
                    } else if (badge) {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error updating early bird counter: ' + error);
                });
        }
        
        // Update immediately and then every 30 seconds
        updateCounter();
        setInterval(updateCounter, 30000);
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>

