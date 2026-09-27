<?php

namespace App\Service;

/**
 * Redimensionne une photo et la convertit en WebP (souvent 5 à 10 fois plus léger
 * qu'un JPEG ou PNG de smartphone, sans perte visible).
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;
    public const QUALITY = 82;

    /**
     * @return string chemin du fichier WebP créé (l'original est supprimé)
     *
     * @throws \RuntimeException si l'image est illisible
     */
    public function toWebp(string $path): string
    {
        // Une photo 4000×3000 décompressée pèse ~50 Mo, ~100 Mo pendant le redimensionnement.
        $previousLimit = ini_get('memory_limit');
        if ('-1' !== $previousLimit && $this->toBytes($previousLimit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        try {
            return $this->convert($path);
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    private function convert(string $path): string
    {
        $info = @getimagesize($path);
        $image = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };
        if (!$image) {
            throw new \RuntimeException(sprintf('Image illisible : %s', basename($path)));
        }

        if (IMAGETYPE_JPEG === $info[2]) {
            $image = $this->applyExifOrientation($image, $path);
        }

        if (imagesx($image) > self::MAX_WIDTH) {
            $scaled = imagescale($image, self::MAX_WIDTH, -1, IMG_BICUBIC);
            imagedestroy($image);
            $image = $scaled;
        }

        // Conserve la transparence des PNG.
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $target = preg_replace('/\.(jpe?g|png|webp)$/i', '', $path).'.webp';
        if (!imagewebp($image, $target, self::QUALITY)) {
            throw new \RuntimeException(sprintf('Conversion WebP impossible : %s', basename($path)));
        }
        imagedestroy($image);

        if ($target !== $path) {
            @unlink($path);
        }

        return $target;
    }

    private function toBytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /** Les photos de téléphone sont souvent stockées « couchées » avec une indication de rotation. */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if (0 === $angle) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated;
    }
}
