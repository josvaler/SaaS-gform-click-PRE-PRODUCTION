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
$isPremium = $userPlan === 'PREMIUM';
$isEnterprise = $userPlan === 'ENTERPRISE';
$showAds = $currentUser && $userPlan === 'FREE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <header class="navbar" role="banner">
        <div class="logo">
            <a href="/" class="badge" aria-label="Home">
                <span>✨</span>
                <span><?= html($appConfig['name']) ?></span>
            </a>
        </div>
        <nav class="nav-links" aria-label="Main navigation">
            <?php foreach ($navLinksLeft as $link): ?>
                <a href="<?= html($link['href']) ?>" <?= $isActive($link['href']) ? 'class="active" aria-current="page"' : '' ?>>
                    <?= html($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <nav class="nav-links" aria-label="User navigation">
            <?php foreach ($navLinksRight as $link): ?>
                <a href="<?= html($link['href']) ?>" <?= $isActive($link['href']) ? 'class="active" aria-current="page"' : '' ?>>
                    <?= html($link['label']) ?>
                </a>
            <?php endforeach; ?>
            
            <?php if ($currentUser): ?>
                <div class="user-profile <?= $isPremium ? 'premium' : ($isEnterprise ? 'enterprise' : 'free') ?>">
                    <?php if ($userAvatar): ?>
                        <div class="user-avatar-wrapper">
                            <img src="<?= html($userAvatar) ?>" alt="<?= html($userName) ?>" class="user-avatar">
                        </div>
                    <?php endif; ?>
                    <div class="user-info">
                        <span class="user-name"><?= html($userName) ?></span>
                        <?php if ($userEmail): ?>
                            <span class="user-email" style="font-size: 0.85rem; color: var(--color-text-muted); display: block;"><?= html($userEmail) ?></span>
                        <?php endif; ?>
                        <span class="plan-badge <?= $isPremium ? 'premium-badge' : ($isEnterprise ? 'enterprise-badge' : 'free-badge') ?>">
                            <?= $isEnterprise ? '🏢 ENTERPRISE' : ($isPremium ? '💎 PREMIUM' : '⭐ FREE') ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </header>
    <main>

