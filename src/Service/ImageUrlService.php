<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Builds direct public URLs for stored image originals and variants.
 */
class ImageUrlService
{
    /**
     * @var \App\Model\Table\ImagesTable
     */
    private ImagesTable $imagesTable;

    /**
     * @var array<int,\App\Model\Entity\Image|null>
     */
    private array $imageCache = [];

    /**
     * @param \App\Model\Table\ImagesTable|null $imagesTable
     */
    public function __construct(?ImagesTable $imagesTable = null)
    {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = $imagesTable ?? TableRegistry::getTableLocator()->get('Images');
        $this->imagesTable = $table;
    }

    /**
     * Build a direct URL from an image id.
     *
     * @param string|int $id
     * @param array<string,mixed> $params
     * @return string
     */
    public function urlForId(int|string $id, array $params = []): string
    {
        $idInt = (int)$id;
        if ($idInt <= 0) {
            return '';
        }

        $image = $this->loadImage($idInt);
        if ($image === null) {
            return '';
        }

        return $this->urlForImage($image, $params);
    }

    /**
     * Build a direct URL from an image entity.
     *
     * @param \App\Model\Entity\Image $image
     * @param array<string,mixed> $params
     * @return string
     */
    public function urlForImage(Image $image, array $params = []): string
    {
        $variant = $this->resolveVariant($params);
        $storagePath = $this->resolveStoragePath($image, $variant);
        if ($storagePath === '') {
            return '';
        }

        $url = '/img/storage/' . ltrim($storagePath, '/');
        $cacheBust = $this->cacheBustQuery($params);

        return $cacheBust === '' ? $url : $url . $cacheBust;
    }

    /**
     * Build a cache-busting query string when requested.
     *
     * @param array<string,mixed> $params
     * @return string
     */
    public function cacheBustQuery(array $params): string
    {
        $timestamp = $params['_ts'] ?? null;
        if ($timestamp === null || $timestamp === '') {
            return '';
        }

        return '?' . http_build_query(['_ts' => (string)$timestamp]);
    }

    /**
     * @param int $id
     * @return \App\Model\Entity\Image|null
     */
    private function loadImage(int $id): ?Image
    {
        if (array_key_exists($id, $this->imageCache)) {
            return $this->imageCache[$id];
        }

        /** @var \App\Model\Entity\Image|null $image */
        $image = $this->imagesTable->find()->where(['Images.id' => $id])->first();
        $this->imageCache[$id] = $image;

        return $image;
    }

    /**
     * @param array<string,mixed> $params
     * @return string
     */
    private function resolveVariant(array $params): string
    {
        $variant = trim((string)($params['variant'] ?? ''));
        if ($variant !== '') {
            return $variant;
        }

        $profileName = trim((string)($params['profile'] ?? ''));
        if ($profileName === '') {
            return '';
        }

        $profiles = (array)Configure::read('Images.profiles', []);
        $profileConfig = $profiles[$profileName] ?? null;
        if (!is_array($profileConfig)) {
            return '';
        }

        $sourceVariant = $profileConfig['sourceVariant'] ?? null;

        return is_string($sourceVariant) ? trim($sourceVariant) : '';
    }

    /**
     * @param \App\Model\Entity\Image $image
     * @param string $variant
     * @return string
     */
    private function resolveStoragePath(Image $image, string $variant): string
    {
        if ($variant !== '') {
            $variants = $this->decodeVariants($image);
            $variantFile = $variants[$variant]['file'] ?? null;
            if (is_string($variantFile) && $variantFile !== '') {
                $subdir = trim((string)($image->storage_subdir ?? ''), '/');

                return ltrim(($subdir !== '' ? $subdir . '/' : '') . basename($variantFile), '/');
            }
        }

        $storagePath = trim((string)($image->storage_path ?? ''), '/');
        if ($storagePath !== '') {
            return $this->sanitizePath($storagePath);
        }

        $subdir = trim((string)($image->storage_subdir ?? ''), '/');
        $filename = basename((string)$image->filename);
        if ($filename === '') {
            return '';
        }

        return $this->sanitizePath(($subdir !== '' ? $subdir . '/' : '') . $filename);
    }

    /**
     * @param \App\Model\Entity\Image $image
     * @return array<string,array<string,mixed>>
     */
    private function decodeVariants(Image $image): array
    {
        $variants = $image->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true) ?: [];
        }

        return is_array($variants) ? $variants : [];
    }

    /**
     * @param string $path
     * @return string
     */
    private function sanitizePath(string $path): string
    {
        return ltrim(str_replace(['../', '..\\'], '', $path), '/');
    }
}
