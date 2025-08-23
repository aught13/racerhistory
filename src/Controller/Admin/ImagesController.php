<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ImageProcessor;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class ImagesController extends AppController
{
    /**
     * Upload an image and persist original + variants.
     * Stores files outside webroot (storage/images) and serves via serve() action.
     * Accepts optional usage context: model, foreign_key, field.
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
            $this->maybeRecordUsage($existing->id);
            return $this->json(['success' => true, 'image' => $this->serializeImage($existing)]);
        }
        $ext = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION) ?: $processed['original']['ext'];
        $uuid = Text::uuid();
        $storageDir = ROOT . DS . 'storage' . DS . 'images' . DS . date('Y') . DS . date('m') . DS; // non-public
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0770, true);
        }
        $filename = $uuid . '.' . $ext;
        file_put_contents($storageDir . $filename, $processed['original']['data']);
        $variantMeta = [];
        foreach ($processed['variants'] as $name => $v) {
            $vf = $uuid . '_' . $name . '.' . $v['ext'];
            file_put_contents($storageDir . $vf, $v['data']);
            $variantMeta[$name] = [
                'file' => $vf,
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
            $this->maybeRecordUsage($image->id);
            return $this->json(['success' => true, 'image' => $this->serializeImage($image)]);
        }

        return $this->json(['success' => false, 'error' => 'Unable to save image']);
    }

    /**
     * Serve an image (original or variant) by id and optional variant name.
     * Example: /admin/images/serve/123?variant=thumb
     */
    public function serve(int $id): Response
    {
        $this->request->allowMethod(['get']);
        $images = $this->fetchTable('Images');
        $image = $images->find()->where(['id' => $id])->first();
        if (!$image) {
            throw new RecordNotFoundException('Image not found');
        }
        $variant = $this->request->getQuery('variant');
        $baseDir = ROOT . DS . 'storage' . DS . 'images' . DS . date('Y') . DS . date('m') . DS; // NOTE: simplification; ideally store path on entity
        $path = $baseDir . $image->filename;
        if ($variant) {
            $raw = is_string($image->variants) ? json_decode($image->variants, true) : $image->variants;
            if (isset($raw[$variant]['file'])) {
                $path = $baseDir . $raw[$variant]['file'];
            }
        }
        if (!is_file($path)) {
            throw new RecordNotFoundException('File missing');
        }
        $contents = file_get_contents($path) ?: '';
        return $this->response->withType($image->mime)->withStringBody($contents);
    }

    /**
     * Management index view (list images with usage counts).
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);
        $images = $this->fetchTable('Images')->find()->orderDesc('id')->limit(100)->all();
        $this->set(compact('images'));
        $this->viewBuilder()->setOption('serialize', []);
        return $this->response; // normal template render (templates/Admin/Images/index.php expected)
    }

    /**
     * Edit image metadata (status or original_name only for now).
     */
    public function edit(int $id): Response
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
        return $this->response;
    }

    /**
     * Record usage if context passed.
     */
    private function maybeRecordUsage(int $imageId): void
    {
        $model = $this->request->getData('model') ?? $this->request->getQuery('model');
        $foreign = $this->request->getData('foreign_key') ?? $this->request->getQuery('foreign_key');
        $field = $this->request->getData('field') ?? $this->request->getQuery('field');
        if (!$model || !$foreign || !$field) {
            return; // insufficient context
        }
        /** @var \App\Model\Table\ImageUsagesTable $usages */
        $usages = TableRegistry::getTableLocator()->get('ImageUsages');
        $existing = $usages->find()->where(compact('imageId','model','foreign','field'))->first();
        if ($existing) {
            return; // already recorded
        }
        $usage = $usages->newEntity([
            'image_id' => $imageId,
            'model' => (string)$model,
            'foreign_key' => (int)$foreign,
            'field' => (string)$field,
        ]);
        $usages->save($usage); // ignore failure silently for now
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
        return [
            'id' => $image->id,
            'filename' => $image->filename,
            'url' => $baseUrl,
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
