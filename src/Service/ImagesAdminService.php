<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Number;
use Cake\ORM\Query\SelectQuery as OrmSelectQuery;
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

    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Model\Table\ImagesTable|null $imagesTable
     * @param \App\Service\ImageDeleteService|null $imageDeleteService
     * @param \App\Service\ImageEditService|null $imageEditService
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService
     */
    public function __construct(
        ?ImagesTable $imagesTable = null,
        ?ImageDeleteService $imageDeleteService = null,
        ?ImageEditService $imageEditService = null,
        ?RbacPermissionService $rbacPermissionService = null,
    ) {
        /** @var \App\Model\Table\ImagesTable $table */
        $table = $imagesTable ?? TableRegistry::getTableLocator()->get('Images');
        $this->imagesTable = $table;
        $this->imageDeleteService = $imageDeleteService ?? new ImageDeleteService();
        $this->imageEditService = $imageEditService ?? new ImageEditService();
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
    }

    /**
     * Total images count for index heading text.
     *
     * @param mixed $identity Current authenticated identity.
     * @return int
     */
    public function getTotalCount(mixed $identity = null): int
    {
        $query = $this->applyScope($identity, 'index', $this->imagesTable->find(), 'Images');

        return (int)$query->count();
    }

    /**
     * Build DataTables server-side payload for admin images index.
     *
     * @param array<string,mixed> $params
     * @param mixed $identity Current authenticated identity.
     * @return array{draw:int,total:int,filtered:int,data:array<int,array<string,mixed>>}
     */
    public function buildIndexDataTablesResponse(array $params, mixed $identity = null): array
    {
        $draw = max(0, (int)($params['draw'] ?? 0));
        $start = max(0, (int)($params['start'] ?? 0));
        $length = (int)($params['length'] ?? 15);
        if ($length <= 0) {
            $length = 15;
        }
        // Clamp aggressive scroller batch sizes to reduce origin spikes.
        $length = min($length, 45);

        $searchValue = trim((string)($params['searchValue'] ?? ''));
        $orderDir = strtolower((string)($params['orderDir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = (string)($params['orderColumn'] ?? 'id');
        $sortField = $this->resolveIndexSortField($orderColumn);

        $baseQuery = $this->applyScope($identity, 'index', $this->imagesTable->find(), 'Images');
        $total = (int)(clone $baseQuery)->count();

        $query = clone $baseQuery;
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

        $filtered = (int)(clone $query)->count();

        $rows = $query
            ->order([$sortField => $orderDir])
            ->offset($start)
            ->limit($length)
            ->all();

        $data = [];
        foreach ($rows as $row) {
            $rowData = is_array($row) ? $row : $row->toArray();

            // 1. Decode the JSON string into a PHP array
            $variantsArray = json_decode($rowData['variants'], true);

            // 2. Extract the 'thumb' value
            $thumbData = (string)($variantsArray['thumb']['file'] ?? '');

            $id = (int)($rowData['id'] ?? 0);
            $path = (string)($rowData['storage_subdir'] ?? '');
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

            $thumbUrl = '/img/storage/' . $path . '/' . $thumbData;
            $editUrl = '/admin/images/edit/' . $id;

            $data[] = [
                'id' => $id,
                'preview' => sprintf(
                    '<img src="%s" alt="" ' .
                    'class="img-thumbnail js-admin-image-thumb" width="60" height="60" ' .
                    'style="width:60px; height:60px; object-fit:cover;" ' .
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
        $sports = $this->getSportsForSelect();

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
                'service' => 'sport',
            ],
        ];

        $personService = new PersonService();
        $teamSeasonService = new TeamSeasonService();
        $rosterService = new TeamSeasonRosterService();
        $teamSportContextService = new TeamSportContextService();

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
                    } elseif ($meta['service'] === 'sport') {
                        $display = $teamSportContextService->resolveSportNameFromTeam(
                            (object)['sport_id' => $id],
                        ) ?? '';
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
     * @param mixed $identity Current authenticated identity.
     * @return array{image:\App\Model\Entity\Image,currentTags:array<int,mixed>}
     */
    public function getEditPageData(int $id, mixed $identity = null): array
    {
        $image = $this->getImageWithTags($id, $identity, 'update');
        $currentTags = $image->image_tags ?? [];

        // Provide a users list for owner selection in the admin edit UI
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $users = $usersTable->find('list', ['keyField' => 'id', 'valueField' => 'username'])
            ->order(['username' => 'ASC'])
            ->toArray();

        $canManageImageOwner = $this->rbacPermissionService->isAdmin($identity);
        $ownerLabel = $image->user_id && isset($users[(int)$image->user_id])
            ? $users[(int)$image->user_id]
            : 'Unassigned';

        return compact('image', 'currentTags', 'users', 'canManageImageOwner', 'ownerLabel');
    }

    /**
     * Save editable metadata fields for an image.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,image:\App\Model\Entity\Image}
     */
    public function updateMetadata(int $id, array $data, mixed $identity = null): array
    {
        $image = $this->getImageWithTags($id, $identity, 'update');
        $patchData = [];
        if (array_key_exists('original_name', $data)) {
            $patchData['original_name'] = (string)$data['original_name'];
        }
        if (array_key_exists('status', $data)) {
            $patchData['status'] = (string)$data['status'];
        }
        if (array_key_exists('photo_credit', $data)) {
            $patchData['photo_credit'] = $data['photo_credit'] === null ? null : (string)$data['photo_credit'];
        }
        if (array_key_exists('user_id', $data)) {
            $uid = (int)$data['user_id'];
            $patchData['user_id'] = $uid > 0 ? $uid : null;
        }

        if (!$this->rbacPermissionService->isAdmin($identity)) {
            unset($patchData['user_id']);
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
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getTagsPageData(int $id, mixed $identity = null): array
    {
        $image = $this->getImageWithTags($id, $identity, 'update');

        $teams = (new TeamService())->getTeamsForSelect();
        $teamSeasons = (new TeamSeasonService())->getTeamSeasonsForSelect();
        $games = (new GameService())->getRecentGamesForSelect(200);
        $sites = (new SiteService())->getSitesForSelect();
        $opponents = (new OpponentService())->getOpponentsForSelect();
        $sports = $this->getSportsForSelect();

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
     * @return array<int,array{id:int,label:string}>
     */
    private function getSportsForSelect(): array
    {
        $sports = [];
        foreach ((new TeamSportContextService())->getLegacySportOptions() as $sportId => $label) {
            $sports[] = [
                'id' => (int)$sportId,
                'label' => (string)$label,
            ];
        }

        return $sports;
    }

    /**
     * Apply image tags from request payload.
     *
     * @param int $id
     * @param array<string,mixed> $data
     * @param mixed $identity Current authenticated identity.
     * @return void
     */
    public function applyTags(int $id, array $data, mixed $identity = null): void
    {
        $this->getImageWithTags($id, $identity, 'update');
        $tagging = TaggingService::forImages();
        $tagging->applyFromData($id, $data);
    }

    /**
     * Delete a single image and attached references.
     *
     * @param int $id
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function deleteImage(int $id, mixed $identity = null): array
    {
        $this->getImageById($id, $identity, 'delete');

        return $this->imageDeleteService->deleteImageById($id);
    }

    /**
     * Delete multiple images and return summary.
     *
     * @param array<int|string,mixed> $ids
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function bulkDelete(array $ids, mixed $identity = null): array
    {
        if ($this->rbacPermissionService->isAdmin($identity)) {
            return $this->imageDeleteService->bulkDeleteImages($ids);
        }

        $normalizedIds = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if ($normalizedIds === []) {
            return ['deleted' => 0];
        }

        $query = $this->applyScope($identity, 'delete', $this->imagesTable->find(), 'Images')
            ->select(['id'])
            ->where(['Images.id IN' => $normalizedIds])
            ->disableHydration();

        $allowedIds = [];
        foreach ($query->all() as $row) {
            if (is_array($row) && !empty($row['id'])) {
                $allowedIds[] = (int)$row['id'];
            }
        }

        return $this->imageDeleteService->bulkDeleteImages($allowedIds);
    }

    /**
     * Load an image entity for manipulation flows.
     *
     * @param int $id Image id.
     * @param mixed $identity Current authenticated identity.
     * @param string $ability Scope ability to apply when loading the image.
     * @return \App\Model\Entity\Image
     */
    public function getImageById(int $id, mixed $identity = null, string $ability = 'read'): Image
    {
        /** @var \App\Model\Entity\Image|null $image */
        $image = $this->applyScope($identity, $ability, $this->imagesTable->find(), 'Images')
            ->where(['Images.id' => $id])
            ->first();
        if (!$image instanceof Image) {
            throw new RecordNotFoundException('Image not found or not accessible.');
        }

        return $image;
    }

    /**
     * Apply manipulations to an image by id.
     *
     * @param int $id
     * @param array<string,mixed> $manipulations
     * @param string $mode
     * @param array<string,mixed>|null $thumbCrop
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function manipulateImage(
        int $id,
        array $manipulations,
        string $mode = 'apply',
        ?array $thumbCrop = null,
        mixed $identity = null,
    ): array {
        $image = $this->getImageById($id, $identity, 'update');

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
     * @param int $id Image id.
     * @param array<string,int> $crop Crop coordinates.
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function cropThumb(int $id, array $crop, mixed $identity = null): array
    {
        $image = $this->getImageById($id, $identity, 'update');

        return $this->imageEditService->cropThumbVariant($this->imagesTable, $image, $crop);
    }

    /**
     * Apply a hero crop by image id.
     *
     * @param int $id Image id.
     * @param array<string,int> $crop Crop coordinates.
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function cropHero(int $id, array $crop, mixed $identity = null): array
    {
        $image = $this->getImageById($id, $identity, 'update');

        return $this->imageEditService->cropHeroVariant($this->imagesTable, $image, $crop);
    }

    /**
     * Load image with tag associations.
     *
     * @param int $id Image id.
     * @param mixed $identity Current authenticated identity.
     * @param string $ability Scope ability to apply when loading the image.
     * @return \App\Model\Entity\Image
     */
    private function getImageWithTags(int $id, mixed $identity = null, string $ability = 'read'): Image
    {
        /** @var \App\Model\Entity\Image|null $image */
        $image = $this->applyScope(
            $identity,
            $ability,
            $this->imagesTable->find()->contain(['ImageTags']),
            'Images',
        )
            ->where(['Images.id' => $id])
            ->first();
        if (!$image instanceof Image) {
            throw new RecordNotFoundException('Image not found or not accessible.');
        }

        return $image;
    }

    /**
     * Apply either the framework policy scope or the RBAC service fallback to a query.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $action Scope action name.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @param string $modelName Canonical RBAC model name.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function applyScope(
        mixed $identity,
        string $action,
        OrmSelectQuery $query,
        string $modelName,
    ): OrmSelectQuery {
        if (is_object($identity) && method_exists($identity, 'applyScope')) {
            return $identity->applyScope($action, $query);
        }

        return $this->rbacPermissionService->scopeQuery($identity, $modelName, $query, $action, 'user_id');
    }
}
