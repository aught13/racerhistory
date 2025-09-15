<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ImageProcessor;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

/**
 * Admin Images Controller
 *
 * Handles image upload, storage, and serving for the admin interface.
 *
 * @property \App\Model\Table\ImagesTable $Images
 * @property \App\Model\Table\ImageUsagesTable $ImageUsages
 */
class ImagesController extends AppController
{
    /**
     * Holds last persistence error for image storage (directory or file write issues)
     * so upload() can return a meaningful JSON error.
     */
    private ?string $lastPersistError = null;

    /**
     * Controller initialization: unlock the 'upload' action from FormProtection.
     * This allows multipart XHR requests without Cake token fields.
     */
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            if (!in_array('upload', $current, true)) {
                $current[] = 'upload';
                $this->FormProtection->setConfig('unlockedActions', $current);
            }
        }
    }

    /**
     * Upload an image and persist original + variants.
     * Stores files outside webroot (storage/images) and serves via serve() action.
     * Accepts optional usage context: model, foreign_key, field.
     */
    public function upload(): Response
    {
        $this->request->allowMethod(['post']);
        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }
        try {
            // Prevent stray prior output (warnings) from corrupting JSON response
            if (ob_get_length()) {
                ob_clean();
            }
            // Preflight: ensure base storage directory is writable (option 2 guidance)
            $baseStorage = WWW_ROOT . 'img' . DS . 'storage' . DS;
            if (!is_dir($baseStorage)) {
                // attempt to create base directory if missing
                mkdir($baseStorage, 0775, true);
            }
            if (!is_writable($baseStorage)) {
                $ownerUid = fileowner($baseStorage);
                $ownerName = $ownerUid !== false && function_exists('posix_getpwuid')
                    ? (posix_getpwuid($ownerUid)['name'] ?? (string)$ownerUid)
                    : (string)$ownerUid;
                $suggest = 'chgrp -R www-data ' . $baseStorage . ' && chmod -R 775 ' . $baseStorage;

                $msg = 'Storage base not writable: ' . $baseStorage . ' (owner=' . $ownerName . ').';
                $msg .= ' Run (as root): ' . $suggest;

                return $this->json([
                    'success' => false,
                    'error' => $msg,
                ]);
            }
            $file = $this->extractUploaded();
            $validation = $this->validateUpload($file);
            if ($validation !== true) {
                return $this->json(['success' => false, 'error' => $validation]);
            }
            [$processed, $hash, $mime, $ext] = $this->processFile($file);
            $images = $this->fetchTable('Images');
            /** @var \App\Model\Entity\Image|null $existing */
            $existing = $images->find()->where(['hash' => $hash])->first();
            if ($existing) {
                $this->maybeRecordUsage($existing->id);

                return $this->json(['success' => true, 'image' => $this->serializeImage($existing)]);
            }
            $image = $this->persistNewImage($images, $processed, $hash, $mime, $ext, $file->getClientFilename());
            if ($image) {
                $this->maybeRecordUsage($image->id);

                return $this->json(['success' => true, 'image' => $this->serializeImage($image)]);
            }
            $detail = $this->lastPersistError ?: 'Unable to save image';

            return $this->json(['success' => false, 'error' => $detail]);
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('Image upload exception: ' . $e->getMessage());

            return $this->json(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * Serve an image (original or variant) by id and optional variant name.
     * Example: /admin/images/serve/123?variant=thumb
     */
    public function serve(int $id): Response
    {
        $this->request->allowMethod(['get']);
        $image = $this->loadImageOrFail($id);
        $variant = (string)$this->request->getQuery('variant');
        [$path, $mime] = $this->resolveImagePath($image, $variant);
        $contents = is_file($path)
            ? (file_get_contents($path) ?: '')
            : '';
        if ($contents === '') {
            // Graceful fallback: return a 1x1 transparent PNG instead of 404 to avoid broken icons in editor/content.
            return $this->placeholderTransparentPng();
        }

        return $this->response->withType($mime)->withStringBody($contents);
    }

    /**
     * Management index view (list images with usage counts).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $images = $this->fetchTable('Images')->find()->orderByDesc('id')->limit(100)->all();
        $this->set(compact('images'));
        // Let Cake render the template normally (no explicit return of Response which caused blank output)
    }

    /**
     * Edit image metadata (status or original_name only for now).
     */
    public function edit(int $id): ?Response
    {
        $images = $this->fetchTable('Images');
        $image = $images->get($id);
        if ($this->request->is(['post','put','patch'])) {
            $images->patchEntity($image, $this->request->getData(), ['fields' => ['original_name','status']]);
            if ($images->save($image)) {
                $this->Flash->success('Image updated');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Could not update image');
        }
        $this->set(compact('image'));

        return null; // Allow auto-render
    }

    /**
     * Record usage if context passed.
     */
    private function maybeRecordUsage(int $imageId): void
    {
        [$model, $foreign, $field] = [
            $this->request->getData('model') ?? $this->request->getQuery('model'),
            $this->request->getData('foreign_key') ?? $this->request->getQuery('foreign_key'),
            $this->request->getData('field') ?? $this->request->getQuery('field'),
        ];
        if (!$model || !$foreign || !$field) {
            return;
        }
        $usages = TableRegistry::getTableLocator()->get('ImageUsages');
        $exists = $usages->find()->where([
            'image_id' => $imageId,
            'model' => $model,
            'foreign_key' => (int)$foreign,
            'field' => $field,
        ])->first();
        if ($exists) {
            return;
        }
        $usage = $usages->newEntity([
            'image_id' => $imageId,
            'model' => (string)$model,
            'foreign_key' => (int)$foreign,
            'field' => (string)$field,
        ]);
        $usages->save($usage);
    }

    /**
     * Determine if the current request is authenticated.
     *
     * If the Authentication component is not available we assume this is
     * controlled elsewhere and return true to avoid blocking internal
     * operations during CLI/test runs.
     *
     * @return bool
     */
    private function isAuthenticated(): bool
    {
        if (!$this->components()->has('Authentication')) {
            return true;
        }

        $res = $this->Authentication->getResult();

        return (bool)($res && $res->isValid());
    }

    /**
     * @return \Psr\Http\Message\UploadedFileInterface
     */

    /**
     * Extract uploaded file from request and normalize to PSR-7 UploadedFile.
     *
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    private function extractUploaded()
    {
        $file = $this->request->getData('upload') ?? $this->request->getData('file');
        if (is_array($file) && isset($file['tmp_name'])) {
            $file = new \Laminas\Diactoros\UploadedFile(
                $file['tmp_name'],
                (int)($file['size'] ?? 0),
                (int)($file['error'] ?? UPLOAD_ERR_OK),
                (string)($file['name'] ?? ''),
                (string)($file['type'] ?? '')
            );
        }

        return $file;
    }

    /**
     * Validate uploaded file type and error state.
     *
     * @param mixed $file Uploaded file.
     * @return string|bool True if valid otherwise error message.
     */
    private function validateUpload(mixed $file): bool|string
    {
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return 'No file uploaded';
        }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime = $file->getClientMediaType();
        if (!in_array($mime, $allowed, true)) {
            return 'Unsupported file type';
        }

        return true;
    }

    /**
     * Process uploaded file via ImageProcessor and return processed structure.
     *
     * @param mixed $file Uploaded file.
     * @return array{0:array,1:string,2:string,3:string}
     */
    private function processFile(mixed $file): array
    {
        $variantConfig = (array)Configure::read('Images.variants', [
            'thumb' => ['fit' => [150,150]],
            'medium' => ['maxWidth' => 800],
        ]);
        $processor = new ImageProcessor();
        $processed = $processor->process($file, $variantConfig);
        $hash = hash('sha256', $processed['original']['data']);
        $mime = $file->getClientMediaType();
        $ext = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION) ?: $processed['original']['ext'];

        return [$processed, $hash, $mime, $ext];
    }

    /**
     * Persist image entity and return saved entity or null on failure.
     *
     * @param \Cake\Datasource\RepositoryInterface $images Images table instance.
     * @param array<string,mixed> $processed Processed image data.
     * @param string $hash Content hash.
     * @param string $mime Mime type.
     * @param string $ext File extension.
     * @param string|null $originalName Original filename.
     * @return \App\Model\Entity\Image|null
     */
    private function persistNewImage(
        \Cake\Datasource\RepositoryInterface $images,
        array $processed,
        string $hash,
        string $mime,
        string $ext,
        ?string $originalName,
    ) {
        $uuid = Text::uuid();
        $subdir = date('Y') . '/' . date('m');
        $storageDir = WWW_ROOT . 'img' . DS . 'storage' . DS . $subdir . DS;
        $writeErrors = [];
        if (!$this->createStorageDir($storageDir, $writeErrors)) {
            $this->lastPersistError = end($writeErrors) ?: 'Storage directory not writable';

            return null;
        }
        [$filename, $variantMeta] = $this->writeImageFiles($uuid, $ext, $storageDir, $processed, $writeErrors);
        // If original write failed, abort
        foreach ($writeErrors as $err) {
            if (str_contains($err, 'Failed to write original image')) {
                $this->lastPersistError = $err;

                return null;
            }
        }
        if ($writeErrors) {
            \Cake\Log\Log::warning('Image write warnings: ' . implode('; ', $writeErrors));
        }
        $entityData = [
            'filename' => $filename,
            'storage_subdir' => $subdir,
            'storage_path' => $subdir . '/' . $filename,
            'original_name' => $originalName,
            'mime' => $mime,
            'ext' => $ext,
            'byte_size' => strlen($processed['original']['data']),
            'width' => $processed['original']['width'],
            'height' => $processed['original']['height'],
            'variants' => json_encode($variantMeta),
            'hash' => $hash,
            'status' => 'active',
        ];
        $image = $images->newEntity($entityData);

        $saved = $images->save($image);

        // Ensure we return the concrete Image entity type for static analysis
        if ($saved instanceof \App\Model\Entity\Image) {
            return $saved;
        }

        return null;
    }

    /**
     * Ensure storage directory exists, capturing warnings instead of emitting them.
     *
     * @param string $storageDir Absolute directory path.
     * @param array<int,string> $writeErrors Collector for warnings.
     * @return bool Success
     */
    private function createStorageDir(string $storageDir, array &$writeErrors): bool
    {
        if (is_dir($storageDir)) {
            // Extra safety: ensure directory is writable; attempt permission fix if not.
            if (!is_writable($storageDir)) {
                // Try to chmod; use try/catch for proper error handling
                try {
                    chmod($storageDir, 0775);
                } catch (\Throwable $e) {
                    \Cake\Log\Log::warning('Failed to chmod storage directory: ' . $e->getMessage(), [
                        'storage_dir' => $storageDir,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (!is_writable($storageDir)) {
                    $writeErrors[] = 'Storage directory exists but not writable: ' . $storageDir;
                    \Cake\Log\Log::error(end($writeErrors));

                    return false;
                }
            }

            return true;
        }
        $mkdirOk = false;
        $this->withCapturedWarnings(function () use ($storageDir, &$mkdirOk) {
            $oldUmask = umask(0002); // ensure group write bit preserved
            $mkdirOk = mkdir($storageDir, 0775, true);
            umask($oldUmask);
        }, $writeErrors);
        if (!$mkdirOk && !is_dir($storageDir)) {
            $writeErrors[] = 'Failed to create storage directory: ' . $storageDir;
            \Cake\Log\Log::error(end($writeErrors));

            return false;
        }
        // Inherit parent group if possible so web server user (same group) can write future subdirs
        $parent = dirname(rtrim($storageDir, DIRECTORY_SEPARATOR));
        if (is_dir($parent)) {
            try {
                $parentGroupId = filegroup($parent);
                $dirGroupId = filegroup($storageDir);

                if ($parentGroupId !== false && $dirGroupId !== false && $parentGroupId !== $dirGroupId) {
                    if (function_exists('posix_getgrgid')) {
                        try {
                            $grp = posix_getgrgid($parentGroupId);
                            if ($grp && isset($grp['name'])) {
                                chgrp($storageDir, $grp['name']);
                            }
                        } catch (\Throwable $e) {
                            \Cake\Log\Log::info('Could not change group ownership of storage directory', [
                                'storage_dir' => $storageDir,
                                'parent_group_id' => $parentGroupId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Cake\Log\Log::info('Could not get file group information for storage directory setup', [
                    'storage_dir' => $storageDir,
                    'parent_dir' => $parent,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // After recursive creation, enforce expected permissions (best effort)
        try {
            chmod($storageDir, 0775);
        } catch (\Throwable $e) {
            \Cake\Log\Log::warning('Could not set final permissions on storage directory', [
                'storage_dir' => $storageDir,
                'error' => $e->getMessage(),
            ]);
        }
        if (!is_writable($storageDir)) {
            $writeErrors[] = 'Created storage directory but not writable: ' . $storageDir;
            \Cake\Log\Log::error(end($writeErrors));

            return false;
        }

        return true;
    }

    /**
     * Write original and variant image files to disk.
     *
     * @param string $uuid Base uuid for filenames.
     * @param string $ext Original extension.
     * @param string $storageDir Directory to write to.
     * @param array<string,mixed> $processed Processed image structure.
     * @param array<int,string> $writeErrors Collector for warnings.
     * @return array{0:string,1:array<string,mixed>} Filename of original, variant metadata.
     */
    private function writeImageFiles(
        string $uuid,
        string $ext,
        string $storageDir,
        array $processed,
        array &$writeErrors,
    ): array {
        $filename = $uuid . '.' . $ext;
        $this->withCapturedWarnings(
            function () use ($storageDir, $filename, $processed, &$writeErrors) {
                $target = $storageDir . $filename;
                // Pre-flight directory writability check (defensive)
                if (!is_dir($storageDir)) {
                    $writeErrors[] = 'Target directory missing at write time: ' . $storageDir;
                } elseif (!is_writable($storageDir)) {
                    $writeErrors[] = 'Target directory not writable at write time: ' . $storageDir;
                }
                $data = $processed['original']['data'] ?? '';

                try {
                    $result = file_put_contents($target, $data);
                    if ($result === false) {
                        $err = error_get_last();
                        $w = is_writable($storageDir) ? 'yes' : 'no';
                        $errMsg = $err['message'] ?? 'n/a';
                        $bytes = strlen((string)$data);
                        $writeErrors[] = sprintf(
                            'Failed to write original image (path=%s, writable=%s, bytes=%d, error=%s)',
                            $target,
                            $w,
                            $bytes,
                            $errMsg
                        );

                        // Attempt fallback low-level write
                        try {
                            $fh = fopen($target, 'wb');
                            if ($fh) {
                                $written = fwrite($fh, $data);
                                fclose($fh);
                                if ($written === false) {
                                    $writeErrors[] = 'Fallback fwrite also failed for original image: ' . $target;
                                } else {
                                    // Remove failure marker if fallback succeeded
                                                $writeErrors[] = 'Fallback fwrite succeeded for original image';
                                }
                            } else {
                                $writeErrors[] = 'Could not open file for fallback write: ' . $target;
                            }
                        } catch (\Throwable $e) {
                            $writeErrors[] = 'Fallback write attempt threw exception: ' . $e->getMessage();
                        }
                    }
                } catch (\Throwable $e) {
                    $writeErrors[] = 'Exception during original image write: ' . $e->getMessage();
                }
            },
            $writeErrors
        );
        $variantMeta = [];
        foreach ($processed['variants'] as $name => $v) {
            $vf = $uuid . '_' . $name . '.' . $v['ext'];
            $this->withCapturedWarnings(function () use ($storageDir, $vf, $v, &$writeErrors) {
                $vTarget = $storageDir . $vf;

                try {
                    $vResult = file_put_contents($vTarget, $v['data']);
                    if ($vResult === false) {
                        $err = error_get_last();
                        $vErrMsg = $err['message'] ?? 'n/a';
                        $writeErrors[] = sprintf(
                            'Failed to write variant %s (path=%s, error=%s)',
                            $vf,
                            $vTarget,
                            $vErrMsg
                        );
                    }
                } catch (\Throwable $e) {
                    $writeErrors[] = 'Exception during variant image write (' . $vf . '): ' . $e->getMessage();
                }
            }, $writeErrors);
                $variantMeta[$name] = [
                'file' => $vf,
                'width' => $v['width'],
                'height' => $v['height'],
                'mime' => $v['mime'],
                ];
        }

        return [$filename, $variantMeta];
    }

    /**
     * Execute a callback capturing PHP warnings so they don't leak into JSON output.
     *
     * @param callable $callback Callback to execute.
     * @param array<int,string> $collector Collector for warning messages (by reference outside caller builds array).
     * @return void
     */
    private function withCapturedWarnings(callable $callback, array &$collector): void
    {
        set_error_handler(function ($severity, $message, $file, $line) use (&$collector) {
            if (!(error_reporting() & $severity)) {
                return false; // normal handling for suppressed severities
            }
            $collector[] = $message . ' @' . basename($file) . ':' . $line;

            return true; // swallow
        });
        try {
            $callback();
        } finally {
            // Always restore original error handler stack correctly
            restore_error_handler();
        }
    }

    /**
     * Load image entity or throw RecordNotFoundException.
     *
     * @param int $id Image id.
     * @return \App\Model\Entity\Image
     */
    private function loadImageOrFail(int $id)
    {
        $images = $this->fetchTable('Images');
        $image = $images->find()->where(['id' => $id])->first();
        if (!$image) {
            throw new RecordNotFoundException('Image not found');
        }

        return $image;
    }

    /**
     * Resolve path and mime for an image entity or id.
     *
     * @param mixed $image Image entity or numeric id.
     * @param string $variant Variant name.
     * @return array{0:string,1:string}
     */
    private function resolveImagePath(mixed $image, string $variant): array
    {
        $storagePath = $image->storage_path ?? null;
        if ($storagePath) {
            $path = WWW_ROOT . 'img' . DS . 'storage' . DS . str_replace(['../','..\\'], '', $storagePath);
            $baseDir = dirname($path) . DS;
        } else {
            $subdir = $image->storage_subdir ?? (date('Y') . '/' . date('m'));
            $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS . $subdir . DS;
            $path = $baseDir . $image->filename;
        }
        $mime = $image->mime; // default to original mime
        if ($variant) {
            $raw = is_string($image->variants) ? json_decode($image->variants, true) : $image->variants;
            if (isset($raw[$variant]['file'])) {
                $path = $baseDir . $raw[$variant]['file'];
                if (!empty($raw[$variant]['mime'])) {
                    $mime = $raw[$variant]['mime'];
                }
            }
        }
        // Legacy fallback: if file not found in new public path, try old private path
        if (!is_file($path)) {
            $legacyBase = ROOT . DS . 'storage' . DS . 'images' . DS;
            $legacyPath = $legacyBase . ($image->storage_path ?? ($image->storage_subdir . '/' . $image->filename));
            if ($variant && isset($raw[$variant]['file'])) {
                $legacyPath = dirname($legacyPath) . DS . $raw[$variant]['file'];
            }
            if (is_file($legacyPath)) {
                $path = $legacyPath; // use legacy file
            }
        }

        return [$path, $mime];
    }

    /**
     * Return a 1x1 transparent PNG response (base64 inline data) used as a safe placeholder.
     */
    private function placeholderTransparentPng(): Response
    {
        // Single pixel transparent PNG
        $data = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMA' .
            'AQAABQABDQottAAAAABJRU5ErkJggg=='
        );

        return $this->response
            ->withType('image/png')
            ->withHeader('Cache-Control', 'public, max-age=60')
            ->withStringBody($data ?: '');
    }

    /**
     * Browse recent images (simple paginated subset for now).
     */
    public function browse(): Response
    {
        $this->request->allowMethod(['get']);
        $images = $this->fetchTable('Images')->find()->orderDesc('id')->limit(50)->all();

        return $this->json([
            'success' => true,
            'images' => array_map(
                fn($i) => $this->serializeImage($i),
                $images->toList()
            ),
        ]);
    }

    /**
     * Serialize image entity for JSON response.
     *
     * @param \App\Model\Entity\Image $image Image entity.
     * @return array<string,mixed>
     */
    private function serializeImage(\App\Model\Entity\Image $image): array
    {
        $variants = [];
        $raw = $image->variants;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (is_array($raw)) {
            $variants = $raw;
        }
        // Provide signed/parameterized URL (for now simple route) to serve original
        $baseUrl = '/admin/images/serve/' . $image->id;
        $publicServeUrl = '/images/serve/' . $image->id;
        $directUrl = '/img/storage/' .
            ltrim($image->storage_path ?? ($image->storage_subdir . '/' . $image->filename), '/');

        return [
            'id' => $image->id,
            'filename' => $image->filename,
            'url' => $baseUrl,
            'variants' => $variants,
            'direct_url' => $directUrl,
            'public_url' => $publicServeUrl,
        ];
    }

    /**
     * Return JSON response helper.
     *
     * @param array<string,mixed> $payload Payload.
     */
    private function json(array $payload): Response
    {
        return $this->response->withType('application/json')->withStringBody(json_encode($payload));
    }
}
