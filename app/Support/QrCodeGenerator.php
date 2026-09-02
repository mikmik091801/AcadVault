<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Renders QR codes as PNG.
 *
 * Why not SimpleSoftwareIO\QrCode directly: its PNG writer requires the
 * imagick extension, which is not installed here, and its SVG output is
 * silently dropped by dompdf (verified — an SVG produces no image object and
 * no vector fills in the generated PDF, while a PNG produces a proper image
 * XObject). So the QR matrix comes from bacon/bacon-qr-code, which ships with
 * simple-qrcode, and is rasterised here with GD. One PNG then serves both the
 * PDF and the browser.
 */
class QrCodeGenerator
{
    /**
     * Render $content as a QR code PNG.
     *
     * @param  int  $size  Target width/height in pixels (approximate — the
     *                     final size snaps to a whole number of modules).
     * @param  int  $margin  Quiet zone in modules (the QR spec requires 4).
     */
    public function png(string $content, int $size = 220, int $margin = 4): string
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M())->getMatrix();

        $modules = $matrix->getWidth();
        $total = $modules + ($margin * 2);

        // Whole-pixel modules keep the code crisp and scannable.
        $scale = max(1, (int) floor($size / $total));
        $dimension = $total * $scale;

        $image = imagecreatetruecolor($dimension, $dimension);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $dimension - 1, $dimension - 1, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $margin) * $scale;
                    $py = ($y + $margin) * $scale;
                    imagefilledrectangle($image, $px, $py, $px + $scale - 1, $py + $scale - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * The same PNG as a data: URI, ready to drop into an <img src>.
     */
    public function dataUri(string $content, int $size = 220, int $margin = 4): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($content, $size, $margin));
    }
}
