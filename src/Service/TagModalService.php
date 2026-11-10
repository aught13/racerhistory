<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\BlogTagsTable;
use App\Model\Table\ImageTagsTable;
use Cake\ORM\Table;

class TagModalService
{
    private const IMAGE_FORM_FIELDS = [
        'person_select',
        'team_select',
        'teamseason_select',
        'game_select',
        'site_select',
        'opponent_select',
        'sport_select',
        'roster_select',
        'tags',
    ];

    private const BLOG_POST_FORM_FIELDS = [
        'person_select',
        'team_select',
        'teamseason_select',
        'game_select',
        'site_select',
        'opponent_select',
        'sport_select',
        'roster_select',
        'tags',
        'preset_tags',
    ];

    /**
     * Create the tag modal service with its workflow dependencies.
     *
     * @param \App\Service\ImagesAdminService $imagesAdminService The image administration service.
     * @param \App\Service\BlogPostsAdminService $blogPostsAdminService The blog post administration service.
     * @param \App\Service\TeamService $teamService The team service.
     * @param \App\Service\TeamSeasonService $teamSeasonService The team season service.
     * @param \App\Service\GameService $gameService The game service.
     * @param \App\Service\SiteService $siteService The site service.
     * @param \App\Service\OpponentService $opponentService The opponent service.
     * @param \App\Service\TeamSportContextService $teamSportContextService The team sport context service.
     * @param \App\Model\Table\ImageTagsTable $imageTagsTable The image tags table.
     * @param \App\Model\Table\BlogTagsTable $blogTagsTable The blog tags table.
     * @param \App\Service\TaggingServiceFactory $taggingServiceFactory The tagging service factory.
     */
    public function __construct(
        private ImagesAdminService $imagesAdminService,
        private BlogPostsAdminService $blogPostsAdminService,
        private TeamService $teamService,
        private TeamSeasonService $teamSeasonService,
        private GameService $gameService,
        private SiteService $siteService,
        private OpponentService $opponentService,
        private TeamSportContextService $teamSportContextService,
        private ImageTagsTable $imageTagsTable,
        private BlogTagsTable $blogTagsTable,
        private TaggingServiceFactory $taggingServiceFactory,
    ) {
    }

    /**
     * @param string $subject The taggable subject type.
     * @param int $id The taggable subject ID.
     * @param mixed $identity The current identity.
     * @return array<string,mixed>
     */
    public function modalViewData(string $subject, int $id, mixed $identity): array
    {
        $subject = strtolower($subject);
        $canonicalSubject = $this->canonicalSubject($subject);

        if ($canonicalSubject === 'images') {
            $viewData = $this->imagesAdminService->getTagsPageData($id, $identity);
        } elseif ($canonicalSubject === 'blogposts') {
            $post = $id > 0
                ? $this->blogPostsAdminService->getEditEntity($id, $identity)
                : $this->blogPostsAdminService->newEntity();
            $viewData = $this->blogPostsAdminService->buildFormViewData($post, $identity);
            $viewData['post'] = $post;
        } else {
            $viewData = $this->fallbackViewData();
        }

        return array_merge($viewData, [
            'subject' => $canonicalSubject ?? $subject,
            'subjectId' => $id,
        ]);
    }

    /**
     * @param string $subject The taggable subject type.
     * @param int $id The taggable subject ID.
     * @param array<string,mixed> $data
     * @return array{success:bool,applied?:array,tags?:array,formFields?:array}
     */
    public function apply(string $subject, int $id, array $data): array
    {
        $canonicalSubject = $this->canonicalSubject(strtolower($subject));
        if ($canonicalSubject === null) {
            return ['success' => false];
        }

        $taggingService = $canonicalSubject === 'images'
            ? $this->taggingServiceFactory->forImages()
            : $this->taggingServiceFactory->forBlogPosts();
        $applied = $taggingService->applyFromData($id, $data);
        $tagsTable = $canonicalSubject === 'images' ? $this->imageTagsTable : $this->blogTagsTable;
        $formKeys = $canonicalSubject === 'images'
            ? self::IMAGE_FORM_FIELDS
            : self::BLOG_POST_FORM_FIELDS;

        return [
            'success' => true,
            'applied' => $applied,
            'tags' => $this->tagLabels($tagsTable, $applied),
            'formFields' => array_intersect_key($data, array_flip($formKeys)),
        ];
    }

    /**
     * Resolve a supported subject alias to its canonical name.
     *
     * @param string $subject The submitted subject name.
     * @return string|null
     */
    private function canonicalSubject(string $subject): ?string
    {
        return match ($subject) {
            'images', 'image' => 'images',
            'blogposts', 'blog-posts', 'blog_post', 'post' => 'blogposts',
            default => null,
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function fallbackViewData(): array
    {
        $sports = [];
        foreach ($this->teamSportContextService->getLegacySportOptions() as $sportId => $label) {
            $sports[] = [
                'id' => (int)$sportId,
                'label' => (string)$label,
            ];
        }

        return [
            'teams' => $this->teamService->getTeamsForSelect(),
            'teamSeasons' => $this->teamSeasonService->getTeamSeasonsForSelect(),
            'games' => $this->gameService->getRecentGamesForSelect(200),
            'sites' => $this->siteService->getSitesForSelect(),
            'opponents' => $this->opponentService->getOpponentsForSelect(),
            'sports' => $sports,
            'currentTags' => [],
            'tagString' => '',
        ];
    }

    /**
     * @param \Cake\ORM\Table $tagsTable The table containing tag records.
     * @param array<int,string> $slugs
     * @return array<int,array{slug:string,name:string}>
     */
    private function tagLabels(Table $tagsTable, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $tags = [];
        foreach ($tagsTable->find()->select(['slug', 'name'])->where(['slug IN' => $slugs])->all() as $tag) {
            $tags[] = [
                'slug' => (string)$tag->get('slug'),
                'name' => (string)$tag->get('name'),
            ];
        }

        return $tags;
    }
}
