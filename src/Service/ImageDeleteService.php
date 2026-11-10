<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesImageTagsTable;
use App\Model\Table\ImagesTable;

class ImageDeleteService
{
    private ImageStorageService $storage;

    private TaggingService $tagging;
    private ImagesTable $imagesTable;
    private ImagesImageTagsTable $imagesImageTagsTable;

    /**
     * @param \App\Service\ImageStorageService $storage Image storage dependency.
     * @param \App\Service\TaggingServiceFactory $taggingServiceFactory Tagging context factory.
     * @param \App\Model\Table\ImagesTable $imagesTable Image persistence dependency.
     * @param \App\Model\Table\ImagesImageTagsTable $imagesImageTagsTable Image-tag junction dependency.
     */
    public function __construct(
        ImageStorageService $storage,
        TaggingServiceFactory $taggingServiceFactory,
        ImagesTable $imagesTable,
        ImagesImageTagsTable $imagesImageTagsTable,
    ) {
        $this->storage = $storage;
        $this->tagging = $taggingServiceFactory->forImages();
        $this->imagesTable = $imagesTable;
        $this->imagesImageTagsTable = $imagesImageTagsTable;
    }

    /**
     * Delete a single image by id.
     *
     * @param int $id
     * @return array{success:bool,deleted:bool}
     */
    public function deleteImageById(int $id): array
    {
        $images = $this->imagesTable;
        $image = $images->get($id);

        return $this->deleteImage($image);
    }

    /**
     * Delete a single image entity and related data.
     *
     * @param \App\Model\Entity\Image $image
     * @return array{success:bool,deleted:bool}
     */
    public function deleteImage(Image $image): array
    {
        $imageId = (int)$image->id;

        $this->imagesImageTagsTable->deleteAll(['image_id' => $imageId]);

        $this->deleteImageFiles($image);

        $deleted = (bool)$this->imagesTable->delete($image);

        // Preserve controller behavior: always prune after deleting attachments.
        $this->tagging->pruneOrphanedTags();

        return ['success' => $deleted, 'deleted' => $deleted];
    }

    /**
     * Bulk delete images by ids.
     *
     * @param array<int|string,mixed> $ids
     * @return array{deleted:int}
     */
    public function bulkDeleteImages(array $ids): array
    {
        $imageIds = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $imageIds[$id] = $id;
            }
        }

        if (!$imageIds) {
            return ['deleted' => 0];
        }

        $images = $this->imagesTable;
        $imagesToDelete = $images->find()
            ->whereInList('id', array_values($imageIds))
            ->all();

        $deleted = 0;
        foreach ($imagesToDelete as $image) {
            /** @var \App\Model\Entity\Image $image */
            $this->imagesImageTagsTable->deleteAll(['image_id' => (int)$image->id]);
            $this->deleteImageFiles($image);

            if ($images->delete($image)) {
                $deleted++;
            }
        }

        $this->tagging->pruneOrphanedTags();

        return ['deleted' => $deleted];
    }

    /**
     * Delete the original image file and any variant files on disk.
     *
     * @param \App\Model\Entity\Image $image
     */
    private function deleteImageFiles(Image $image): void
    {
        [$originalPath] = $this->storage->resolveImagePath($image, '');

        if (is_file($originalPath)) {
            unlink($originalPath);
        }

        $variants = $image->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }

        if (!is_array($variants)) {
            return;
        }

        $dir = dirname($originalPath);
        foreach ($variants as $variantMeta) {
            $file = is_array($variantMeta) ? ($variantMeta['file'] ?? null) : null;
            if (!$file) {
                continue;
            }

            $safeFile = str_replace(['../', '..\\'], '', (string)$file);
            $safeFile = ltrim($safeFile, '/\\');

            $variantPath = $dir . DS . $safeFile;
            if (is_file($variantPath)) {
                unlink($variantPath);
            }
        }
    }
}
