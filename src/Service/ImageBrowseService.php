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
     * @return array{
     *     success: bool,
     *     images: array<int, array{
     *         id: int,
     *         url: string,
     *         thumbnail_url: string,
     *         hero_url: string,
     *         original_name: string,
     *         tags: array<int, string>
     *     }>
     * }
     */
    /**
     * Backwards-compatible signature: accepts either (tag, limit) or (tag, search, limit).
     *
     * @param string|null $tag
     * @param mixed $searchOrLimit Either a search string or an integer limit for backward compatibility
     * @param int|null $limit
     * @return array{
     *     success: bool,
     *     images: array<int, array{
     *         id: int,
     *       url: string,
     *       thumbnail_url: string,
     *       hero_url: string,
     *       original_name: string,
     *       tags: array<int, string>
     *     }>
     * }
     */
    public function browse(?string $tag = null, mixed $searchOrLimit = null, ?int $limit = null): array
    {
        $normalizedTag = $tag !== null ? trim($tag) : '';
        if ($normalizedTag === '') {
            $normalizedTag = null;
        }

        // Normalize input for backward compatibility: callers historically passed
        // (tag, limit) where the second argument was an integer. If an integer
        // was provided in the second position and $limit is null, treat it as
        // the limit. Otherwise, treat the second argument as the search string.
        $search = null;
        if ($searchOrLimit !== null && is_int($searchOrLimit) && $limit === null) {
            $limit = $searchOrLimit;
        } elseif ($searchOrLimit !== null && is_string($searchOrLimit)) {
            $search = trim($searchOrLimit);
            if ($search === '') {
                $search = null;
            }
        }

        // Default to 50 results for browse; increase default when searching.
        if ($search !== null && $limit === null) {
            $requestedLimit = 500;
        } else {
            $requestedLimit = $limit ?? 50;
        }

        // Clamp limits to avoid excessive payloads
        $requestedLimit = min((int)$requestedLimit, 2000);
        $requestedLimit = max($requestedLimit, 0);

        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        $query = $imagesTable->find();

        // If a tag filter is provided, restrict to those images.
        if ($normalizedTag !== null) {
            $query->innerJoinWith('ImageTags', function (SelectQuery $q) use ($normalizedTag) {
                return $q->where(['ImageTags.slug' => $normalizedTag]);
            });
        }

        // If a search term is provided, allow searching image id, original_name, and tag names/slugs.
        if ($search !== null) {
            // Ensure tag fields are available for searching
            $query->leftJoinWith('ImageTags');
            $like = '%' . $search . '%';
            $query->where(function ($exp, SelectQuery $q) use ($search, $like) {
                $conds = [];
                // numeric id match
                if (is_numeric($search)) {
                    $conds[] = ['Images.id' => (int)$search];
                }
                $conds[] = $q->newExpr()->like('Images.original_name', $like);
                $conds[] = $q->newExpr()->like('ImageTags.name', $like);
                $conds[] = $q->newExpr()->like('ImageTags.slug', $like);

                // Use a plain OR array to be compatible across Cake versions
                return $exp->add(['OR' => $conds]);
            });
            // Protect against duplicates from joins
            $query->group(['Images.id']);
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
