<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\I18n\Number;
use Cake\ORM\TableRegistry;

/**
 * ImagesAdminService
 *
 * Owns admin-side orchestration for image management pages.
 *
 * Responsibilities:
 * - Load index and edit/tag page data.
 * - Apply metadata edits and tag updates.
 * - Build bulk-upload lookup lists and common entity tag payloads.
 * - Delegate deletion and manipulation flows to specialized image services.
 *
 * HTTP concerns (request method checks, flash messages, redirects, JSON
 * response creation) remain in Admin/ImagesController.
 */
class ImagesAdminService
{
    /**
     * @var \App\Model\Table\ImagesTable
     */
    private ImagesTable $imagesTable;

    /**
     * @var \App\Service\ImageDeleteService
     */
    private ImageDeleteService $imageDeleteService;

    /**
     * @var \App\Service\ImageEditService
     */
    private ImageEditService $imageEditService;

    /**
     * @param \App\Model\Table\ImagesTable|null $imagesTable
     * @param \App\Service\ImageDeleteService|null $imageDeleteService
     * @param \App\Service\ImageEditService|null $imageEditService
     */
    public function __construct(
        ?ImagesTable $imagesTable = null,
        ?ImageDeleteService $imageDeleteService = null,
        ?ImageEditService $imageEditService = null,
    ) {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = $imagesTable ?? TableRegistry::getTableLocator()->get('Images');
        $this->imagesTable = $table;
        $this->imageDeleteService = $imageDeleteService ?? new ImageDeleteService();
        $this->imageEditService = $imageEditService ?? new ImageEditService();
    }

    /**
     * Total images count for index heading text.
     */
    public function getTotalCount(): int
    {
        return (int)$this->imagesTable->find()->count();
    }

    /**
     * Build DataTables server-side payload for admin images index.
     *
     * @param array<string,mixed> $params
     * @return array{draw:int,total:int,filtered:int,data:array<int,array<string,mixed>>}
     */
    public function buildIndexDataTablesResponse(array $params): array
    {
        $draw = max(0, (int)($params['draw'] ?? 0));
        $start = max(0, (int)($params['start'] ?? 0));
        $length = (int)($params['length'] ?? 15);
        if ($length <= 0) {
            $length = 15;
        }
        $length = min($length, 120);

        $searchValue = trim((string)($params['searchValue'] ?? ''));
        $orderDir = strtolower((string)($params['orderDir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = (string)($params['orderColumn'] ?? 'id');
        $sortField = $this->resolveIndexSortField($orderColumn);

        $total = (int)$this->imagesTable->find()->count();

        $query = $this->imagesTable->find();
        if ($searchValue !== '') {
            $conditions = [
                'Images.original_name LIKE' => '%' . $searchValue . '%',
                'Images.filename LIKE' => '%' . $searchValue . '%',
                'Images.mime LIKE' => '%' . $searchValue . '%',
                'Images.status LIKE' => '%' . $searchValue . '%',
            ];
            if (ctype_digit($searchValue)) {
                $conditions[] = ['Images.id' => (int)$searchValue];
            }
            $query->where(['OR' => $conditions]);
        }

        $filtered = (int)$query->count();

        $rows = $query
            ->order([$sortField => $orderDir])
            ->offset($start)
            ->limit($length)
            ->all();

        $data = [];
        foreach ($rows as $row) {
            $rowData = is_array($row) ? $row : $row->toArray();

            $id = (int)($rowData['id'] ?? 0);
            $originalName = (string)($rowData['original_name'] ?? '');
            $filename = (string)($rowData['filename'] ?? '');
            $name = $originalName !== '' ? $originalName : $filename;
            $mime = (string)($rowData['mime'] ?? '');
            $byteSize = (int)($rowData['byte_size'] ?? 0);
            $width = (int)($rowData['width'] ?? 0);
            $height = (int)($rowData['height'] ?? 0);
            $dimensions = $width > 0 && $height > 0 ? ($width . 'x' . $height) : '-';

            $status = (string)($rowData['status'] ?? 'unknown');
            $statusClass = strtolower($status) === 'active' ? 'bg-success' : 'bg-secondary';

            $thumbUrl = '/images/serve/' . $id . '?variant=thumb';
            $editUrl = '/admin/images/edit/' . $id;

            $data[] = [
                'id' => $id,
                'preview' => sprintf(
                    '<img src="%s" alt="" class="img-thumbnail" style="max-width:60px; height:auto;" ' .
                    'loading="lazy" decoding="async">',
                    $this->escapeHtml($thumbUrl),
                ),
                'original_name' => $this->escapeHtml($name),
                'mime' => '<code>' . $this->escapeHtml($mime) . '</code>',
                'size' => $this->escapeHtml((string)Number::toReadableSize($byteSize)),
                'dimensions' => $this->escapeHtml($dimensions),
                'status' => sprintf(
                    '<span class="badge %s">%s</span>',
                    $statusClass,
                    $this->escapeHtml($status),
                ),
                'actions' => sprintf(
                    '<a href="%s" class="btn btn-sm btn-primary">Edit</a>',
                    $this->escapeHtml($editUrl),
                ),
            ];
        }

        return [
            'draw' => $draw,
            'total' => $total,
            'filtered' => $filtered,
            'data' => $data,
        ];
    }

    /**
     * Resolve DataTables column key to a sortable database field.
     *
     * @param string $column
     * @return string
     */
    private function resolveIndexSortField(string $column): string
    {
        return match ($column) {
            'original_name' => 'Images.original_name',
            'mime' => 'Images.mime',
            'size' => 'Images.byte_size',
            'dimensions' => 'Images.width',
            'status' => 'Images.status',
            default => 'Images.id',
        };
    }

    /**
     * Escape helper for HTML fragments returned to DataTables.
     *
     * @param string $value
     * @return string
     */
    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build select-list data required by the bulk upload form.
     *
     * @return array<string,mixed>
     */
    public function getBulkUploadFormData(): array
    {
        $teamSeasonLabels = (new TeamSeasonService())->getTeamSeasonsForSelect();
        $gameLabels = (new GameService())->getRecentGamesForSelect(200);
        $siteLabels = (new SiteService())->getSitesForSelect();
        $opponents = (new OpponentService())->getOpponentsForSelect();
        $teams = (new TeamService())->getTeamsForSelect();
        $sports = (new SportService())->getSportsForSelect();

        return compact(
            'teamSeasonLabels',
            'gameLabels',
            'siteLabels',
            'opponents',
            'teams',
            'sports',
        );
    }

    /**
     * Build common entity tags from bulk-upload form data.
     *
     * @param array<string,mixed> $data
     * @return array<int,array<string,string>|string>
     */
    public function buildCommonEntityTags(array $data): array
    {
        $tagsToApply = [];

        $map = [
            'person_select' => [
                'prefix' => 'person-',
                'service' => 'person',
            ],
            'roster_select' => [
                'prefix' => 'team_season_roster-',
                'service' => 'roster',
            ],
            'teamseason_select' => [
                'prefix' => 'teamseason-',
                'service' => 'teamseason',
            ],
            'game_select' => [
                'prefix' => 'game-',
                'table' => 'Games',
                'label' => fn($row) => $row->opponent_name ?? 'game',
            ],
            'site_select' => [
                'prefix' => 'site-',
                'table' => 'Places',
                'label' => fn($row) => $row->place_city ?? 'site',
            ],
            'opponent_select' => [
                'prefix' => 'opponent-',
                'table' => 'Opponents',
                'label' => fn($row) => $row->opponent_name ?? 'opponent',
            ],
            'team_select' => [
                'prefix' => 'team-',
                'table' => 'Teams',
                'label' => fn($row) => $row->team_name ?? 'team',
            ],
            'sport_select' => [
                'prefix' => 'sport-',
                'table' => 'Sports',
                'label' => fn($row) => $row->sport_name ?? 'sport',
            ],
        ];

        $personService = new PersonService();
        $teamSeasonService = new TeamSeasonService();
        $rosterService = new TeamSeasonRosterService();

        $hasRoster = !empty($data['roster_select']) && (int)$data['roster_select'] > 0;

        foreach ($map as $field => $meta) {
            if ($hasRoster && $field === 'teamseason_select') {
                continue;
            }

            $skipFields = ['game_select', 'site_select', 'opponent_select', 'team_select', 'sport_select'];
            if ($hasRoster && in_array($field, $skipFields, true)) {
                continue;
            }

            if (empty($data[$field])) {
                continue;
            }

            $values = is_array($data[$field]) ? $data[$field] : [$data[$field]];
            foreach ($values as $value) {
                $id = (int)$value;
                if ($id <= 0) {
                    continue;
                }

                $slug = $meta['prefix'] . $id;

                $display = '';
                if (isset($meta['service'])) {
                    if ($meta['service'] === 'person') {
                        $display = $personService->getDisplayLabel($id);
                    } elseif ($meta['service'] === 'teamseason') {
                        $display = $teamSeasonService->getSportDisplayLabel($id);
                    } elseif ($meta['service'] === 'roster') {
                        $rosterData = $rosterService->getRosterDisplayData($id);
                        $display = $rosterData['team_season_label'] ?? $rosterData['label'] ?? 'Roster #' . $id;
                    }
                } else {
                    $table = TableRegistry::getTableLocator()->get((string)$meta['table']);
                    $row = $table->find()->select()->where(['id' => $id])->first();
                    $display = $row ? (string)$meta['label']($row) : '';
                }

                if ($display !== '') {
                    $tagsToApply[] = [
                        'slug' => $slug,
                        'name' => $display,
                    ];
                }
            }
        }

        if (!empty($data['common_tags']) && is_string($data['common_tags'])) {
            $commonTags = array_values(array_filter(
                array_map('trim', explode(',', $data['common_tags'])),
                fn($tag) => $tag !== '',
            ));
            foreach ($commonTags as $tag) {
                $tagsToApply[] = $tag;
            }
        }

        return $tagsToApply;
    }

    /**
     * Load image edit page data.
     *
     * @param int $id
     * @return array{image:\App\Model\Entity\Image,currentTags:array<int,mixed>}
     */
    public function getEditPageData(int $id): array
    {
        $image = $this->getImageWithTags($id);
        $currentTags = $image->image_tags ?? [];

        return compact('image', 'currentTags');
    }

    /**
     * Save editable metadata fields for an image.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @return array{success:bool,image:\App\Model\Entity\Image}
     */
    public function updateMetadata(int $id, array $data): array
    {
        $image = $this->getImageWithTags($id);

        $patchData = [];
        if (array_key_exists('original_name', $data)) {
            $patchData['original_name'] = (string)$data['original_name'];
        }
        if (array_key_exists('status', $data)) {
            $patchData['status'] = (string)$data['status'];
        }

        if ($patchData === []) {
            return ['success' => true, 'image' => $image];
        }

        $image = $this->imagesTable->patchEntity($image, $patchData);
        $success = (bool)$this->imagesTable->save($image);

        return ['success' => $success, 'image' => $image];
    }

    /**
     * Build all view data required by the tags UI.
     *
     * @param int $id
     * @return array<string,mixed>
     */
    public function getTagsPageData(int $id): array
    {
        $image = $this->getImageWithTags($id);

        $teams = (new TeamService())->getTeamsForSelect();
        $teamSeasons = (new TeamSeasonService())->getTeamSeasonsForSelect();
        $games = (new GameService())->getRecentGamesForSelect(200);
        $sites = (new SiteService())->getSitesForSelect();
        $opponents = (new OpponentService())->getOpponentsForSelect();
        $sports = (new SportService())->getSportsForSelect();

        $ui = (new ImageTagUiService())->formatTagsForUi($image->image_tags ?? []);
        $currentTags = $ui['currentTags'];
        $tagString = $ui['tagString'];

        return compact(
            'image',
            'currentTags',
            'teams',
            'teamSeasons',
            'games',
            'sites',
            'opponents',
            'sports',
            'tagString',
        );
    }

    /**
     * Apply image tags from request payload.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @return void
     */
    public function applyTags(int $id, array $data): void
    {
        $tagging = TaggingService::forImages();
        $tagging->applyFromData($id, $data);
    }

    /**
     * Delete a single image and attached references.
     *
     * @param int $id
     * @return array<string,mixed>
     */
    public function deleteImage(int $id): array
    {
        return $this->imageDeleteService->deleteImageById($id);
    }

    /**
     * Delete multiple images and return summary.
     *
     * @param array<int|string,mixed> $ids
     * @return array<string,mixed>
     */
    public function bulkDelete(array $ids): array
    {
        return $this->imageDeleteService->bulkDeleteImages($ids);
    }

    /**
     * Load an image entity for manipulation flows.
     *
     * @param int $id
     */
    public function getImageById(int $id): Image
    {
        /** @var \App\Model\Entity\Image $image */
        $image = $this->imagesTable->get($id);

        return $image;
    }

    /**
     * Apply manipulations to an image by id.
     *
     * @param int $id
     * @param array<string,mixed> $manipulations
     * @param string $mode
     * @param array<string,mixed>|null $thumbCrop
     * @return array<string,mixed>
     */
    public function manipulateImage(
        int $id,
        array $manipulations,
        string $mode = 'apply',
        ?array $thumbCrop = null,
    ): array {
        $image = $this->getImageById($id);

        return $this->imageEditService->manipulateImage(
            $this->imagesTable,
            $image,
            $manipulations,
            $mode,
            $thumbCrop,
        );
    }

    /**
     * Apply a thumb crop by image id.
     *
     * @param int $id
     * @param array<string,int> $crop
     * @return array<string,mixed>
     */
    public function cropThumb(int $id, array $crop): array
    {
        $image = $this->getImageById($id);

        return $this->imageEditService->cropThumbVariant($this->imagesTable, $image, $crop);
    }

    /**
     * Load image with tag associations.
     *
     * @param int $id
     * @return \App\Model\Entity\Image
     */
    private function getImageWithTags(int $id): Image
    {
        /** @var \App\Model\Entity\Image $image */
        $image = $this->imagesTable->get($id, contain: ['ImageTags']);

        return $image;
    }
}
