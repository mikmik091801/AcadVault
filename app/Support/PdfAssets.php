<?php

namespace App\Support;

/**
 * Shared dompdf hardening for every PDF the app produces.
 *
 * Both fixes here exist because dompdf fails *silently* — it drops an image
 * and still returns a valid-looking PDF, so a certificate can lose its QR
 * code without anything erroring. Keeping them in one place means a new
 * document type cannot accidentally ship without them.
 */
class PdfAssets
{
    /**
     * A writable scratch directory for dompdf, created on first use.
     *
     * dompdf spools every data: URI image through a temp file. Left to
     * sys_get_temp_dir() that can resolve to a directory the web user cannot
     * write — on Windows the built-in server gets C:\WINDOWS — and every
     * image is then discarded without an error.
     */
    public static function tempDir(): string
    {
        $dir = storage_path('app/dompdf');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * The institution mark, flattened onto white and inlined as a data URI.
     *
     * The source PNG is truecolour+alpha. dompdf embeds transparency as a
     * separate soft-mask XObject built through a second temp file, and when
     * that fails the mark renders as a solid black box. Document headers are
     * white, so flattening is visually identical and removes the failure mode.
     */
    public static function logoDataUri(): ?string
    {
        $path = public_path('images/logo-mark.png');

        if (! is_file($path)) {
            return null;
        }

        $source = @imagecreatefrompng($path);

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $flat = imagecreatetruecolor($width, $height);
        imagefilledrectangle($flat, 0, 0, $width - 1, $height - 1, imagecolorallocate($flat, 255, 255, 255));

        imagealphablending($flat, true);
        imagecopy($flat, $source, 0, 0, 0, 0, $width, $height);
        imagesavealpha($flat, false);

        ob_start();
        imagepng($flat, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($flat);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
