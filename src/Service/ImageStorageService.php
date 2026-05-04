<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

/**
 * ImageStorageService
 *
 * Handles upload validation, processing, persistence, and lookup of images.
 * Delegates tagging to TaggingService and pixel work to ImageProcessor.
 */
class ImageStorageService
{
    private ImageProcessor $processor;
    private TaggingService $tagging;
    private ?string $lastError = null;

    /**
     * @param \App\Service\ImageProcessor|null $processor Optional processor override
     * @param \App\Service\TaggingService|null $tagging   Optional tagging override
     */
    public function __construct(?ImageProcessor $processor = null, ?TaggingService $tagging = null)
    {
        $this->processor = $processor ?? new ImageProcessor();
        $this->tagging = $tagging ?? TaggingService::forImages();
    }

    /**
     * Return last error message set during upload/persist flows.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Upload a single file and persist the image + variants.
     * Returns ['success' => bool, 'image' => ?Image, 'existing' => bool, 'error' => ?string].
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @param array<int|string,string|array>          $tags
     * @param array<string,mixed>                     $manipulations
     * @return array<string,mixed>
     */
    public function upload(UploadedFileInterface $file, array $tags = [], array $manipulations = []): array
    {
        $this->lastError = null;

        $validation = $this->validateUpload($file);
        if ($validation !== true) {
            $this->lastError = is_string($validation) ? $validation : 'Invalid upload';

            return ['success' => false, 'error' => $this->lastError];
        }

        [$processed, $hash, $mime, $ext] = $this->processFile($file, $manipulations);
        $images = $this->imagesTable();
        /** @var \App\Model\Entity\Image|null $existing */
        $existing = $images->find()->where(['hash' => $hash])->first();
        if ($existing) {
            $this->tagging->attachTags((int)$existing->id, $tags);

            return ['success' => true, 'image' => $existing, 'existing' => true];
        }

        $image = $this->persistNewImage($images, $processed, $hash, $mime, $ext, $file->getClientFilename());
        if ($image) {
            $this->tagging->attachTags((int)$image->id, $tags);

            return ['success' => true, 'image' => $image, 'existing' => false];
        }

        $error = $this->lastError ?? 'Unable to save image';

        return ['success' => false, 'error' => $error];
    }

    /**
     * Validate uploaded file type and error state.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     */
    public function validateUpload(UploadedFileInterface $file): bool|string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return 'No file uploaded';
        }
        if ($file->getSize() === 0) {
            return 'Empty file';
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = $file->getClientMediaType();
        if (!in_array($mime, $allowed, true)) {
            return 'Unsupported file type';
        }
        $tmpPath = $file->getStream()->getMetadata('uri');
        if ($tmpPath && is_file($tmpPath)) {
            set_error_handler(static function () {
                return true;
            });
            try {
                $imgInfo = getimagesize($tmpPath);
            } finally {
                restore_error_handler();
            }
            if ($imgInfo === false) {
                return 'Invalid image data';
            }
        }

        return true;
    }

    /**
     * Process uploaded file via ImageProcessor and return processed structure.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @param array $manipulations
     * @return array{0:array,1:string,2:string,3:string}
     */
    public function processFile(UploadedFileInterface $file, array $manipulations = []): array
    {
        $variantConfig = (array)Configure::read('Images.variants', [
            'thumb' => ['fit' => [150, 150]],
            'medium' => ['maxWidth' => 800],
        ]);
        $processed = $this->processor->process($file, $variantConfig, $manipulations);
        $hash = hash('sha256', $processed['original']['data']);
        $mime = $file->getClientMediaType();
        $ext = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION) ?: $processed['original']['ext'];

        return [$processed, $hash, $mime, $ext];
    }

    /**
     * Persist image entity and return saved entity or null on failure.
     *
     * @param \App\Model\Table\ImagesTable $images
     * @param array $processed
     * @param string $hash
     * @param string $mime
     * @param string $ext
     * @param string|null $originalName
     */
    public function persistNewImage(
        ImagesTable $images,
        array $processed,
        string $hash,
        string $mime,
        string $ext,
        ?string $originalName,
    ): ?Image {
        $uuid = Text::uuid();
        $subdir = date('Y') . '/' . date('m');
        $storageDir = $this->storageRoot() . $subdir . DS;
        $writeErrors = [];
        if (!$this->createStorageDir($storageDir, $writeErrors)) {
            $this->lastError = end($writeErrors) ?: 'Storage directory not writable';

            return null;
        }
        [$filename, $variantMeta] = $this->writeImageFiles($uuid, $ext, $storageDir, $processed, $writeErrors);
        foreach ($writeErrors as $err) {
            if (str_contains($err, 'Failed to write original image')) {
                $this->lastError = $err;

                return null;
            }
        }

        $data = [
            'filename' => $filename,
            'storage_subdir' => $subdir,
            'storage_path' => $subdir . '/' . $filename,
            'original_name' => $originalName,
            'mime' => $mime,
            'ext' => $ext,
            'byte_size' => strlen((string)$processed['original']['data']),
            'width' => $processed['original']['width'],
            'height' => $processed['original']['height'],
            'variants' => json_encode($variantMeta),
            'hash' => $hash,
            'status' => 'active',
        ];
        $image = $images->newEntity($data);
        if ($images->save($image)) {
            return $image;
        }
        $this->lastError = 'Failed to save image record';

        return null;
    }

    /**
     * Write original + variants to storage directory.
     *
     * @param string $uuid
     * @param string $ext
     * @param string $storageDir
     * @param array $processed
     * @param array $writeErrors
     * @return array{0:string,1:array}
     */
    private function writeImageFiles(
        string $uuid,
        string $ext,
        string $storageDir,
        array $processed,
        array &$writeErrors,
    ): array {
        $filename = $uuid . '.' . $ext;
        $variants = [];
        $originalPath = $storageDir . $filename;
        if (file_put_contents($originalPath, $processed['original']['data']) === false) {
            $writeErrors[] = 'Failed to write original image';
        }
        foreach ($processed['variants'] as $name => $variant) {
            $variantFilename = $uuid . '-' . $name . '.' . ($variant['ext'] ?? $ext);
            $variantPath = $storageDir . $variantFilename;
            if (file_put_contents($variantPath, $variant['data']) === false) {
                $writeErrors[] = 'Failed to write variant ' . $name;
            } else {
                $variants[$name] = [
                    'file' => $variantFilename,
                    'width' => $variant['width'] ?? null,
                    'height' => $variant['height'] ?? null,
                    'mime' => $variant['mime'] ?? null,
                ];
            }
        }

        return [$filename, $variants];
    }

    /**
     * Ensure storage directory exists and is writable.
     *
     * @param string $dir
     * @param array $errors
     */
    private function createStorageDir(string $dir, array &$errors): bool
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $errors[] = 'Failed to create storage directory: ' . $dir;

            return false;
        }
        if (!is_writable($dir)) {
            $errors[] = 'Storage directory not writable: ' . $dir;

            return false;
        }

        return true;
    }

    /**
     * Load image or throw.
     *
     * @param int $id
     */
    public function loadImageOrFail(int $id): Image
    {
        return $this->imagesTable()->get($id);
    }

    /**
     * Resolve file path and mime for an image and optional variant.
     *
     * @param \App\Model\Entity\Image $image
     * @param string $variant
     * @return array{0:string,1:string}
     */
    public function resolveImagePath(Image $image, string $variant): array
    {
        $storagePath = $image->storage_path ?? null;
        if ($storagePath) {
            $path = $this->storageRoot() . str_replace(['../', '..\\'], '', $storagePath);
            $baseDir = dirname($path) . DS;
        } else {
            $subdir = $image->storage_subdir ?? (date('Y') . '/' . date('m'));
            $baseDir = $this->storageRoot() . $subdir . DS;
            $path = $baseDir . $image->filename;
        }
        $mime = $image->mime;

        $variants = $image->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }

        if ($variant && is_array($variants) && isset($variants[$variant]['file'])) {
            $path = $baseDir . $variants[$variant]['file'];
            if (!empty($variants[$variant]['mime'])) {
                $mime = $variants[$variant]['mime'];
            }
        }

        if (!is_file($path)) {
            $legacyBase = $this->legacyStorageRoot();
            $legacyPath = $legacyBase . ($image->storage_path ?? ($image->storage_subdir . '/' . $image->filename));
            if ($variant && is_array($variants) && isset($variants[$variant]['file'])) {
                $legacyPath = dirname($legacyPath) . DS . $variants[$variant]['file'];
            }
            if (is_file($legacyPath)) {
                $path = $legacyPath;
            }
        }

        return [$path, $mime];
    }

    /**
     * Lookup a table instance.
     */
    private function imagesTable(): ImagesTable
    {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = TableRegistry::getTableLocator()->get('Images');

        return $table;
    }

    /**
     * Get the configured storage root path for images.
     * Returns the configured Images.storageRoot value or defaults to WWW_ROOT/img/storage/.
     * Ensures the path ends with a directory separator.
     *
     * @return string The storage root path with trailing directory separator
     */
    private function storageRoot(): string
    {
        $root = (string)Configure::read('Images.storageRoot', '');
        if ($root !== '') {
            return rtrim($root, DS) . DS;
        }

        return WWW_ROOT . 'img' . DS . 'storage' . DS;
    }

    /**
     * Get the configured legacy storage root path for images.
     * Returns the configured Images.legacyStorageRoot value or defaults to ROOT/storage/images/.
     * Ensures the path ends with a directory separator.
     *
     * @return string The legacy storage root path with trailing directory separator
     */
    private function legacyStorageRoot(): string
    {
        $root = (string)Configure::read('Images.legacyStorageRoot', '');
        if ($root !== '') {
            return rtrim($root, DS) . DS;
        }

        return ROOT . DS . 'storage' . DS . 'images' . DS;
    }
}
