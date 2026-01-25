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
     * @param array<string,mixed> $manipulations Optional image manipulations to apply.
     * @return array<string, mixed>
     */
    public function process(
        UploadedFileInterface $file,
        array $variantConfig,
        array $manipulations = [],
    ): array {
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
                    if (function_exists('imagedestroy')) {
                        $fn = 'imagedestroy';
                        $fn($tmp);
                    }
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
        // Apply manipulations to the original image if provided
        if (!empty($manipulations)) {
            $image = $this->applyManipulations($image, $manipulations);
        }

        $width = $image->width();
        $height = $image->height();
        $variants = [];
        foreach ($variantConfig as $name => $cfg) {
            $variantImage = clone $image;
            // Apply custom crop first if specified (for custom thumbnail positioning)
            if (isset($cfg['crop']) && is_array($cfg['crop'])) {
                $cx = (int)($cfg['crop']['x'] ?? 0);
                $cy = (int)($cfg['crop']['y'] ?? 0);
                $cw = (int)($cfg['crop']['width'] ?? $variantImage->width());
                $ch = (int)($cfg['crop']['height'] ?? $variantImage->height());
                if ($cw > 0 && $ch > 0) {
                    $variantImage->crop($cw, $ch, $cx, $cy);
                }
            }
            if (isset($cfg['fit'])) {
                [$w, $h] = $cfg['fit'];
                $variantImage->cover($w, $h);
            } elseif (isset($cfg['maxWidth'])) {
                $mw = (int)$cfg['maxWidth'];
                if ($variantImage->width() > $mw) {
                    $variantImage->scale(width: $mw);
                }
            }
            // Respect format config for variants
            $targetFormat = $cfg['format'] ?? null;
            if ($targetFormat === 'webp') {
                $encoded = (string)$variantImage->toWebp();
                $mimeV = 'image/webp';
            } else {
                $encoded = (string)$variantImage->encode();
                $mimeV = $variantImage->mime ?? 'image/jpeg';
            }
            $variants[$name] = [
                'data' => $encoded,
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
     * Manipulate existing image file (for post-upload editing).
     *
     * @param string $fileContent Raw file content.
     * @param string $mimeType MIME type of the image.
     * @param array<string, array<string,mixed>> $variantConfig Variant configuration.
     * @param array<string,mixed> $manipulations Image manipulations to apply.
     * @return array<string, mixed>
     */
    public function manipulateExisting(
        string $fileContent,
        string $mimeType,
        array $variantConfig,
        array $manipulations,
    ): array {
        if ($this->manager === null) {
            // Fallback if Intervention Image not available
            return [
                'original' => [
                    'data' => $fileContent,
                    'width' => 0,
                    'height' => 0,
                    'mime' => $mimeType,
                    'ext' => $this->inferExtension($mimeType),
                ],
                'variants' => [],
            ];
        }

        try {
            $image = $this->manager->read($fileContent);
        } catch (\Throwable $e) {
            // Fallback on read failure
            return [
                'original' => [
                    'data' => $fileContent,
                    'width' => 0,
                    'height' => 0,
                    'mime' => $mimeType,
                    'ext' => $this->inferExtension($mimeType),
                ],
                'variants' => [],
            ];
        }

        // Apply manipulations to the original image
        if (!empty($manipulations)) {
            $image = $this->applyManipulations($image, $manipulations);
        }

        $width = $image->width();
        $height = $image->height();
        $variants = [];
        foreach ($variantConfig as $name => $cfg) {
            $variantImage = clone $image;
            // Apply custom crop first if specified (for custom thumbnail positioning)
            if (isset($cfg['crop']) && is_array($cfg['crop'])) {
                $cx = (int)($cfg['crop']['x'] ?? 0);
                $cy = (int)($cfg['crop']['y'] ?? 0);
                $cw = (int)($cfg['crop']['width'] ?? $variantImage->width());
                $ch = (int)($cfg['crop']['height'] ?? $variantImage->height());
                if ($cw > 0 && $ch > 0) {
                    $variantImage->crop($cw, $ch, $cx, $cy);
                }
            }
            if (isset($cfg['fit'])) {
                [$w, $h] = $cfg['fit'];
                $variantImage->cover($w, $h);
            } elseif (isset($cfg['maxWidth'])) {
                $mw = (int)$cfg['maxWidth'];
                if ($variantImage->width() > $mw) {
                    $variantImage->scale(width: $mw);
                }
            }
            // Respect format config for variants
            $targetFormat = $cfg['format'] ?? null;
            if ($targetFormat === 'webp') {
                $encoded = (string)$variantImage->toWebp();
                $mimeV = 'image/webp';
            } else {
                $encoded = (string)$variantImage->encode();
                $mimeV = $variantImage->mime ?? 'image/jpeg';
            }
            $variants[$name] = [
                'data' => $encoded,
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
     * Attach tags to an image (creates tags on demand).
     *
     * @param int $imageId Image id.
     * @param array<int|string,string> $tags Tag names or slugs.
     * @return void
     */
    public function attachTags(int $imageId, array $tags): void
    {
        (new ImageTagService())->attachTags($imageId, $tags);
    }

    /**
     * Get images that match all given tag slugs.
     *
     * @param array<int,string> $tagSlugs Tag slugs that must all be present.
     * @param int $limit Result limit.
     * @return array<int,\App\Model\Entity\Image>
     */
    public function getImagesByAllTags(array $tagSlugs, int $limit = 10): array
    {
        return (new ImageTagService())->getImagesByAllTags($tagSlugs, $limit);
    }

    /**
     * Convenience: images tagged for a person.
     *
     * @param int $personId Person id.
     * @param int $limit Limit.
     * @return array<int,\App\Model\Entity\Image>
     */
    public function getImagesForPerson(int $personId, int $limit = 10): array
    {
        return (new ImageTagService())->getImagesForPerson($personId, $limit);
    }

    /**
     * Convenience: images tagged for a team season.
     */
    public function getImagesForTeamSeason(int $teamSeasonId, int $limit = 10): array
    {
        return (new ImageTagService())->getImagesForTeamSeason($teamSeasonId, $limit);
    }

    /**
     * Convenience: roster image (person + team season).
     */
    public function getRosterImages(int $personId, int $teamSeasonId, int $limit = 1): array
    {
        return (new ImageTagService())->getRosterImages($personId, $teamSeasonId, $limit);
    }

    /**
     * Resolve or create and return ImageTags for provided slugs (utility for controllers).
     *
     * @param array<int,string> $tagSlugs Tag slugs.
     * @return array<int,\App\Model\Entity\ImageTag>
     */
    public function ensureTags(array $tagSlugs): array
    {
        return (new ImageTagService())->ensureTags($tagSlugs);
    }

    /**
     * Apply image manipulations (crop, rotate, brightness, contrast).
     *
     * @param mixed $image Image instance.
     * @param array<string,mixed> $manipulations Manipulations array.
     * @return mixed
     */
    private function applyManipulations(
        mixed $image,
        array $manipulations,
    ): mixed {
        // Rotate FIRST: expects angle (degrees, can be negative, normalized to 0..359)
        if (isset($manipulations['rotate'])) {
            $angle = (int)round((float)$manipulations['rotate']);
            $angle = $angle % 360;
            if ($angle !== 0) {
                // Intervention rotates counter-clockwise; user input is clockwise
                $image->rotate(-$angle);
            }
        }

        // Crop AFTER rotation: expects x, y, width, height in rotated image coordinates
        if (!empty($manipulations['crop'])) {
            $crop = $manipulations['crop'];
            if (isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
                $x = (int)$crop['x'];
                $y = (int)$crop['y'];
                $w = (int)$crop['width'];
                $h = (int)$crop['height'];
                // Ensure bounds are valid (post-rotation size)
                $maxW = $image->width();
                $maxH = $image->height();
                if ($x < 0) {
                    $x = 0;
                }
                if ($y < 0) {
                    $y = 0;
                }
                if ($x + $w > $maxW) {
                    $w = $maxW - $x;
                }
                if ($y + $h > $maxH) {
                    $h = $maxH - $y;
                }
                if ($w > 0 && $h > 0) {
                    $image->crop($w, $h, $x, $y);
                }
            }
        }

        // Brightness: expects value (-100 to 100)
        if (isset($manipulations['brightness'])) {
            $brightness = (int)$manipulations['brightness'];
            if ($brightness !== 0) {
                $image->brightness($brightness);
            }
        }

        // Contrast: expects value (-100 to 100)
        if (isset($manipulations['contrast'])) {
            $contrast = (int)$manipulations['contrast'];
            if ($contrast !== 0) {
                $image->contrast($contrast);
            }
        }

        // Blur: expects value (0-100, optional)
        if (!empty($manipulations['blur'])) {
            $blur = (int)$manipulations['blur'];
            if ($blur > 0 && $blur <= 100) {
                $image->blur($blur);
            }
        }

        return $image;
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
