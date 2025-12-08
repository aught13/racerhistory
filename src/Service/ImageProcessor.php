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
        $image = $imagesTable->get($imageId, ['contain' => ['ImageTags']]);

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
            /** @phpstan-ignore if.alwaysTrue */
            if ($existing) {
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

        $query = $images->find()
            ->matching('ImageTags', function ($q) use ($tagSlugs) {
                return $q->where(['ImageTags.slug IN' => $tagSlugs]);
            })
            ->group(['Images.id'])
            ->having(['COUNT(DISTINCT ImageTags.slug) >=' => $needed])
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
