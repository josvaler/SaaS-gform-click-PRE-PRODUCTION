<?php
declare(strict_types=1);

namespace App\Services;

class UrlValidationService
{
    private const ALLOWED_DOMAINS = [
        'docs.google.com',
        'forms.gle',
    ];

    private const ALLOWED_PATHS = [
        '/forms/',
    ];

    public function validateGoogleFormsUrl(string $url): array
    {
        // Check if URL is empty
        if (empty(trim($url))) {
            return [
                'valid' => false,
                'error' => 'La URL no puede estar vacía.'
            ];
        }

        // Parse URL
        $parsedUrl = parse_url($url);
        
        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            return [
                'valid' => false,
                'error' => 'La URL proporcionada no es válida.'
            ];
        }

        $host = strtolower($parsedUrl['host']);
        $path = $parsedUrl['path'] ?? '';

        // Check if host is allowed
        $isAllowedDomain = false;
        foreach (self::ALLOWED_DOMAINS as $allowedDomain) {
            if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                $isAllowedDomain = true;
                break;
            }
        }

        if (!$isAllowedDomain) {
            return [
                'valid' => false,
                'error' => 'Solo se permiten URLs de Google Forms (docs.google.com/forms/ o forms.gle/).'
            ];
        }

        // For docs.google.com, check if path contains /forms/
        if ($host === 'docs.google.com' || str_ends_with($host, '.docs.google.com')) {
            if (strpos($path, '/forms/') === false) {
                return [
                    'valid' => false,
                    'error' => 'La URL debe ser un formulario de Google Forms (debe contener /forms/).'
                ];
            }
        }

        // Ensure URL uses HTTPS
        $scheme = $parsedUrl['scheme'] ?? '';
        if ($scheme !== 'https') {
            return [
                'valid' => false,
                'error' => 'La URL debe usar HTTPS.'
            ];
        }

        return [
            'valid' => true,
            'normalized_url' => $this->normalizeUrl($url)
        ];
    }

    private function normalizeUrl(string $url): string
    {
        // Remove fragment and normalize
        $parsedUrl = parse_url($url);
        $normalized = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        if (isset($parsedUrl['path'])) {
            $normalized .= $parsedUrl['path'];
        }
        
        if (isset($parsedUrl['query'])) {
            $normalized .= '?' . $parsedUrl['query'];
        }
        
        return $normalized;
    }
}

