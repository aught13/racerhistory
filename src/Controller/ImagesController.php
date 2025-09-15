<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Public Images Controller
 *
 * @property \App\Model\Table\ImagesTable $Images
 */
class ImagesController extends AppController
{
    /**
     * Public serve endpoint (no auth) for original or variant.
     * /images/serve/123?variant=thumb
     */
    public function serve(int $id): Response
    {
        $this->request->allowMethod(['get']);
        $image = $this->fetchTable('Images')->find()->where(['id' => $id])->first();
        if (!$image) {
            throw new RecordNotFoundException('Image not found');
        }
        $variant = (string)$this->request->getQuery('variant');
        [$path, $mime] = $this->resolvePath($image, $variant);
        $body = is_file($path)
            ? (file_get_contents($path) ?: '')
            : '';
        if ($body === '') {
            return $this->placeholderTransparentPng();
        }

        return $this->response->withType($mime)->withStringBody($body);
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

        return $this->response
            ->withType('image/png')
            ->withHeader('Cache-Control', 'public, max-age=60')
            ->withStringBody($data ?: '');
    }
}
