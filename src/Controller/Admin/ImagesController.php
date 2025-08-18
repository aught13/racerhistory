<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ImageProcessor;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Utility\Text;

class ImagesController extends AppController
{
    /**
     * Upload an image and persist original + variants.
     */
    public function upload(): Response
    {
        $this->request->allowMethod(['post']);
        /** @var \Psr\Http\Message\UploadedFileInterface|null $file */
        $file = $this->request->getData('upload') ?? $this->request->getData('file');
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json(['success' => false, 'error' => 'No file uploaded']);
        }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime = $file->getClientMediaType();
        if (!in_array($mime, $allowed, true)) {
            return $this->json(['success' => false, 'error' => 'Unsupported file type']);
        }
        $variantConfig = (array)Configure::read('Images.variants', [
            'thumb' => ['fit' => [150,150]],
            'medium' => ['maxWidth' => 800],
        ]);
        $processor = new ImageProcessor();
        $processed = $processor->process($file, $variantConfig);
        $hash = hash('sha256', $processed['original']['data']);
        $images = $this->fetchTable('Images');
        $existing = $images->find()->where(['hash' => $hash])->first();
        if ($existing) {
            return $this->json(['success' => true, 'image' => $this->serializeImage($existing)]);
        }
        $ext = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION) ?: $processed['original']['ext'];
        $uuid = Text::uuid();
        $baseDir = WWW_ROOT . 'uploads' . DS . date('Y') . DS . date('m') . DS;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }
        $filename = $uuid . '.' . $ext;
        file_put_contents($baseDir . $filename, $processed['original']['data']);
        $variantMeta = [];
        foreach ($processed['variants'] as $name => $v) {
            $vf = $uuid . '_' . $name . '.' . $v['ext'];
            file_put_contents($baseDir . $vf, $v['data']);
            $variantMeta[$name] = [
                'path' => str_replace(WWW_ROOT, '/', $baseDir . $vf),
                'width' => $v['width'],
                'height' => $v['height'],
                'mime' => $v['mime'],
            ];
        }
        $image = $images->newEntity([
            'filename' => $filename,
            'original_name' => $file->getClientFilename(),
            'mime' => $mime,
            'ext' => $ext,
            'byte_size' => strlen($processed['original']['data']),
            'width' => $processed['original']['width'],
            'height' => $processed['original']['height'],
            'variants' => json_encode($variantMeta),
            'hash' => $hash,
            'status' => 'active',
        ]);
        if ($images->save($image)) {
            return $this->json(['success' => true, 'image' => $this->serializeImage($image)]);
        }

        return $this->json(['success' => false, 'error' => 'Unable to save image']);
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
        $basePath = '/uploads/' . date('Y') . '/' . date('m') . '/';

        return [
            'id' => $image->id,
            'filename' => $image->filename,
            'url' => $basePath . $image->filename,
            'variants' => $variants,
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
