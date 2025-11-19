<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ShortLinkRepository;
use App\Models\ClickRepository;

class RedirectService
{
    public function __construct(
        private ShortLinkRepository $shortLinkRepo,
        private ClickRepository $clickRepo
    ) {
    }

    public function handleRedirect(string $shortCode): ?array
    {
        $link = $this->shortLinkRepo->findByShortCode($shortCode);
        
        if (!$link) {
            return [
                'success' => false,
                'error' => 'Link not found'
            ];
        }
        
        // Check if link is active
        if (!$link['is_active']) {
            return [
                'success' => false,
                'error' => 'Link is deactivated'
            ];
        }
        
        // Check if link is expired
        if ($link['expires_at'] !== null) {
            $expiresAt = new \DateTime($link['expires_at']);
            $now = new \DateTime();
            if ($now > $expiresAt) {
                return [
                    'success' => false,
                    'error' => 'Link has expired'
                ];
            }
        }
        
        // Record click
        $this->recordClick($link['id']);
        
        return [
            'success' => true,
            'url' => $link['original_url'],
            'has_preview' => (bool)$link['has_preview_page']
        ];
    }

    private function recordClick(int $shortLinkId): void
    {
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        
        $deviceType = $this->detectDeviceType($userAgent);
        $country = $this->detectCountry($ipAddress);
        
        $this->clickRepo->recordClick([
            'short_link_id' => $shortLinkId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'country' => $country,
            'referrer' => $referrer,
        ]);
    }

    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function detectDeviceType(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    private function detectCountry(string $ipAddress): ?string
    {
        // Simple IP geolocation - in production, use a proper service like MaxMind GeoIP2
        // For now, return null (can be enhanced later)
        return null;
    }
}

