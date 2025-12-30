<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Intervention\Image\ImageManager;

/**
 * Public Images Controller
 *
 * @property \App\Model\Table\ImagesTable $Images
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class ImagesController extends AppController
{
    /**
     * Initialization hook method.
     */
    public function initialize(): void
    {
        parent::initialize();

        // Load Authorization component and skip all checks (public image serving)
        $this->loadComponent('Authorization.Authorization');
        $this->Authorization->skipAuthorization();
    }

    /**
     * Public serve endpoint (no auth) for original or variant.
     * /images/serve/123?variant=thumb
     */
    public function serve(int $id): Response
    {
        $request = $this->getRequest();
        $request->allowMethod(['get', 'head']);
        $image = $this->fetchTable('Images')->find()->where(['id' => $id])->first();
        if (!$image) {
            throw new RecordNotFoundException('Image not found');
        }
        $variant = (string)$request->getQuery('variant');
        [$path, $mime] = $this->resolvePath($image, $variant);

        if (!is_file($path)) {
            return $this->placeholderTransparentPng();
        }

        $hasVersion = (string)$request->getQuery('v') !== '';
        $cacheControl = $hasVersion
            ? 'public, max-age=31536000, immutable'
            : 'private, max-age=0, must-revalidate';

        $transform = $this->extractTransformParams();
        if ($transform !== null) {
            return $this->serveTransformed($image, $path, $mime, $variant, $transform, $cacheControl);
        }

        $body = file_get_contents($path) ?: '';
        if ($body === '') {
            return $this->placeholderTransparentPng();
        }

        $etag = $this->buildEtag((string)($image->hash ?? ''), $variant, []);

        return $this->getResponse()
            ->withType($mime)
            ->withHeader('ETag', $etag)
            ->withHeader('Cache-Control', $cacheControl)
            ->withStringBody($body);
    }

    /**
     * Extract supported transformation parameters from query string.
     * Returns null when no transform params are present.
     *
     * Supported (Glide-like): w, h, fit (cover|contain), fm (jpg|png|webp), q (1..100)
     *
     * @return array<string,mixed>|null
     */
    private function extractTransformParams(): ?array
    {
        $q = $this->getRequest()->getQueryParams();
        unset($q['variant'], $q['v']);

        $w = $q['w'] ?? null;
        $h = $q['h'] ?? null;
        $fit = strtolower((string)($q['fit'] ?? ''));
        $fm = strtolower((string)($q['fm'] ?? ''));
        $quality = $q['q'] ?? null;

        $out = [];

        $width = is_numeric($w) ? (int)$w : null;
        $height = is_numeric($h) ? (int)$h : null;
        if ($width !== null && $width > 0) {
            $out['w'] = $width;
        }
        if ($height !== null && $height > 0) {
            $out['h'] = $height;
        }

        if ($fit !== '') {
            if (in_array($fit, ['cover', 'contain'], true)) {
                $out['fit'] = $fit;
            }
        }

        if ($fm !== '') {
            if (in_array($fm, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $out['fm'] = $fm === 'jpeg' ? 'jpg' : $fm;
            }
        }

        if ($quality !== null && $quality !== '') {
            $qi = is_numeric($quality) ? (int)$quality : null;
            if ($qi !== null) {
                $out['q'] = max(1, min(100, $qi));
            }
        }

        return $out ?: null;
    }

    /**
     * Serve a transformed derivative with disk caching (tmp/cache).
     *
     * @param \App\Model\Entity\Image $image
     * @param string $path Absolute file path
     * @param string $mime Original mime
     * @param string $variant Variant name (optional)
     * @param array<string,mixed> $transform Transform params
     */
    private function serveTransformed(
        object $image,
        string $path,
        string $mime,
        string $variant,
        array $transform,
        string $cacheControl,
    ): Response {
        $baseHash = (string)($image->hash ?? '');
        $etag = $this->buildEtag($baseHash, $variant, $transform);

        [$outMime, $ext] = $this->outputFormat($transform['fm'] ?? null, $mime);
        $cacheDir = CACHE . 'image_derivatives' . DIRECTORY_SEPARATOR . (string)$image->id . DIRECTORY_SEPARATOR;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $key = hash('sha256', $etag);
        $cached = $cacheDir . $key . '.' . $ext;
        if (is_file($cached)) {
            $body = file_get_contents($cached) ?: '';
            if ($body !== '') {
                return $this->getResponse()
                    ->withType($outMime)
                    ->withHeader('ETag', $etag)
                    ->withHeader('Cache-Control', $cacheControl)
                    ->withStringBody($body);
            }
        }

        try {
            $manager = extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
            $raw = file_get_contents($path) ?: '';
            if ($raw === '') {
                return $this->placeholderTransparentPng();
            }

            $img = $manager->read($raw);

            $w = (int)($transform['w'] ?? 0);
            $h = (int)($transform['h'] ?? 0);
            $fit = (string)($transform['fit'] ?? '');

            if ($w > 0 && $h > 0 && $fit === 'cover') {
                $img->cover($w, $h);
            } elseif ($w > 0 || $h > 0) {
                // scaleDown preserves aspect ratio and will not upscale.
                $img->scaleDown($w > 0 ? $w : null, $h > 0 ? $h : null);
            }

            $q = (int)($transform['q'] ?? 85);
            $encoded = in_array($outMime, ['image/jpeg', 'image/webp'], true)
                ? $img->encodeByMediaType($outMime, $q)
                : $img->encodeByMediaType($outMime);
            $body = (string)$encoded;
            if ($body === '') {
                return $this->placeholderTransparentPng();
            }

            file_put_contents($cached, $body);

            return $this->getResponse()
                ->withType($outMime)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl)
                ->withStringBody($body);
        } catch (\Throwable $e) {
            // Degrade gracefully to original bytes if transform fails.
            $body = file_get_contents($path) ?: '';
            if ($body === '') {
                return $this->placeholderTransparentPng();
            }

            return $this->getResponse()
                ->withType($mime)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl)
                ->withStringBody($body);
        }
    }

    /**
     * @return array{0:string,1:string} [mime, extension]
     */
    private function outputFormat(?string $fm, string $fallbackMime): array
    {
        return match ($fm) {
            'jpg' => ['image/jpeg', 'jpg'],
            'png' => ['image/png', 'png'],
            'webp' => ['image/webp', 'webp'],
            default => [$fallbackMime ?: 'image/jpeg', $this->mimeToExt($fallbackMime ?: 'image/jpeg')],
        };
    }

    /**
     * Return a best-effort file extension for a mime type.
     *
     * @param string $mime Mime type
     * @return string File extension without dot
     */
    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * @param array<string,mixed> $transform
     */
    private function buildEtag(string $hash, string $variant, array $transform): string
    {
        $basis = $hash . '|' . $variant . '|' . json_encode($transform);

        return '"' . hash('sha256', $basis) . '"';
    }

    /**
     * Resolve filesystem path and mime type for an image or image id.
     *
     * @param mixed $image Image entity or numeric id
     * @param string $variant Optional variant name
     * @return array{0:string,1:string} [$path, $mime]
     */
    private function resolvePath(mixed $image, string $variant): array
    {
        // Handle case where $image is a numeric ID
        if (is_numeric($image)) {
            $imageEntity = $this->fetchTable('Images')->find()->where(['id' => $image])->first();
            if (!$imageEntity) {
                throw new RecordNotFoundException('Image not found');
            }
            $image = $imageEntity;
        }

        $storagePath = $image->storage_path ?? null;
        if ($storagePath) {
            $storagePathSanitized = str_replace(['../', '..\\'], '', $storagePath);
            $path = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $storagePathSanitized;
            $base = dirname($path) . DIRECTORY_SEPARATOR;
        } else {
            $subdir = $image->storage_subdir ?? (date('Y') . '/' . date('m'));
            $base = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR;
            $path = $base . $image->filename;
        }
        $mime = $image->mime;
        if ($variant) {
            $raw = is_string($image->variants) ? json_decode($image->variants, true) : $image->variants;
            if (isset($raw[$variant]['file'])) {
                $path = $base . $raw[$variant]['file'];
                if (!empty($raw[$variant]['mime'])) {
                    $mime = $raw[$variant]['mime'];
                }
            }
        }

        return [$path, $mime];
    }

    /**
     * Return a 1x1 transparent PNG Response used as a safe placeholder.
     */
    private function placeholderTransparentPng(): Response
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMA'
            . 'AQAABQABDQottAAAAABJRU5ErkJggg==';
        $data = base64_decode($b64);

        return $this->getResponse()
            ->withType('image/png')
            ->withHeader('Cache-Control', 'public, max-age=60')
            ->withStringBody($data ?: '');
    }

    /**
     * Debugging manipulate action to log the image entity.
     */
    public function manipulate(int $id): void
    {
        $image = $this->Images->get($id);
        if (!$image) {
            $this->log("Image not found for ID: $id", 'error');
        } else {
            $this->log('Loaded image: ' . json_encode($image), 'debug');
        }

        $this->set(compact('image'));
    }
}
