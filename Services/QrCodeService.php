<?php
declare(strict_types=1);

namespace App\Services;

class QrCodeService
{
    private string $qrDirectory;
    private string $baseUrl;

    public function __construct(string $qrDirectory, string $baseUrl)
    {
        $this->qrDirectory = rtrim($qrDirectory, '/');
        $this->baseUrl = rtrim($baseUrl, '/');
        
        // Create directory if it doesn't exist
        if (!is_dir($this->qrDirectory)) {
            mkdir($this->qrDirectory, 0755, true);
        }
    }

    public function generateQrCode(string $shortCode, string $filename = null): ?string
    {
        $url = $this->baseUrl . '/' . $shortCode;
        
        if ($filename === null) {
            $filename = $shortCode . '.png';
        }
        
        $filePath = $this->qrDirectory . '/' . $filename;
        
        // Use simple QR code generation (requires endroid/qr-code package)
        // For now, we'll create a placeholder that can be replaced with actual QR library
        try {
            // Check if endroid/qr-code is available
            if (class_exists('\Endroid\QrCode\QrCode')) {
                $qrCode = new \Endroid\QrCode\QrCode($url);
                $qrCode->setSize(300);
                $qrCode->setMargin(10);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                file_put_contents($filePath, $result->getString());
            } else {
                // Fallback: Use Google Charts API (simple but requires internet)
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
                $qrImage = file_get_contents($qrUrl);
                
                if ($qrImage !== false) {
                    file_put_contents($filePath, $qrImage);
                } else {
                    // If API fails, return null
                    return null;
                }
            }
            
            return '/qr/' . $filename;
        } catch (\Throwable $e) {
            error_log('QR Code generation error: ' . $e->getMessage());
            return null;
        }
    }

    public function getQrCodePath(string $shortCode): string
    {
        return '/qr/' . $shortCode . '.png';
    }

    public function deleteQrCode(string $filename): bool
    {
        $filePath = $this->qrDirectory . '/' . basename($filename);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}

