<?php
if (!isset($pageTitle)) {
    $pageTitle = 'GForms ShortLinks';
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '/';

$navLinksLeft = $navLinksLeft ?? [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
    ['label' => 'Price', 'href' => '/price'],
];

$navLinksRight = $navLinksRight ?? [
    ['label' => 'My Plan', 'href' => '/billing'],
    ['label' => 'Logout', 'href' => '/logout'],
];

$isActive = function($href) use ($currentPath) {
    return strpos($currentPath, $href) !== false;
};

$currentUser = $_SESSION['user'] ?? null;
$userAvatar = $currentUser['avatar'] ?? $currentUser['avatar_url'] ?? null;
$userName = $currentUser['name'] ?? 'User';
$userEmail = $currentUser['email'] ?? '';
$userPlan = $currentUser['plan'] ?? 'FREE';
$userRole = $currentUser['role'] ?? 'USER';
$isPremium = $userPlan === 'PREMIUM';
$isEnterprise = $userPlan === 'ENTERPRISE';
$isAdmin = $userRole === 'ADMIN';
$showAds = $currentUser && $userPlan === 'FREE';
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($pageTitle) ?></title>
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <header class="navbar" role="banner">
        <div class="navbar-container">
            <div class="logo">
                <a href="/" class="badge" aria-label="Home">
                    <span>✨</span>
                    <span><?= html($appConfig['name']) ?></span>
                </a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Desktop Navigation -->
            <nav class="nav-links nav-desktop" aria-label="Main navigation">
                <?php foreach ($navLinksLeft as $link): ?>
                    <a href="<?= html($link['href']) ?>" class="nav-link <?= $isActive($link['href']) ? 'active' : '' ?>" <?= $isActive($link['href']) ? 'aria-current="page"' : '' ?>>
                        <?= html($link['label']) ?>
                    </a>
                <?php endforeach; ?>
                
                <!-- Language Selector -->
                <div class="language-selector">
                    <form method="POST" action="/set-language">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <select name="lang" onchange="this.form.submit()" title="<?= t('lang.select') ?>">
                            <option value="es" <?= current_lang() === 'es' ? 'selected' : '' ?>><?= t('lang.spanish') ?></option>
                            <option value="en" <?= current_lang() === 'en' ? 'selected' : '' ?>><?= t('lang.english') ?></option>
                        </select>
                    </form>
                </div>
                
                <?php if ($currentUser): ?>
                    <!-- Compact User Profile with Dropdown -->
                    <div class="user-profile-compact">
                        <button class="user-profile-trigger" aria-label="User menu" aria-expanded="false">
                            <?php if ($userAvatar): ?>
                                <img src="<?= html($userAvatar) ?>" alt="<?= html($userName) ?>" class="user-avatar-compact">
                            <?php else: ?>
                                <div class="user-avatar-placeholder"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                            <?php endif; ?>
                            <span class="plan-badge-compact <?= $isAdmin ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : ($isPremium ? 'premium-badge' : 'free-badge')) ?>">
                                <?= $isAdmin ? '👑' : ($isEnterprise ? '🏢' : ($isPremium ? '💎' : '⭐')) ?>
                            </span>
                        </button>
                        <div class="user-dropdown">
                            <div class="user-dropdown-header">
                                <div class="user-dropdown-avatar">
                                    <?php if ($userAvatar): ?>
                                        <img src="<?= html($userAvatar) ?>" alt="<?= html($userName) ?>">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="user-dropdown-info">
                                    <div class="user-dropdown-name"><?= html($userName) ?></div>
                                    <div class="user-dropdown-email"><?= html($userEmail) ?></div>
                                    <div class="user-dropdown-plan <?= $isAdmin ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : ($isPremium ? 'premium-badge' : 'free-badge')) ?>">
                                        <?= $isAdmin ? '👑 ADMIN' : ($isEnterprise ? '🏢 ENTERPRISE' : ($isPremium ? '💎 PREMIUM' : '⭐ FREE')) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="user-dropdown-menu">
                                <?php foreach ($navLinksRight as $link): ?>
                                    <a href="<?= html($link['href']) ?>" class="user-dropdown-item <?= $isActive($link['href']) ? 'active' : '' ?>">
                                        <?= html($link['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php if ($isAdmin): ?>
                                    <a href="/admin" class="user-dropdown-item <?= $isActive('/admin') ? 'active' : '' ?>">
                                        <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i><?= t('nav.admin') ?>
                                    </a>
                                <?php endif; ?>
                                <a href="/profile" class="user-dropdown-item"><?= t('nav.profile') ?></a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login link for non-logged users -->
                    <a href="/login" class="nav-link"><?= t('nav.login') ?></a>
                <?php endif; ?>
            </nav>
            
            <!-- Mobile Navigation -->
            <nav class="nav-mobile" aria-label="Mobile navigation">
                <div class="mobile-menu-content">
                    <?php foreach ($navLinksLeft as $link): ?>
                        <a href="<?= html($link['href']) ?>" class="mobile-nav-link <?= $isActive($link['href']) ? 'active' : '' ?>">
                            <?= html($link['label']) ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if ($currentUser): ?>
                        <?php foreach ($navLinksRight as $link): ?>
                            <a href="<?= html($link['href']) ?>" class="mobile-nav-link <?= $isActive($link['href']) ? 'active' : '' ?>">
                                <?= html($link['label']) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($isAdmin): ?>
                            <a href="/admin" class="mobile-nav-link <?= $isActive('/admin') ? 'active' : '' ?>">
                                <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i><?= t('nav.admin') ?>
                            </a>
                        <?php endif; ?>
                        <a href="/profile" class="mobile-nav-link"><?= t('nav.profile') ?></a>
                        <div class="mobile-user-info">
                            <div class="mobile-user-name"><?= html($userName) ?></div>
                            <div class="mobile-user-email"><?= html($userEmail) ?></div>
                            <div class="mobile-user-plan <?= $isAdmin ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : ($isPremium ? 'premium-badge' : 'free-badge')) ?>">
                                <?= $isAdmin ? '👑 ADMIN' : ($isEnterprise ? '🏢 ENTERPRISE' : ($isPremium ? '💎 PREMIUM' : '⭐ FREE')) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="mobile-nav-link"><?= t('nav.login') ?></a>
                    <?php endif; ?>
                    
                    <!-- Mobile Language Selector -->
                    <div class="mobile-language-selector">
                        <form method="POST" action="/set-language">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <label for="mobile-lang-select"><?= t('lang.select') ?>:</label>
                            <select id="mobile-lang-select" name="lang" onchange="this.form.submit()">
                                <option value="es" <?= current_lang() === 'es' ? 'selected' : '' ?>><?= t('lang.spanish') ?></option>
                                <option value="en" <?= current_lang() === 'en' ? 'selected' : '' ?>><?= t('lang.english') ?></option>
                            </select>
                        </form>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <main>

