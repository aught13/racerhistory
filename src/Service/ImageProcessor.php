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
    private ?ImageManager $manager = null;

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
                if (class_exists(ImageManager::class)) {
                    if (method_exists(ImageManager::class, 'gd')) {
                        $this->manager = ImageManager::gd();
                    } else {
                        // Try array config (v3) then legacy string
                        try {
                            if (class_exists('Intervention\\Image\\Drivers\\Gd\\Driver')) {
                                $driver = new \Intervention\Image\Drivers\Gd\Driver();
                                $this->manager = new ImageManager($driver);
                            } else {
                                $this->manager = new ImageManager('gd');
                            }
                        } catch (\Throwable $inner) {
                            $this->manager = null; // will degrade
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->manager = null; // operate in degraded mode
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
        if ($this->manager) {
            try {
                $image = $this->manager->read($contents);
            } catch (\Throwable $e) {
                $image = null; // degrade
            }
        } else {
            $image = null;
        }

        if ($image === null) {
            $mime = $file->getClientMediaType() ?: 'image/png';
            $width = 1;
            $height = 1;
            if (function_exists('imagecreatefromstring')) {
                set_error_handler(static function () {
                    // Swallow imagecreatefromstring warnings and continue degraded.
                    return true;
                });
                $tmp = imagecreatefromstring($contents);
                // Always restore the previous handler by popping the last
                // installed handler so the error handler stack matches
                // what it was before set_error_handler() was called.
                restore_error_handler();
                if ($tmp !== false) {
                    $width = imagesx($tmp);
                    $height = imagesy($tmp);
                    imagedestroy($tmp);
                }
            }
            $variants = [];
            foreach ($variantConfig as $name => $cfg) {
                $variants[$name] = [
                    'data' => $contents,
                    'width' => $width,
                    'height' => $height,
                    'mime' => $mime,
                    'ext' => $this->inferExtension($mime),
                ];
            }

            return [
                'original' => [
                    'data' => $contents,
                    'width' => $width,
                    'height' => $height,
                    'mime' => $mime,
                    'ext' => $this->inferExtension($mime),
                ],
                'variants' => $variants,
            ];
        }
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
