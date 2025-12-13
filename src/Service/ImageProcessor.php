<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
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
            if (isset($cfg['fit'])) {
                [$w, $h] = $cfg['fit'];
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
            if (isset($cfg['fit'])) {
                [$w, $h] = $cfg['fit'];
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
     * Attach tags to an image (creates tags on demand).
     *
     * @param int $imageId Image id.
     * @param array<int|string,string> $tags Tag names or slugs.
     * @return void
     */
    public function attachTags(int $imageId, array $tags): void
    {
        if (!$tags) {
            return;
        }

        $tagsTable = $this->table('ImageTags');
        $imagesTable = $this->table('Images');
        /** @var \App\Model\Entity\Image $image */
        $image = $imagesTable->get($imageId, contain: ['ImageTags']);

        // Get existing tag IDs for this image
        $existingTagIds = [];
        foreach ($image->image_tags as $tag) {
            $existingTagIds[] = $tag->id;
        }

        $tagEntities = [];
        foreach ($tags as $tag) {
            $name = trim((string)$tag);
            if ($name === '') {
                continue;
            }
            $slug = Text::slug($name) ?: strtolower($name);
            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $name, 'slug' => $slug]);
                $tagsTable->save($existing);
            }
            if (!in_array($existing->id, $existingTagIds)) {
                $tagEntities[] = $existing;
            }
        }

        if ($tagEntities) {
            // @phpstan-ignore property.notFound
            $imagesTable->ImageTags->link($image, $tagEntities);
        }
    }

    /**
     * Record an image usage (idempotent on same tuple).
     *
     * @param int $imageId Image ID.
     * @param string $model Model name.
     * @param int $foreignKey Foreign key value.
     * @param string|null $context Optional context.
     * @param string|null $field Optional field.
     * @return void
     */
    public function recordUsage(
        int $imageId,
        string $model,
        int $foreignKey,
        ?string $context = null,
        ?string $field = null,
    ): void {
        $usages = $this->table('ImageUsages');
        $existing = $usages->find()->where([
            'image_id' => $imageId,
            'model' => $model,
            'foreign_key' => $foreignKey,
            'context' => $context,
            'field' => $field,
        ])->first();
        if ($existing) {
            return;
        }
        $usage = $usages->newEntity([
            'image_id' => $imageId,
            'model' => $model,
            'foreign_key' => $foreignKey,
            'context' => $context,
            'field' => $field,
        ]);
        $usages->save($usage);
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
        $tagSlugs = array_values(array_filter(array_map('strval', $tagSlugs)));
        if (!$tagSlugs) {
            return [];
        }
        $needed = count($tagSlugs);
        $images = $this->table('Images');

        // Use select with tag_count and raw string in HAVING (CakePHP has issues with alias binding in HAVING)
        $query = $images->find()
            ->select($images)
            ->select(['tag_count' => $images->query()->func()->count('DISTINCT ImageTags.slug')])
            ->matching('ImageTags', function ($q) use ($tagSlugs) {
                return $q->where(['ImageTags.slug IN' => $tagSlugs]);
            })
            ->groupBy(['Images.id'])
            ->having("tag_count >= {$needed}")
            ->limit($limit);

        return $query->all()->toList();
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
        return $this->getImagesByAllTags(["person-{$personId}"], $limit);
    }

    /**
     * Convenience: images tagged for a team season.
     */
    public function getImagesForTeamSeason(int $teamSeasonId, int $limit = 10): array
    {
        return $this->getImagesByAllTags(["teamseason-{$teamSeasonId}"], $limit);
    }

    /**
     * Convenience: roster image (person + team season).
     */
    public function getRosterImages(int $personId, int $teamSeasonId, int $limit = 1): array
    {
        return $this->getImagesByAllTags([
            "person-{$personId}",
            "teamseason-{$teamSeasonId}",
            'roster',
        ], $limit);
    }

    /**
     * Resolve or create and return ImageTags for provided slugs (utility for controllers).
     *
     * @param array<int,string> $tagSlugs Tag slugs.
     * @return array<int,\App\Model\Entity\ImageTag>
     */
    public function ensureTags(array $tagSlugs): array
    {
        $tagsTable = $this->table('ImageTags');
        $tags = [];
        foreach ($tagSlugs as $slug) {
            $slug = Text::slug($slug) ?: strtolower($slug);
            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $slug, 'slug' => $slug]);
                $tagsTable->save($existing);
            }
            /** @phpstan-ignore if.alwaysTrue */
            if ($existing) {
                $tags[] = $existing;
            }
        }

        return $tags;
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
        // Crop: expects x, y, width, height
        if (!empty($manipulations['crop'])) {
            $crop = $manipulations['crop'];
            if (isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
                $x = (int)$crop['x'];
                $y = (int)$crop['y'];
                $w = (int)$crop['width'];
                $h = (int)$crop['height'];
                // Ensure bounds are valid
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

        // Rotate: expects angle (degrees, 0-360)
        if (!empty($manipulations['rotate'])) {
            $angle = (int)$manipulations['rotate'];
            if ($angle > 0 && $angle < 360) {
                $image->rotate(-$angle); // Negative because Intervention rotates counter-clockwise
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
     * Simple TableLocator helper.
     *
     * @param string $alias Table alias.
     * @return \Cake\ORM\Table
     */
    private function table(string $alias): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($alias);
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
