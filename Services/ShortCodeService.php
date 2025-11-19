<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ShortLinkRepository;

class ShortCodeService
{
    public function __construct(
        private ShortLinkRepository $shortLinkRepo
    ) {
    }

    public function generateRandomCode(int $length = 6): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, $max)];
        }
        
        // Check uniqueness, regenerate if needed
        $attempts = 0;
        while (!$this->shortLinkRepo->isShortCodeUnique($code) && $attempts < 10) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, $max)];
            }
            $attempts++;
        }
        
        return $code;
    }

    public function validateCustomCode(string $code): array
    {
        // Check length
        if (strlen($code) < 3) {
            return [
                'valid' => false,
                'error' => 'El código personalizado debe tener al menos 3 caracteres.'
            ];
        }
        
        if (strlen($code) > 50) {
            return [
                'valid' => false,
                'error' => 'El código personalizado no puede tener más de 50 caracteres.'
            ];
        }

        // Check format: alphanumeric, hyphens, underscores only
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
            return [
                'valid' => false,
                'error' => 'El código personalizado solo puede contener letras, números, guiones y guiones bajos.'
            ];
        }

        // Check for reserved words (add more as needed)
        $reserved = ['admin', 'api', 'login', 'logout', 'dashboard', 'profile', 'billing', 'pricing', 'create-link', 'links', 'stripe'];
        if (in_array(strtolower($code), $reserved)) {
            return [
                'valid' => false,
                'error' => 'Este código está reservado y no está disponible.'
            ];
        }

        // Check uniqueness
        if (!$this->shortLinkRepo->isShortCodeUnique($code)) {
            return [
                'valid' => false,
                'error' => 'Este código ya está en uso. Por favor, elige otro.'
            ];
        }

        return [
            'valid' => true,
            'sanitized' => $this->sanitizeCode($code)
        ];
    }

    public function sanitizeCode(string $code): string
    {
        // Remove any invalid characters
        $code = preg_replace('/[^a-zA-Z0-9_-]/', '', $code);
        // Convert to lowercase for consistency (optional - remove if case-sensitive)
        return $code;
    }
}

