<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\ORM\TableRegistry;

class ImageDeleteService
{
    private ImageStorageService $storage;

    private TaggingService $tagging;

    /**
     * @param \App\Service\ImageStorageService|null $storage Optional storage service override.
     * @param \App\Service\TaggingService|null $tagging Optional tagging service override.
     */
    public function __construct(?ImageStorageService $storage = null, ?TaggingService $tagging = null)
    {
        $this->storage = $storage ?? new ImageStorageService();
        $this->tagging = $tagging ?? TaggingService::forImages();
    }

    /**
     * Delete a single image by id.
     *
     * @return array{success:bool,deleted:bool}
     */
    public function deleteImageById(int $id): array
    {
        $images = $this->imagesTable();
        $image = $images->get($id);

        return $this->deleteImage($images, $image);
    }

    /**
     * Delete a single image entity and related data.
     *
     * @return array{success:bool,deleted:bool}
     */
    public function deleteImage(ImagesTable $images, Image $image): array
    {
        $imageId = (int)$image->id;

        $this->imagesImageTagsTable()->deleteAll(['image_id' => $imageId]);

        $this->deleteImageFiles($image);

        $deleted = (bool)$images->delete($image);

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

        $images = $this->imagesTable();
        $imagesToDelete = $images->find()
            ->whereInList('id', array_values($imageIds))
            ->all();

        $deleted = 0;
        foreach ($imagesToDelete as $image) {
            /** @var \App\Model\Entity\Image $image */
            $this->imagesImageTagsTable()->deleteAll(['image_id' => (int)$image->id]);
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

    /**
     * Lookup the Images table.
     */
    private function imagesTable(): ImagesTable
    {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = TableRegistry::getTableLocator()->get('Images');

        return $table;
    }

    /**
     * Lookup the join table that links images to tags.
     */
    private function imagesImageTagsTable(): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get('ImagesImageTags');
    }
}
