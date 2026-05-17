<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\Log\Log;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Public Images Controller
 *
 * This controller serves original and variant images from the filesystem with optional on-the-fly transformations.
 *
 * It supports query parameters for resizing, fitting, format conversion, and quality adjustment.
 * The serve() action is designed to be publicly accessible without authentication, suitable for direct image URLs.
 *
 * Actions:
 * - serve($id): Serve the original or variant image with optional transformations.
 * - manipulate($id): Debugging action to log the image entity (not for public use).
 *
 * Security:
 * - The serve() action skips authorization checks to allow public access.
 * - The controller includes safeguards against path traversal and serves a placeholder image when the requested file is not found or invalid.
 * - Caching headers and ETag support are implemented for efficient client-side caching of images.
 *
 * Dependencies:
 * - Intervention Image: For on-the-fly image transformations.
 *
 * Components:
 * - AuthorizationComponent: Used to skip authorization checks for the serve action, as images are intended
 * to be publicly accessible. The manipulate action is for debugging and should not be exposed in production.
 * - RequestHandlerComponent: Can be used to automatically detect AJAX requests and set response types, although in this implementation we manually check for JSON requests in each action.
 *
 * Note: Ensure that the 'img/storage' directory is properly secured and not directly accessible to prevent unauthorized file access.
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
     * /images/serve/123?profile=roster_avatar
     *
     * @param int $id
     */
    public function serve(int $id): Response
    {
        $request = $this->getRequest();
        $request->allowMethod(['get', 'head']);
        $image = $this->fetchTable('Images')->find()->where(['id' => $id])->first();
        if (!$image) {
            return $this->placeholderTransparentWebp();
        }
        $profileName = strtolower((string)$request->getQuery('profile'));
        $profileConfig = $this->getProfileConfig($profileName);

        $variant = $this->resolveVariantForProfile((string)$request->getQuery('variant'), $profileConfig);
        [$path, $mime] = $this->resolvePath($image, $variant);

        if (!is_file($path)) {
            return $this->placeholderTransparentWebp();
        }

        $cacheControl = 'public, max-age=3600, must-revalidate';

        $etagVariant = $variant;
        if ($profileName !== '') {
            $etagVariant .= '|profile:' . $profileName;
        }

        $etag = $this->buildEtag((string)($image->hash ?? ''), $etagVariant, []);

        $transform = $this->extractTransformParams($profileConfig);
        if ($this->shouldAutoServeWebp($mime) && ($transform === null || !isset($transform['fm']))) {
            $transform ??= [];
            $transform['fm'] = 'webp';
        }

        if ($transform !== null) {
            return $this->serveTransformed($image, $path, $mime, $etagVariant, $transform, $cacheControl);
        }

        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return $this->getResponse()
                ->withStatus(304)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl);
        }

        $body = file_get_contents($path) ?: '';
        if ($body === '') {
            return $this->placeholderTransparentWebp();
        }

        // Calculate the precise size of the image payload in bytes
        $contentLength = mb_strlen($body, '8bit');

        return $this->getResponse()
            ->withType($mime)
            ->withHeader('ETag', $etag)
            ->withHeader('Cache-Control', $cacheControl)
            ->withHeader('Content-Length', (string)$contentLength)
            ->withStringBody($body);
    }

    /**
     * Extract supported transformation parameters from query string.
     * Returns null when no transform params are present.
     *
     * Supported (Glide-like): w, h, fit (cover|contain), fm (jpg|png|webp), q (1..100)
     *
     * Profile defaults (Images.profiles) are merged first, then overridden by explicit query params.
     *
     * @param array<string,mixed> $profileConfig
     * @return array<string,mixed>|null
     */
    private function extractTransformParams(array $profileConfig = []): ?array
    {
        $out = $this->profileToTransformParams($profileConfig);

        $q = $this->getRequest()->getQueryParams();
        unset($q['variant'], $q['v'], $q['profile']);

        $w = $q['w'] ?? null;
        $h = $q['h'] ?? null;
        $fit = strtolower((string)($q['fit'] ?? ''));
        $fm = strtolower((string)($q['fm'] ?? ''));
        $quality = $q['q'] ?? null;

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
     * Resolve an image profile from app config.
     *
     * @param string $profileName
     * @return array<string,mixed>
     */
    private function getProfileConfig(string $profileName): array
    {
        if ($profileName === '') {
            return [];
        }

        $profiles = (array)Configure::read('Images.profiles', []);
        $profileConfig = $profiles[$profileName] ?? null;

        return is_array($profileConfig) ? $profileConfig : [];
    }

    /**
     * Resolve effective variant for profile-backed requests.
     *
     * @param string $variant
     * @param array<string,mixed> $profileConfig
     */
    private function resolveVariantForProfile(string $variant, array $profileConfig): string
    {
        if ($variant !== '') {
            return $variant;
        }

        $sourceVariant = $profileConfig['sourceVariant'] ?? null;

        return is_string($sourceVariant) ? $sourceVariant : '';
    }

    /**
     * Convert a profile config into validated transform parameters.
     *
     * @param array<string,mixed> $profileConfig
     * @return array<string,mixed>
     */
    private function profileToTransformParams(array $profileConfig): array
    {
        $out = [];

        $w = $profileConfig['w'] ?? null;
        $h = $profileConfig['h'] ?? null;
        $fit = strtolower((string)($profileConfig['fit'] ?? ''));
        $fm = strtolower((string)($profileConfig['fm'] ?? ''));
        $q = $profileConfig['q'] ?? null;

        if (is_numeric($w) && (int)$w > 0) {
            $out['w'] = (int)$w;
        }
        if (is_numeric($h) && (int)$h > 0) {
            $out['h'] = (int)$h;
        }
        if ($fit !== '' && in_array($fit, ['cover', 'contain'], true)) {
            $out['fit'] = $fit;
        }
        if ($fm !== '' && in_array($fm, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $out['fm'] = $fm === 'jpeg' ? 'jpg' : $fm;
        }
        if (is_numeric($q)) {
            $out['q'] = max(1, min(100, (int)$q));
        }

        return $out;
    }

    /**
     * Serve a transformed derivative with disk caching (tmp/cache).
     *
     * @param object $image
     * @param string $path
     * @param string $mime
     * @param string $variant
     * @param array $transform
     * @param string $cacheControl
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

        $ifNoneMatch = $this->getRequest()->getHeaderLine('If-None-Match');
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return $this->getResponse()
                ->withStatus(304)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl);
        }

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
                return $this->placeholderTransparentWebp();
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
                return $this->placeholderTransparentWebp();
            }

            $this->atomicWriteCache($cached, $body);

            return $this->getResponse()
                ->withType($outMime)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl)
                ->withStringBody($body);
        } catch (Throwable $e) {
            // Degrade gracefully to original bytes if transform fails.
            $body = file_get_contents($path) ?: '';
            if ($body === '') {
                return $this->placeholderTransparentWebp();
            }

            return $this->getResponse()
                ->withType($mime)
                ->withHeader('ETag', $etag)
                ->withHeader('Cache-Control', $cacheControl)
                ->withStringBody($body);
        }
    }

    /**
     * Runs the output format routine.
     *
     * @param string|null $fm
     * @param string $fallbackMime
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
     * @param string $mime
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
     * Runs the build etag routine.
     *
     * @param string $hash
     * @param string $variant
     * @param array $transform
     */
    private function buildEtag(string $hash, string $variant, array $transform): string
    {
        $basis = $hash . '|' . $variant . '|' . json_encode($transform);

        return '"' . hash('sha256', $basis) . '"';
    }

    /**
     * Determine whether automatic WebP output should be used.
     *
     * WebP is the default format unless the source is already WebP or a format
     * is explicitly requested with `fm`.
     *
     * @param string $mime
     */
    private function shouldAutoServeWebp(string $mime): bool
    {
        return strtolower($mime) !== 'image/webp';
    }

    /**
     * Resolve filesystem path and mime type for an image or image id.
     *
     * @param mixed $image
     * @param string $variant
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
     * Write data to a cache file atomically using a temp file + rename.
     * Using a temp file prevents concurrent readers from seeing a partially-written
     * file, which would result in serving a corrupted/truncated image.
     *
     * @param string $destination
     * @param string $data
     */
    private function atomicWriteCache(string $destination, string $data): void
    {
        $tmp = $destination . '.tmp.' . getmypid();
        $written = file_put_contents($tmp, $data);
        if ($written === false) {
            Log::warning('image_cache: failed to write tmp file ' . $tmp);

            return;
        }
        if (!rename($tmp, $destination)) {
            Log::warning('image_cache: failed to rename ' . $tmp . ' to ' . $destination);
            if (!unlink($tmp)) {
                Log::warning('image_cache: failed to unlink tmp file ' . $tmp);
            }
        }
    }

    /**
     * Return a 1x1 transparent WebP Response used as a safe placeholder.
     */
    private function placeholderTransparentWebp(): Response
    {
        $b64 = 'UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/QERFA0B' .
           'VlA4WQoAAAAcAAAASUNDUAAREAAAbW50clJHQiBYWVogAAAAAAAAAAAAAAAAMVdQ' .
           'S0AnAAAAAAAAAAAANmYAAHAGAABwYwAAZgAAVTRSTAAUAAAAAAAAAAAAAAAAAAAA' .
           'AAAAAAA=';
        $data = base64_decode($b64);

        return $this->getResponse()
            ->withType('image/webp')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->withStringBody($data ?: '');
    }

    /**
     * Debugging manipulate action to log the image entity.
     *
     * @param int $id
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
