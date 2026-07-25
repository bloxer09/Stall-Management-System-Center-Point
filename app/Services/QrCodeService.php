<?php

namespace App\Services;

class QrCodeService
{
    public function generateSvg(string $data, int $size = 150): string
    {
        $encoded = urlencode($data);
        // Generates an inline SVG/image QR code payload suitable for Dompdf
        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}&format=svg";

        try {
            $svgContent = @file_get_contents($apiUrl);
            if ($svgContent && str_contains($svgContent, '<svg')) {
                return $svgContent;
            }
        } catch (\Exception $e) {
            // Fallback to embedded image link
        }

        return '<img src="https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . $encoded . '" width="' . $size . '" height="' . $size . '" alt="QR Code" />';
    }
}
