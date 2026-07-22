<?php

namespace App\Services;

class QrCodeGenerator
{
    /**
     * Generates a clean SVG QR code data URI or SVG string for verification links.
     * Uses Google Chart API / QR Server fallback or inline SVG renderer.
     */
    public static function getQrCodeUrl(string $data, int $size = 150): string
    {
        $encoded = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}";
    }

    public static function renderSvgQrCode(string $data, int $size = 150): string
    {
        $url = self::getQrCodeUrl($data, $size);
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="QR Code" width="' . $size . '" height="' . $size . '" style="border: 1px solid #e5e7eb; padding: 4px; background: #fff;" />';
    }
}
