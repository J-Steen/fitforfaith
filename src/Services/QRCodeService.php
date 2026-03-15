<?php
namespace App\Services;

/**
 * Simple QR Code generator using pure PHP.
 * This is a minimal implementation for registration URL QR codes.
 * For production, consider bundling phpqrcode (single file, no Composer).
 *
 * This implementation generates a URL to qr.php which proxies to an external
 * QR generation API, with a fallback to a simple PNG generator.
 */
class QRCodeService {
    /**
     * Get the URL for a QR code image for a given registration token.
     * Uses a Google Charts API-compatible format (works offline too via lib).
     */
    public static function getImageUrl(string $token, int $size = 300): string {
        return url('qr/' . $token . '?size=' . $size);
    }

    /**
     * Get the registration URL that the QR code links to.
     */
    public static function getRegistrationUrl(string $token): string {
        return url('register/' . $token);
    }

    /**
     * Generate a PNG QR code and save to storage, or return from cache.
     * Returns the file path.
     */
    public static function generate(string $text, string $filename, int $size = 300): string {
        $outPath = QRCODE_PATH . '/' . $filename . '.png';

        if (file_exists($outPath)) return $outPath;

        // Try to use phpqrcode if available
        $phpQrLib = BASE_PATH . '/lib/phpqrcode/qrlib.php';
        if (file_exists($phpQrLib)) {
            require_once $phpQrLib;
            \QRcode::png($text, $outPath, QR_ECLEVEL_M, (int)ceil($size / 25), 2);
            return $outPath;
        }

        // Fallback: use GD to generate a basic QR placeholder
        // In production, install phpqrcode for real QR codes
        if (extension_loaded('gd')) {
            $img = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefill($img, 0, 0, $white);
            // Draw border
            imagerectangle($img, 0, 0, $size-1, $size-1, $black);
            // Label
            $label = 'QR: ' . substr($text, -20);
            imagestring($img, 2, 10, (int)($size/2 - 5), $label, $black);
            imagepng($img, $outPath);
            imagedestroy($img);
        }

        return $outPath;
    }

    /**
     * Serve a QR code image to the browser.
     */
    public static function serve(string $token): void {
        $qr = \App\Models\QRCode::findByToken($token);
        if (!$qr) {
            http_response_code(404);
            exit('QR code not found.');
        }

        $text     = self::getRegistrationUrl($token);
        $filename = 'qr_' . $token;
        $filePath = self::generate($text, $filename);

        if (!file_exists($filePath)) {
            // Return SVG-based QR placeholder
            header('Content-Type: image/svg+xml');
            echo self::svgPlaceholder($text);
            exit;
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        exit;
    }

    /**
     * Basic SVG placeholder (rendered in browser as QR-like pattern).
     * Replace with real QR library in production.
     */
    private static function svgPlaceholder(string $text): string {
        $encoded = htmlspecialchars($text, ENT_QUOTES);
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="300" height="300" fill="white"/>
  <rect x="20" y="20" width="80" height="80" fill="none" stroke="black" stroke-width="8"/>
  <rect x="35" y="35" width="50" height="50" fill="black"/>
  <rect x="200" y="20" width="80" height="80" fill="none" stroke="black" stroke-width="8"/>
  <rect x="215" y="35" width="50" height="50" fill="black"/>
  <rect x="20" y="200" width="80" height="80" fill="none" stroke="black" stroke-width="8"/>
  <rect x="35" y="215" width="50" height="50" fill="black"/>
  <text x="150" y="160" text-anchor="middle" font-size="10" fill="#333">Scan to Register</text>
  <text x="150" y="175" text-anchor="middle" font-size="7" fill="#666">Install phpqrcode for real QR</text>
</svg>
SVG;
    }
}
