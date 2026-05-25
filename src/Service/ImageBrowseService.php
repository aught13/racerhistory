<?php
declare(strict_types=1);

namespace App\Service;


use App\Model\Entity\Image;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

class ImageBrowseService
{
    /**
     * Build the payload for the admin image browse modal.
     *
     * @param string|null $tag
     * @param int|null $limit
    * @return array{success: bool, images: array<int, array{id: int, url: string, thumbnail_url: string, hero_url: string, original_name: string, tags: array<int, string>}>}
     */
    public function browse(?string $tag = null, ?int $limit = null): array
    {
        $normalizedTag = $tag !== null ? trim($tag) : '';
        if ($normalizedTag === '') {
            $normalizedTag = null;
        }

        $requestedLimit = $limit ?? 50;
        $requestedLimit = min((int)$requestedLimit, 100);
        $requestedLimit = max($requestedLimit, 0);

        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        $query = $imagesTable->find();

        if ($normalizedTag !== null) {
            $query->innerJoinWith('ImageTags', function (SelectQuery $q) use ($normalizedTag) {
                return $q->where(['ImageTags.slug' => $normalizedTag]);
            });
        }

        $query
            ->contain(['ImageTags'])
            ->orderByDesc('Images.id')
            ->limit($requestedLimit);

        $imageUrlService = new ImageUrlService($imagesTable);
        $results = [];
        foreach ($query->all() as $image) {
            if (!$image instanceof Image) {
                continue;
            }

            $tags = [];
            foreach ((array)($image->image_tags ?? []) as $tag) {
                if (is_object($tag) && isset($tag->name)) {
                    $tags[] = (string)$tag->name;
                }
            }

            $results[] = [
                'id' => (int)$image->id,
                'url' => $imageUrlService->urlForImage($image),
                'thumbnail_url' => $imageUrlService->urlForImage($image, ['variant' => 'thumb']),
                'hero_url' => $imageUrlService->urlForImage($image, ['variant' => 'hero']),
                'original_name' => (string)$image->original_name,
                'tags' => $tags,
            ];
        }

        return [
            'success' => true,
            'images' => $results,
        ];
    }
}
