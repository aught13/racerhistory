<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\Core\Configure;
use Cake\I18n\DateTime;

class ImageEditService
{
    private ImageProcessor $processor;

    private ImageStorageService $storage;

    /**
     * @param \App\Service\ImageProcessor|null $processor Optional processor override (useful for testing).
     * @param \App\Service\ImageStorageService|null $storage Optional storage override (useful for testing).
     */
    public function __construct(?ImageProcessor $processor = null, ?ImageStorageService $storage = null)
    {
        $this->processor = $processor ?? new ImageProcessor();
        $this->storage = $storage ?? new ImageStorageService();
    }

    /**
     * Apply manipulations to an existing image.
     *
     * @param \App\Model\Table\ImagesTable $images
     * @param \App\Model\Entity\Image $image
     * @param array<string,mixed> $manipulations
     * @param string $mode 'apply' or 'copy'
     * @param array<string,mixed>|null $thumbCrop Optional thumb crop payload from request.
     * @return array<string,mixed>
     */
    public function manipulateImage(
        ImagesTable $images,
        Image $image,
        array $manipulations,
        string $mode = 'apply',
        ?array $thumbCrop = null,
    ): array {
        [$originalPath] = $this->storage->resolveImagePath($image, '');
        if (!is_file($originalPath)) {
            throw new \RuntimeException('Original image file not found');
        }

        $fileContent = file_get_contents($originalPath);
        if ($fileContent === false) {
            throw new \RuntimeException('Unable to read original image file');
        }

        $variantConfig = (array)Configure::read('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
            'medium' => ['maxWidth' => 800, 'format' => 'webp'],
            'webp' => ['format' => 'webp'],
        ]);

        $hasThumbCrop = is_array($thumbCrop)
            && !empty($thumbCrop['width'])
            && !empty($thumbCrop['height']);

        if ($hasThumbCrop) {
            $variantConfig['thumb'] = [
                'crop' => [
                    'x' => (int)($thumbCrop['x'] ?? 0),
                    'y' => (int)($thumbCrop['y'] ?? 0),
                    'width' => (int)$thumbCrop['width'],
                    'height' => (int)$thumbCrop['height'],
                ],
                'fit' => [150, 150],
                'format' => 'webp',
            ];
        }

        $processed = $this->processor->manipulateExisting(
            $fileContent,
            $image->mime ?? 'image/jpeg',
            $variantConfig,
            $manipulations,
        );

        $origWidth = (int)($processed['original']['width'] ?? 0);
        $origHeight = (int)($processed['original']['height'] ?? 0);
        if ($origWidth === 0 && $origHeight === 0) {
            return [
                'success' => false,
                'status' => 'missing_library',
            ];
        }

        $originalData = (string)($processed['original']['data'] ?? '');
        if ($originalData === '') {
            throw new \RuntimeException('Processed image data was empty');
        }

        if ($mode === 'copy') {
            $mime = (string)($processed['original']['mime'] ?? $image->mime ?? 'image/jpeg');
            $ext = (string)($processed['original']['ext'] ?? $image->ext ?? 'jpg');
            $hash = hash('sha256', $originalData);
            $copyName = $image->original_name
                ? $image->original_name . ' (edited)'
                : $image->filename . ' (edited)';

            $new = $this->storage->persistNewImage($images, $processed, $hash, $mime, $ext, $copyName);
            if (!$new) {
                $detail = $this->storage->getLastError() ?: 'Unable to save image copy';
                throw new \RuntimeException($detail);
            }

            return [
                'success' => true,
                'status' => 'copied',
                'new_image_id' => (int)$new->id,
            ];
        }

        if (file_put_contents($originalPath, $originalData) === false) {
            throw new \RuntimeException('Failed to write image file');
        }

        $existingVariants = $image->variants;
        if (is_string($existingVariants)) {
            $existingVariants = json_decode($existingVariants, true);
        }

        $dir = dirname($originalPath);
        $baseName = pathinfo((string)$image->filename, PATHINFO_FILENAME);

        $newVariantsMeta = [];
        foreach ((array)($processed['variants'] ?? []) as $name => $meta) {
            $existingFile = null;
            if (is_array($existingVariants) && isset($existingVariants[$name]['file'])) {
                $existingFile = (string)$existingVariants[$name]['file'];
            }

            $ext = (string)($meta['ext'] ?? $image->ext ?? 'jpg');
            $targetFile = $existingFile ?: ($baseName . '-' . $name . '.' . $ext);
            $variantPath = $dir . DS . $targetFile;

            if (file_put_contents($variantPath, (string)($meta['data'] ?? '')) === false) {
                throw new \RuntimeException("Failed to write variant {$name}");
            }

            $newVariantsMeta[$name] = [
                'file' => $targetFile,
                'width' => (int)($meta['width'] ?? null),
                'height' => (int)($meta['height'] ?? null),
                'mime' => (string)($meta['mime'] ?? ''),
            ];
        }

        $images->patchEntity($image, [
            'byte_size' => strlen($originalData),
            'hash' => hash('sha256', $originalData),
            'width' => (int)($processed['original']['width'] ?? $image->width),
            'height' => (int)($processed['original']['height'] ?? $image->height),
            'modified' => date('Y-m-d H:i:s'),
            'variants' => json_encode($newVariantsMeta),
        ], ['validate' => false]);

        $images->saveOrFail($image);

        return [
            'success' => true,
            'status' => 'applied',
            'image_id' => (int)$image->id,
        ];
    }

    /**
     * Regenerate the thumb variant using a crop area.
     *
     * @param \App\Model\Table\ImagesTable $images
     * @param \App\Model\Entity\Image $image
     * @param array<string,int> $crop
     * @return array<string,mixed>
     */
    public function cropThumbVariant(ImagesTable $images, Image $image, array $crop): array
    {
        [$originalPath] = $this->storage->resolveImagePath($image, '');
        if (!is_file($originalPath)) {
            throw new \RuntimeException('Original image file not found');
        }

        $fileContent = file_get_contents($originalPath);
        if ($fileContent === false) {
            throw new \RuntimeException('Unable to read original image file');
        }

        $variantConfig = [
            'thumb' => [
                'crop' => [
                    'x' => (int)($crop['x'] ?? 0),
                    'y' => (int)($crop['y'] ?? 0),
                    'width' => (int)($crop['width'] ?? 0),
                    'height' => (int)($crop['height'] ?? 0),
                ],
                'fit' => [150, 150],
                'format' => 'webp',
            ],
        ];

        $processed = $this->processor->manipulateExisting(
            $fileContent,
            $image->mime ?? 'image/jpeg',
            $variantConfig,
            [],
        );

        if (!isset($processed['variants']['thumb'])) {
            throw new \RuntimeException('Thumb variant not generated');
        }

        $existingVariants = $image->variants;
        if (is_string($existingVariants)) {
            $existingVariants = json_decode($existingVariants, true);
        }
        $existingVariants = is_array($existingVariants) ? $existingVariants : [];

        $dir = dirname($originalPath);
        $meta = $processed['variants']['thumb'];

        $existingFile = $existingVariants['thumb']['file'] ?? null;
        $baseName = pathinfo((string)$image->filename, PATHINFO_FILENAME);
        $ext = (string)($meta['ext'] ?? 'webp');
        $targetFile = $existingFile ?: ($baseName . '-thumb.' . $ext);
        $variantPath = $dir . DS . $targetFile;

        $bytesWritten = file_put_contents($variantPath, (string)($meta['data'] ?? ''));
        if ($bytesWritten === false) {
            throw new \RuntimeException('Failed to write thumb variant file');
        }

        $existingVariants['thumb'] = [
            'file' => $targetFile,
            'width' => (int)($meta['width'] ?? 150),
            'height' => (int)($meta['height'] ?? 150),
            'mime' => (string)($meta['mime'] ?? 'image/webp'),
        ];

        $thumbHash = hash('sha256', (string)($meta['data'] ?? ''));

        $image = $images->patchEntity($image, [
            'variants' => json_encode($existingVariants),
            'hash' => $thumbHash,
            'modified' => new DateTime('now'),
        ], ['validate' => false]);

        $images->saveOrFail($image);

        return [
            'success' => true,
            'hash' => $thumbHash,
            'bytes_written' => (int)$bytesWritten,
        ];
    }
}
