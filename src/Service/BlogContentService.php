<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\ORM\TableRegistry;
use DOMDocument;
use DOMElement;

/**
 * Adds metadata-driven photo credits to stored images in blog content.
 */
class BlogContentService
{
    private ImagesTable $imagesTable;

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
     * Add captions for credited stored images in a blog body.
     *
     * @param string $html Blog body HTML.
     */
    public function renderWithPhotoCredits(string $html): string
    {
        if (!str_contains(strtolower($html), '<img')) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rh-blog-content-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (!$loaded) {
            return $html;
        }

        $root = $document->getElementById('rh-blog-content-root');
        if (!$root instanceof DOMElement) {
            return $html;
        }

        $imageElements = [];
        $imageIds = [];
        $imagePaths = [];
        foreach ($root->getElementsByTagName('img') as $imageElement) {
            if (!$imageElement instanceof DOMElement) {
                continue;
            }

            $imageElements[] = $imageElement;
            $imageId = $this->readImageId($imageElement);
            if ($imageId > 0) {
                $imageIds[$imageId] = $imageId;
            }

            $imagePath = $this->readStoredImagePath($imageElement->getAttribute('src'));
            if ($imagePath !== '') {
                $imagePaths[$imagePath] = $imagePath;
            }
        }

        $images = $this->findImages(array_values($imageIds), array_values($imagePaths));
        $renderedCredit = false;
        foreach ($imageElements as $imageElement) {
            $image = $this->resolveImage($imageElement, $images);
            if (!$image || trim((string)$image->photo_credit) === '') {
                continue;
            }

            $target = $imageElement->parentNode instanceof DOMElement
                && strtolower($imageElement->parentNode->tagName) === 'picture'
                ? $imageElement->parentNode
                : $imageElement;

            if (!$target->parentNode || $this->hasCreditWrapper($target)) {
                continue;
            }

            $wrapper = $document->createElement('div');
            $wrapper->setAttribute('class', 'blog-image-credit');
            $existingClass = trim($target->getAttribute('class'));
            if ($existingClass !== '') {
                $wrapper->setAttribute('class', 'blog-image-credit ' . $existingClass);
            }
            $existingStyle = trim($target->getAttribute('style'));
            if ($existingStyle !== '') {
                $wrapper->setAttribute('style', $existingStyle);
            }

            $target->parentNode->replaceChild($wrapper, $target);
            $wrapper->appendChild($target);

            $caption = $document->createElement('figcaption');
            $caption->setAttribute('class', 'blog-image-credit__label');
            $caption->appendChild($document->createTextNode('Photo: ' . trim((string)$image->photo_credit)));
            $wrapper->appendChild($caption);
            $renderedCredit = true;
        }

        if (!$renderedCredit) {
            return $html;
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    /**
     * @param \DOMElement $imageElement
     * @param array<string,\App\Model\Entity\Image> $images
     */
    private function resolveImage(DOMElement $imageElement, array $images): ?Image
    {
        $imageId = $this->readImageId($imageElement);
        if ($imageId > 0 && isset($images['id:' . $imageId])) {
            return $images['id:' . $imageId];
        }

        $path = $this->readStoredImagePath($imageElement->getAttribute('src'));
        if ($path !== '' && isset($images['path:' . $path])) {
            return $images['path:' . $path];
        }

        $filename = basename($path);

        return $filename !== '' ? ($images['filename:' . $filename] ?? null) : null;
    }

    /**
     * @param array<int,int> $imageIds
     * @param array<int,string> $imagePaths
     * @return array<string,\App\Model\Entity\Image>
     */
    private function findImages(array $imageIds, array $imagePaths): array
    {
        $filenames = [];
        foreach ($imagePaths as $path) {
            $filename = basename($path);
            if ($filename !== '') {
                $filenames[$filename] = $filename;
            }
        }

        $conditions = [];
        if ($imageIds !== []) {
            $conditions[] = ['Images.id IN' => $imageIds];
        }
        if ($imagePaths !== []) {
            $conditions[] = ['Images.storage_path IN' => $imagePaths];
        }
        if ($filenames !== []) {
            $conditions[] = ['Images.filename IN' => array_values($filenames)];
        }
        if ($conditions === []) {
            return [];
        }

        $found = $this->imagesTable->find()
            ->where(['OR' => $conditions])
            ->all();
        $images = [];
        foreach ($found as $image) {
            if (!$image instanceof Image) {
                continue;
            }

            $images['id:' . (int)$image->id] = $image;
            $path = trim((string)$image->storage_path, '/');
            if ($path !== '') {
                $images['path:' . $path] = $image;
            }
            $filename = basename((string)$image->filename);
            if ($filename !== '') {
                $images['filename:' . $filename] = $image;
            }
        }

        return $images;
    }

    /**
     * Read a persisted image id from inline image markup.
     *
     * @param \DOMElement $imageElement
     */
    private function readImageId(DOMElement $imageElement): int
    {
        $value = trim($imageElement->getAttribute('data-image-id'));

        return ctype_digit($value) ? (int)$value : 0;
    }

    /**
     * Extract a local stored-image path from an image URL.
     *
     * @param string $src Image URL.
     */
    private function readStoredImagePath(string $src): string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/img/storage/')) {
            return '';
        }

        return trim(rawurldecode(substr($path, strlen('/img/storage/'))), '/');
    }

    /**
     * Determine whether an image is already inside a credit wrapper.
     *
     * @param \DOMElement $element
     */
    private function hasCreditWrapper(DOMElement $element): bool
    {
        $parent = $element->parentNode;
        while ($parent instanceof DOMElement) {
            if (str_contains(' ' . $parent->getAttribute('class') . ' ', ' blog-image-credit ')) {
                return true;
            }
            $parent = $parent->parentNode;
        }

        return false;
    }
}
