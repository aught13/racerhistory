<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

class ImageBrowseService
{
    /**
     * Build the payload for the admin image browse modal.
     *
     * @param string|null $tag
     * @param int|null $limit
     * @return array{success: bool, images: array<int, array{id: int, url: string, thumbnail_url: string, original_name: string, tags: array<int, string>}>}
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

        $results = [];
        foreach ($query->all() as $image) {
            $results[] = [
                'id' => (int)$image->id,
                'url' => '/images/serve/' . $image->id,
                'thumbnail_url' => '/images/serve/' . $image->id . '?' . http_build_query([
                    'w' => 300,
                    'h' => 300,
                    'fit' => 'cover',
                ]),
                'original_name' => (string)$image->original_name,
                'tags' => array_map(static fn($t) => (string)$t->name, $image->image_tags ?? []),
            ];
        }

        return [
            'success' => true,
            'images' => $results,
        ];
    }
}
