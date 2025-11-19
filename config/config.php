<?php
declare(strict_types=1);

return [
    'name' => 'GForms ShortLinks',
    'base_url' => env('APP_URL', 'http://localhost'),
    'uploads_dir' => __DIR__ . '/../../uploads',
    'processed_dir' => __DIR__ . '/../../processed',
    'qr_dir' => __DIR__ . '/../../public/qr',
    'quota_limits' => [
        'FREE' => ['daily' => 10, 'monthly' => 200],
        'PREMIUM' => ['daily' => null, 'monthly' => 600],
        'ENTERPRISE' => ['daily' => null, 'monthly' => null],
    ],
    'ads' => [
        'enabled' => env('ADS_ENABLED', 'true') === 'true',
        'provider' => env('ADS_PROVIDER', 'google'),
    ],
];

