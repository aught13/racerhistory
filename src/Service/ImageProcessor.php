<?php
declare(strict_types=1);

namespace App\Service;

use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;

class ImageProcessor
{
    /**
     * Intervention Image manager instance.
     */
    private ImageManager $manager;

    /**
     * @param \Intervention\Image\ImageManager|null $manager Optional pre-configured image manager (for testing / DI).
     */
    public function __construct(?ImageManager $manager = null)
    {
        // For Intervention Image v3 the recommended instantiation is ImageManager::gd() / ::imagick(),
        // but if only v2 is installed, passing driver string is accepted. Use helper if available.
        if ($manager) {
            $this->manager = $manager;
        } else {
            try {
                // Try static factory (v3 style)
                if (method_exists(ImageManager::class, 'gd')) {
                    /** @var \Intervention\Image\ImageManager $mgr */
                    $mgr = ImageManager::gd();
                    $this->manager = $mgr;
                } else {
                    // Fallback older signature expecting driver string
                    $this->manager = new ImageManager('gd');
                }
            } catch (\Throwable $e) {
                // Absolute fallback
                $this->manager = new ImageManager('gd');
            }
        }
    }

    /**
     * Process an uploaded file into original data + configured variants.
     *
     * Contract:
     *  - Returns associative array with keys: original (meta+data) and variants[name].
     *  - Each image meta includes: data (binary string), width, height, mime, ext.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded image file.
     * @param array<string, array<string,mixed>> $variantConfig Variant configuration.
     * @return array<string, mixed>
     */
    public function process(UploadedFileInterface $file, array $variantConfig): array
    {
        $stream = $file->getStream();
        $contents = $stream->getContents();
        $image = $this->manager->read($contents);
        $width = $image->width();
        $height = $image->height();
        $variants = [];
        foreach ($variantConfig as $name => $cfg) {
            $variantImage = clone $image;
            if (isset($cfg['fit'])) {
                [$w,$h] = $cfg['fit'];
                $variantImage->cover($w, $h);
            } elseif (isset($cfg['maxWidth'])) {
                $mw = (int)$cfg['maxWidth'];
                if ($variantImage->width() > $mw) {
                    $variantImage->scale(width: $mw);
                }
            }
            $mimeV = $variantImage->mime ?? 'image/jpeg';
            $variants[$name] = [
                'data' => (string)$variantImage->encode(),
                'width' => $variantImage->width(),
                'height' => $variantImage->height(),
                'mime' => $mimeV,
                'ext' => $this->inferExtension($mimeV),
            ];
        }

        return [
            'original' => [
                'data' => (string)$image->encode(),
                'width' => $width,
                'height' => $height,
                'mime' => $image->mime ?? 'image/jpeg',
                'ext' => $this->inferExtension($image->mime ?? null),
            ],
            'variants' => $variants,
        ];
    }

    /**
     * Infer a file extension from a MIME type.
     *
     * @param string|null $mime MIME type.
     * @return string
     */
    private function inferExtension(?string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
