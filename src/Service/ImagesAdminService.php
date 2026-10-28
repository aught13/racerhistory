<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use App\Model\Table\UsersTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Number;
use Cake\ORM\Query\SelectQuery as OrmSelectQuery;
use Cake\Utility\Text;

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
    private TaggingServiceFactory $taggingServiceFactory;
    private UsersTable $usersTable;
    private TeamService $teamService;
    private TeamSeasonService $teamSeasonService;
    private GameService $gameService;
    private SiteService $siteService;
    private OpponentService $opponentService;
    private TeamSportContextService $teamSportContextService;
    private ImageTagUiService $imageTagUiService;

    /**
     * @param \App\Model\Table\ImagesTable $imagesTable
     * @param \App\Service\ImageDeleteService $imageDeleteService
     * @param \App\Service\ImageEditService $imageEditService
     * @param \App\Service\RbacPermissionService $rbacPermissionService
     * @param \App\Service\TaggingServiceFactory $taggingServiceFactory
     * @param \App\Model\Table\UsersTable $usersTable
     * @param \App\Service\TeamService $teamService
     * @param \App\Service\TeamSeasonService $teamSeasonService
     * @param \App\Service\GameService $gameService
     * @param \App\Service\SiteService $siteService
     * @param \App\Service\OpponentService $opponentService
     * @param \App\Service\TeamSportContextService $teamSportContextService
     * @param \App\Service\ImageTagUiService $imageTagUiService
     */
    public function __construct(
        ImagesTable $imagesTable,
        ImageDeleteService $imageDeleteService,
        ImageEditService $imageEditService,
        RbacPermissionService $rbacPermissionService,
        TaggingServiceFactory $taggingServiceFactory,
        UsersTable $usersTable,
        TeamService $teamService,
        TeamSeasonService $teamSeasonService,
        GameService $gameService,
        SiteService $siteService,
        OpponentService $opponentService,
        TeamSportContextService $teamSportContextService,
        ImageTagUiService $imageTagUiService,
    ) {
        $this->imagesTable = $imagesTable;
        $this->imageDeleteService = $imageDeleteService;
        $this->imageEditService = $imageEditService;
        $this->rbacPermissionService = $rbacPermissionService;
        $this->taggingServiceFactory = $taggingServiceFactory;
        $this->usersTable = $usersTable;
        $this->teamService = $teamService;
        $this->teamSeasonService = $teamSeasonService;
        $this->gameService = $gameService;
        $this->siteService = $siteService;
        $this->opponentService = $opponentService;
        $this->teamSportContextService = $teamSportContextService;
        $this->imageTagUiService = $imageTagUiService;
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
        $teamSeasonLabels = $this->teamSeasonService->getTeamSeasonsForSelect();
        $gameLabels = $this->gameService->getRecentGamesForSelect(200);
        $siteLabels = $this->siteService->getSitesForSelect();
        $opponents = $this->opponentService->getOpponentsForSelect();
        $teams = $this->teamService->getTeamsForSelect();
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
        $data['tags'] = is_string($data['common_tags'] ?? null) ? $data['common_tags'] : [];

        return $this->taggingServiceFactory->forImages()->buildTagsFromData($data);
    }

    /**
     * Normalize per-file bulk-upload tags and optional context labels.
     *
     * @param array|string|null $tagsInput
     * @param array|string|null $contextInput
     * @param string $index
     * @return array<int,string|array{slug:string,name:string}>
     */
    public function buildBulkUploadTags(
        array|string|null $tagsInput,
        array|string|null $contextInput,
        string $index,
    ): array {
        $tags = [];

        $rawTags = null;
        if (is_array($tagsInput)) {
            if (array_key_exists($index, $tagsInput)) {
                $rawTags = $tagsInput[$index];
            } elseif (array_key_exists((int)$index, $tagsInput)) {
                $rawTags = $tagsInput[(int)$index];
            }
        } elseif (is_string($tagsInput)) {
            $rawTags = $tagsInput;
        }

        if (is_string($rawTags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags)), fn($tag) => $tag !== ''));
        } elseif (is_array($rawTags)) {
            $tags = array_values(array_filter(
                array_map(static fn($tag): string => trim((string)$tag), $rawTags),
                static fn(string $tag): bool => $tag !== '',
            ));
        }

        $contextValue = null;
        if (is_array($contextInput)) {
            if (array_key_exists($index, $contextInput)) {
                $contextValue = $contextInput[$index];
            } elseif (array_key_exists((int)$index, $contextInput)) {
                $contextValue = $contextInput[(int)$index];
            }
        } elseif (is_string($contextInput)) {
            $contextValue = $contextInput;
        }

        if (is_string($contextValue)) {
            $contextValue = trim($contextValue);
            if ($contextValue !== '') {
                $slug = strtolower(Text::slug($contextValue, '-'));
                if ($slug === '') {
                    $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $contextValue), '-'));
                }

                $tags[] = [
                    'slug' => 'context-' . ($slug ?: 'context'),
                    'name' => $contextValue,
                ];
            }
        }

        return $tags;
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
        $users = $this->usersTable->find('list', ['keyField' => 'id', 'valueField' => 'username'])
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

        $teams = $this->teamService->getTeamsForSelect();
        $teamSeasons = $this->teamSeasonService->getTeamSeasonsForSelect();
        $games = $this->gameService->getRecentGamesForSelect(200);
        $sites = $this->siteService->getSitesForSelect();
        $opponents = $this->opponentService->getOpponentsForSelect();
        $sports = $this->getSportsForSelect();

        $ui = $this->imageTagUiService->formatTagsForUi($image->image_tags ?? []);
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
        foreach ($this->teamSportContextService->getLegacySportOptions() as $sportId => $label) {
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
        $this->taggingServiceFactory->forImages()->applyFromData($id, $data);
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
