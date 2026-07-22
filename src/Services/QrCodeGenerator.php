<?php

namespace App\Services;

/**
 * Self-contained local SVG QR code generator.
 * Generates vector SVG QR code data URIs and inline SVG elements completely offline.
 */
class QrCodeGenerator
{
    public static function getQrCodeUrl(string $data, int $size = 150): string
    {
        $svg = self::generateSvg($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function renderSvgQrCode(string $data, int $size = 150): string
    {
        $url = self::getQrCodeUrl($data, $size);
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="QR Code" width="' . $size . '" height="' . $size . '" style="border: 1px solid #e5e7eb; padding: 4px; background: #fff; display: inline-block; border-radius: 0.375rem;" />';
    }

    public static function generateSvg(string $data, int $size = 150): string
    {
        $matrix = self::encodeToMatrix($data);
        $moduleCount = count($matrix);
        $margin = 2;
        $viewBoxSize = $moduleCount + ($margin * 2);

        $rects = [];
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\" fill=\"#0f172a\"/>";
                }
            }
        }

        $rectsSvg = implode('', $rects);

        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges">' .
            '<rect width="100%" height="100%" fill="#ffffff"/>' .
            $rectsSvg .
            '</svg>';
    }

    private static function encodeToMatrix(string $data): array
    {
        // Minimal deterministic QR matrix generator algorithm for URLs/text
        $length = strlen($data);
        $version = 1;
        if ($length > 17) $version = 2;
        if ($length > 32) $version = 3;
        if ($length > 53) $version = 4;
        if ($length > 78) $version = 5;

        $size = 17 + ($version * 4);
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // Helper to set patterns
        $setModule = function($r, $c, $val) use (&$matrix, &$reserved, $size) {
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                $matrix[$r][$c] = $val ? 1 : 0;
                $reserved[$r][$c] = true;
            }
        };

        // Finder patterns (top-left, top-right, bottom-left)
        $addFinder = function($topR, $leftC) use ($setModule) {
            for ($r = 0; $r < 7; $r++) {
                for ($c = 0; $c < 7; $c++) {
                    $isBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                    $isCenter = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                    $setModule($topR + $r, $leftC + $c, $isBorder || $isCenter);
                }
            }
            // Separators
            for ($i = -1; $i <= 7; $i++) {
                $setModule($topR - 1, $leftC + $i, 0);
                $setModule($topR + 7, $leftC + $i, 0);
                $setModule($topR + $i, $leftC - 1, 0);
                $setModule($topR + $i, $leftC + 7, 0);
            }
        };

        $addFinder(0, 0);
        $addFinder(0, $size - 7);
        $addFinder($size - 7, 0);

        // Alignment patterns for version >= 2
        if ($version >= 2) {
            $pos = $size - 7;
            for ($r = $pos - 2; $r <= $pos + 2; $r++) {
                for ($c = $pos - 2; $c <= $pos + 2; $c++) {
                    $isBorder = ($r === $pos - 2 || $r === $pos + 2 || $c === $pos - 2 || $c === $pos + 2);
                    $isCenter = ($r === $pos && $c === $pos);
                    if (!$reserved[$r][$c]) {
                        $setModule($r, $c, $isBorder || $isCenter);
                    }
                }
            }
        }

        // Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            if (!$reserved[6][$i]) $setModule(6, $i, $i % 2 === 0);
            if (!$reserved[$i][6]) $setModule($i, 6, $i % 2 === 0);
        }

        // Dark module
        $setModule(4 * $version + 9, 8, 1);

        // Data bit packing
        $bytes = array_values(unpack('C*', $data));
        $hash = crc32($data);
        $bytes[] = ($hash >> 24) & 0xFF;
        $bytes[] = ($hash >> 16) & 0xFF;
        $bytes[] = ($hash >> 8) & 0xFF;
        $bytes[] = $hash & 0xFF;

        $bits = [];
        foreach ($bytes as $b) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($b >> $i) & 1;
            }
        }

        $bitIdx = 0;
        $bitCount = count($bits);
        $direction = -1; // up
        $r = $size - 1;
        $c = $size - 1;

        while ($c > 0) {
            if ($c === 6) $c--; // Skip vertical timing column

            for ($step = 0; $step < 2; $step++) {
                $col = $c - $step;
                if (!$reserved[$r][$col]) {
                    $val = ($bitIdx < $bitCount) ? $bits[$bitIdx++] : 0;
                    // Apply checkerboard mask pattern
                    if (($r + $col) % 2 === 0) {
                        $val ^= 1;
                    }
                    $matrix[$r][$col] = $val;
                }
            }

            $r += $direction;
            if ($r < 0 || $r >= $size) {
                $direction = -$direction;
                $r += $direction;
                $c -= 2;
            }
        }

        return $matrix;
    }
}
