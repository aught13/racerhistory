<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Entity\ImageTag;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

/**
 * ImageTagService
 *
 * Service layer for image tag operations and tag-based image retrieval.
 */
class ImageTagService
{
    /**
     * Attach tags to an image (creates tags on demand).
     *
     * @param int $imageId Image id.
     * @param array<int|string,string|array{slug:string,name?:string}> $tags Tag names or slugs.
     * @return void
     */
    public function attachTags(int $imageId, array $tags): void
    {
        if (!$tags) {
            return;
        }

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        /** @var \App\Model\Entity\Image $image */
        $image = $imagesTable->get($imageId, contain: ['ImageTags']);

        $existingTagIds = [];
        foreach ($image->image_tags as $tag) {
            $existingTagIds[] = $tag->id;
        }

        $tagEntities = [];
        foreach ($tags as $tag) {
            if (is_array($tag) && isset($tag['slug'])) {
                $slug = (string)$tag['slug'];
                $name = isset($tag['name']) ? (string)$tag['name'] : $slug;
            } else {
                $name = trim((string)$tag);
                if ($name === '') {
                    continue;
                }
                $slug = Text::slug($name) ?: strtolower($name);
            }

            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $name, 'slug' => $slug]);
                $tagsTable->save($existing);
            } else {
                $shouldUpdateName = false;
                if ($name !== '') {
                    $existingName = (string)($existing->get('name') ?? '');
                    $existingSlug = (string)($existing->get('slug') ?? '');
                    if ($existingName === $existingSlug || strcasecmp($existingName, $existingSlug) === 0) {
                        $shouldUpdateName = true;
                    } else {
                        if (
                            preg_match(
                                '/^(?:roster|team ?season|team_season_roster|person)[\s_-]*\d+$/i',
                                $existingName,
                            )
                        ) {
                            $shouldUpdateName = true;
                        }
                    }
                }
                if ($shouldUpdateName) {
                    $existing->set('name', $name);
                    $tagsTable->save($existing);
                }
            }

            $existingId = (int)($existing->get('id') ?? 0);
            if ($existingId > 0 && !in_array($existingId, $existingTagIds, true)) {
                $tagEntities[] = $existing;
            }
        }

        if ($tagEntities) {
            $imagesTable->ImageTags->link($image, $tagEntities);
        }
    }

    /**
     * Get images that match all given tag slugs.
     *
     * @param array<int,string> $tagSlugs Tag slugs that must all be present.
     * @param int $limit Result limit.
     * @return array<int,\App\Model\Entity\Image>
     */
    public function getImagesByAllTags(array $tagSlugs, int $limit = 10): array
    {
        $tagSlugs = array_values(array_filter(array_map('strval', $tagSlugs)));
        if (!$tagSlugs) {
            return [];
        }
        $needed = count($tagSlugs);
        $images = TableRegistry::getTableLocator()->get('Images');

        $query = $images->find()
            ->select($images)
            ->select(['tag_count' => $images->query()->func()->count('DISTINCT ImageTags.slug')])
            ->matching('ImageTags', function ($q) use ($tagSlugs) {
                return $q->where(['ImageTags.slug IN' => $tagSlugs]);
            })
            ->groupBy(['Images.id'])
            ->having("tag_count >= {$needed}")
            ->limit($limit);

        $rows = $query->all()->toList();

        /** @var array<int,\App\Model\Entity\Image> $imagesList */
        $imagesList = array_values(array_filter($rows, static fn($row): bool => $row instanceof Image));

        return $imagesList;
    }

    /**
     * Convenience: images tagged for a person.
     *
     * @param int $personId
     * @param int $limit
     */
    public function getImagesForPerson(int $personId, int $limit = 10): array
    {
        return $this->getImagesByAllTags(["person-{$personId}"], $limit);
    }

    /**
     * Convenience: images tagged for a team season.
     *
     * @param int $teamSeasonId
     * @param int $limit
     */
    public function getImagesForTeamSeason(int $teamSeasonId, int $limit = 10): array
    {
        return $this->getImagesByAllTags(["teamseason-{$teamSeasonId}"], $limit);
    }

    /**
     * Convenience: roster image (person + team season).
     *
     * @param int $personId
     * @param int $teamSeasonId
     * @param int $limit
     */
    public function getRosterImages(int $personId, int $teamSeasonId, int $limit = 1): array
    {
        return $this->getImagesByAllTags([
            "person-{$personId}",
            "teamseason-{$teamSeasonId}",
            'roster',
        ], $limit);
    }

    /**
     * Get image tagged with a roster entry.
     *
     * @param int $rosterId Team season roster ID
     * @param int $limit
     * @return array<int,\App\Model\Entity\Image>
     */
    public function getRosterEntryImage(int $rosterId, int $limit = 1): array
    {
        return $this->getImagesByAllTags(["team_season_roster-{$rosterId}"], $limit);
    }

    /**
     * Resolve or create and return ImageTags for provided slugs.
     *
     * @param array $tagSlugs
     * @return array<int,\App\Model\Entity\ImageTag>
     */
    public function ensureTags(array $tagSlugs): array
    {
        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $tags = [];
        foreach ($tagSlugs as $slug) {
            $slug = Text::slug($slug) ?: strtolower($slug);
            $existing = $tagsTable->find()->where(['slug' => $slug])->first();
            if (!$existing) {
                $existing = $tagsTable->newEntity(['name' => $slug, 'slug' => $slug]);
                $tagsTable->save($existing);
            }
            if ($existing instanceof ImageTag) {
                $tags[] = $existing;
            }
        }

        return $tags;
    }
}
