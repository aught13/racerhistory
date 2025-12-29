<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ImageProcessor;
use App\Service\ImageStorageService;
use App\Service\TaggingService;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Admin Images Controller
 *
 * Handles image upload, storage, and serving for the admin interface.
 *
 * @property \App\Model\Table\ImagesTable $Images
 * @property \App\Model\Table\ImageUsagesTable $ImageUsages
 */
class ImagesController extends AppController
{
    /**
     * Controller initialization: unlock actions from FormProtection when the UI
     * uses custom (non-FormHelper) fields.
     */
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            foreach (['upload', 'bulkUpload', 'bulkUploadForm', 'manipulate', 'tags', 'cropThumb'] as $action) {
                if (!in_array($action, $current, true)) {
                    $current[] = $action;
                }
            }
            $this->FormProtection->setConfig('unlockedActions', $current);
        }
    }

    /**
     * Upload an image and persist original + variants.
     * Stores files outside webroot (storage/images) and serves via serve() action.
     * Accepts optional usage context: model, foreign_key, field.
     */
    public function upload(): Response
    {
        $this->request->allowMethod(['post']);
        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }
        try {
            // Prevent stray prior output (warnings) from corrupting JSON response
            if (ob_get_length()) {
                ob_clean();
            }
            $file = $this->extractUploaded();
            if (!$file) {
                return $this->json(['success' => false, 'error' => 'No file uploaded']);
            }

            $tagging = TaggingService::forImages();
            $tags = $tagging->parseTagsFromRequest($this->request);
            $manipulations = $this->collectManipulations();

            $storage = new ImageStorageService(null, $tagging);
            $result = $storage->upload($file, $tags, $manipulations);

            if (!empty($result['success'])) {
                /** @var \App\Model\Entity\Image $image */
                $image = $result['image'];
                $this->maybeRecordUsage((int)$image->id);

                return $this->json([
                    'success' => true,
                    'image' => $this->serializeImage($image),
                    'existing' => (bool)($result['existing'] ?? false),
                ]);
            }

            $detail = $result['error'] ?? $storage->getLastError() ?? 'Unable to save image';

            return $this->json(['success' => false, 'error' => $detail]);
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('Image upload exception: ' . $e->getMessage());

            return $this->json(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * Serve an image (original or variant) by id and optional variant name.
     * Example: /admin/images/serve/123?variant=thumb
     */
    public function serve(int $id): Response
    {
        $this->request->allowMethod(['get', 'head']);
        $variant = (string)$this->request->getQuery('variant');
        // Support legacy query-based sizing by mapping to a prebuilt variant.
        // If callers pass w/h/fit without an explicit variant, serve the "thumb".
        if ($variant === '') {
            $w = $this->request->getQuery('w');
            $h = $this->request->getQuery('h');
            $fit = $this->request->getQuery('fit');
            if ($w !== null || $h !== null || $fit !== null) {
                $variant = 'thumb';
            }
        }

        $storage = new ImageStorageService();
        $image = $storage->loadImageOrFail($id);
        [$path, $mime] = $storage->resolveImagePath($image, $variant);

        \Cake\Log\Log::debug("Serve image #{$id}, variant: '{$variant}', path: {$path}, mime: {$mime}");
        if (is_file($path)) {
            \Cake\Log\Log::debug("File exists at {$path}, size: " . filesize($path) . ' bytes');
        } else {
            \Cake\Log\Log::debug("File NOT found at {$path}");
        }

        $contents = is_file($path)
            ? (file_get_contents($path) ?: '')
            : '';
        if ($contents === '') {
            // Graceful fallback: return a 1x1 transparent PNG instead of 404 to avoid broken icons in editor/content.
            return $this->placeholderTransparentPng();
        }

        // Add cache-busting headers
        $response = $this->response
            ->withType($mime)
            ->withStringBody($contents)
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }

    /**
     * Management index view (list images with usage counts).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $images = $this->fetchTable('Images')->find()->orderByDesc('id')->limit(100)->all();
        $this->set(compact('images'));
        // Let Cake render the template normally (no explicit return of Response which caused blank output)
    }

    /**
     * AJAX endpoint to browse images for modal selection.
     * Optionally filter by tag.
     */
    public function browse(): Response
    {
        $this->request->allowMethod(['get']);
        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }

        $tag = $this->request->getQuery('tag');
        $limit = min((int)($this->request->getQuery('limit') ?? 50), 100);

        $images = $this->fetchTable('Images');
        $query = $images->find();

        // Filter by tag if provided
        if ($tag) {
            $query->innerJoinWith('ImageTags', function ($q) use ($tag) {
                return $q->where(['ImageTags.slug' => $tag]);
            });
        }

        $query->contain(['ImageTags'])->orderByDesc('Images.id')->limit($limit);

        $results = [];
        foreach ($query->all() as $image) {
            $results[] = [
                'id' => $image->id,
                'url' => '/images/serve/' . $image->id,
                'thumbnail_url' => '/images/serve/' . $image->id . '?' . http_build_query([
                    'w' => 300,
                    'h' => 300,
                    'fit' => 'cover',
                ]),
                'original_name' => $image->original_name,
                'tags' => array_map(fn($t) => $t->name, $image->image_tags ?? []),
            ];
        }

        return $this->json(['success' => true, 'images' => $results]);
    }

    /**
     * Display image upload form with basic manipulation preview.
     */
    public function uploadForm(): void
    {
        $this->request->allowMethod(['get']);
        // Template: templates/Admin/Images/upload.php
    }

    /**
     * Render bulk upload UI for multiple images with per-file tags/context.
     */
    public function bulkUploadForm(): void
    {
        $this->request->allowMethod(['get']);

        // Load entity data using service layer
        $teamSeasonService = new \App\Service\TeamSeasonService();
        $teamSeasonLabels = $teamSeasonService->getTeamSeasonsForSelect();

        $gameLabels = [];
        $gamesTable = $this->fetchTable('Games');
        foreach (
            $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->orderByDesc('Games.game_date')
            ->limit(200)
            ->all() as $g
        ) {
            $opp = $g->opponent->opponent_name ?? 'Opponent';
            $date = $g->game_date ? $g->game_date->format('M j, Y') : '';
            $label = $opp . ($date ? ' - ' . $date : '');
            $gameLabels[] = [
                'id' => $g->id,
                'label' => $label,
                'team_season_id' => $g->team_season_id,
            ];
        }

        $placeService = new \App\Service\PlaceService();
        $siteLabels = $placeService->getSitesForSelect();

        $opponentService = new \App\Service\OpponentService();
        $opponents = $opponentService->getOpponentsForSelect();

        $teamService = new \App\Service\TeamService();
        $teams = $teamService->getTeamsForSelect();

        $sportService = new \App\Service\SportService();
        $sports = $sportService->getSportsForSelect();

        $this->set(compact(
            'teamSeasonLabels',
            'gameLabels',
            'siteLabels',
            'opponents',
            'teams',
            'sports',
        ));

        $this->viewBuilder()->setTemplate('bulk_upload');
    }

    /**
     * Accept multiple uploads in one request with per-file tags/context.
     */
    public function bulkUpload(): Response
    {
        $this->request->allowMethod(['post']);

        // Prevent stray output from corrupting JSON
        if (ob_get_length()) {
            ob_clean();
        }

        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }

        try {
            $files = $this->request->getData('uploads') ?? [];
            if (!is_array($files) || $files === []) {
                return $this->json(['success' => false, 'error' => 'No files uploaded']);
            }

            $tagsInput = $this->request->getData('tags') ?? [];
            $contextInput = $this->request->getData('context') ?? [];

            // Collect common entity tags that will apply to all files
            $commonEntityTags = $this->buildCommonEntityTags($this->request->getData());

            $storage = new ImageStorageService();

            $results = [];
            foreach ($files as $index => $file) {
                // Normalize array-style uploads to PSR-7 UploadedFile
                if (!$file instanceof UploadedFileInterface) {
                    if (is_array($file) && !empty($file['tmp_name'])) {
                        $file = new \Laminas\Diactoros\UploadedFile(
                            $file['tmp_name'],
                            (int)($file['size'] ?? 0),
                            (int)($file['error'] ?? UPLOAD_ERR_OK),
                            $file['name'] ?? null,
                            $file['type'] ?? null,
                        );
                    } else {
                        $results[] = [
                            'index' => $index,
                            'name' => is_array($file) ? ($file['name'] ?? null) : null,
                            'success' => false,
                            'error' => 'Invalid upload payload',
                        ];
                        continue;
                    }
                }

                // Merge per-file tags with common entity tags
                $fileTags = $this->collectBulkTags($tagsInput, $contextInput, (string)$index);
                $allTags = array_merge($commonEntityTags, $fileTags);

                $result = $storage->upload($file, $allTags, []);

                $results[] = [
                    'index' => $index,
                    'name' => $file->getClientFilename(),
                    'success' => !empty($result['success']),
                    'existing' => $result['existing'] ?? false,
                    'error' => $result['error'] ?? null,
                    'image' => $result['image'] ?? null,
                ];
            }

            $anySuccess = (bool)count(array_filter($results, fn($r) => !empty($r['success'])));

            return $this->json([
                'success' => $anySuccess,
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('Bulk upload exception: ' . $e->getMessage());

            return $this->json(['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Build entity-based tags from form data (mirrors TaggingService::applyFromData logic).
     *
     * @param array<string,mixed> $data
     * @return array<int,array<string,string>>
     */
    private function buildCommonEntityTags(array $data): array
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
                'label' => fn($r) => $r->opponent_name ?? 'game',
            ],
            'site_select' => [
                'prefix' => 'site-',
                'table' => 'Places',
                'label' => fn($r) => $r->place_name ?? 'site',
            ],
            'opponent_select' => [
                'prefix' => 'opponent-',
                'table' => 'Opponents',
                'label' => fn($r) => $r->opponent_name ?? 'opponent',
            ],
            'team_select' => [
                'prefix' => 'team-',
                'table' => 'Teams',
                'label' => fn($r) => $r->team_name ?? 'team',
            ],
            'sport_select' => [
                'prefix' => 'sport-',
                'table' => 'Sports',
                'label' => fn($r) => $r->sport_name ?? 'sport',
            ],
        ];

        $personService = new \App\Service\PersonService();
        $teamSeasonService = new \App\Service\TeamSeasonService();
        $rosterService = new \App\Service\TeamSeasonRosterService();

        // Check if roster is being set (takes priority over teamseason)
        $hasRoster = !empty($data['roster_select']) && (int)$data['roster_select'] > 0;

        foreach ($map as $field => $meta) {
            // Skip teamseason_select if roster is being set
            if ($hasRoster && $field === 'teamseason_select') {
                continue;
            }

            // Skip other entity tags if roster is being set (only person allowed with roster)
            $skipFields = ['game_select', 'site_select', 'opponent_select', 'team_select', 'sport_select'];
            if ($hasRoster && in_array($field, $skipFields)) {
                continue;
            }

            if (!empty($data[$field])) {
                $id = (int)$data[$field];
                if ($id > 0) {
                    $slug = $meta['prefix'] . $id;

                    // Use service layer for entities with dedicated services
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
                        // Fallback to direct table lookup
                        $table = TableRegistry::getTableLocator()->get($meta['table']);
                        $row = $table->find()->select()->where(['id' => $id])->first();
                        $display = $row ? (string)$meta['label']($row) : '';
                    }

                    if ($display) {
                        $tagsToApply[] = [
                            'slug' => $slug,
                            'name' => $display,
                        ];
                    }
                }
            }
        }

        // Add common freeform tags
        if (!empty($data['common_tags'])) {
            $commonTags = $data['common_tags'];
            if (is_string($commonTags)) {
                $tags = array_values(array_filter(
                    array_map('trim', explode(',', $commonTags)),
                    fn($t) => $t !== ''
                ));
                foreach ($tags as $tag) {
                    $tagsToApply[] = $tag;
                }
            }
        }

        return $tagsToApply;
    }

    /**
     * Edit image metadata (status or original_name only for now).
     */
    public function edit(int $id): ?Response
    {
        $images = $this->fetchTable('Images');
        $image = $images->get($id, contain: ['ImageTags']);

        // Handle basic image updates (original_name, status) only
        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = (array)$this->request->getData();
            $patchData = [];
            if (array_key_exists('original_name', $data)) {
                $patchData['original_name'] = (string)$data['original_name'];
            }
            if (array_key_exists('status', $data)) {
                $patchData['status'] = (string)$data['status'];
            }
            if ($patchData) {
                $image = $images->patchEntity($image, $patchData);
                if ($images->save($image)) {
                    $this->Flash->success('Image updated');
                } else {
                    $this->Flash->error('Failed to update image');
                }
            }

            return $this->redirect(['action' => 'edit', $id]);
        }

        // Minimal data for edit template
        $currentTags = $image->image_tags ?? [];
        $this->set(compact('image', 'currentTags'));

        return null;
    }

    /**
     * Manage tags for an image.
     */
    public function tags(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $images = $this->fetchTable('Images');
        $image = $images->get($id, contain: ['ImageTags']);

        // Service-based select lists (persons and rosters loaded via AJAX)
        $teamService = new \App\Service\TeamService();
        $teams = $teamService->getTeamsForSelect();

        $teamSeasonService = new \App\Service\TeamSeasonService();
        $teamSeasons = $teamSeasonService->getTeamSeasonsForSelect();

        // Games with team_season_id for filtering and formatted labels
        $gameLabels = [];
        $gamesTable = $this->fetchTable('Games');
        foreach (
            $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams'], 'Opponents'])
            ->orderByDesc('Games.game_date')
            ->limit(200)
            ->all() as $g
        ) {
            $teamName = $g->team_season->team->team_name ?? 'Team';
            $oppName = $g->opponent->opponent_name ?? 'Opponent';
            $date = $g->game_date ? $g->game_date->format('Y-m-d') : '';
            $score = $g->pts_mur !== null && $g->pts_opp !== null ? " {$g->pts_mur}-{$g->pts_opp}" : '';
            $label = $teamName . ' vs ' . $oppName
                . ($date ? ' (' . $date . ')' : '') . $score;
            $gameLabels[] = [
                'id' => $g->id,
                'team_season_id' => $g->team_season_id,
                'label' => $label,
            ];
        }
        $games = $gameLabels;

        $placeService = new \App\Service\PlaceService();
        $sites = $placeService->getSitesForSelect();

        $opponentService = new \App\Service\OpponentService();
        $opponents = $opponentService->getOpponentsForSelect();

        $sportService = new \App\Service\SportService();
        $sports = $sportService->getSportsForSelect();

        if ($this->request->is(['post'])) {
            $data = $this->request->getData();
            $tagging = TaggingService::forImages();
            $tagging->applyFromData($id, $data);
            $this->Flash->success('Tags updated');

            return $this->redirect(['action' => 'tags', $id]);
        }

        // Tags for display and preselects
        $image = $images->get($id, contain: ['ImageTags']);
        $currentTags = $image->image_tags ?? [];

        $formattedTags = [];
        $freeformTags = [];
        foreach ($currentTags as $t) {
            $slug = (string)($t->slug ?? '');
            if (preg_match('/-[0-9]+$/', $slug)) {
                if (str_starts_with($slug, 'team_season_roster-')) {
                    $rid = (int)substr($slug, strlen('team_season_roster-'));
                    $display = (new \App\Service\TeamSeasonRosterService())->getRosterDisplayData($rid);
                    $t->name = $display['team_season_label'] ?? $t->name;
                }
                $formattedTags[] = $t;
            } else {
                $freeformTags[] = $t;
            }
        }
        $currentTags = array_merge($formattedTags, $freeformTags);
        $tagString = implode(', ', array_map(fn($t) => $t->name, $freeformTags));

        $selectedPersonId = null;
        $selectedPersonName = null;
        $selectedRosterId = null;
        foreach ($currentTags as $t) {
            $slug = (string)($t->slug ?? '');
            if (str_starts_with($slug, 'person-')) {
                $selectedPersonId = (int)substr($slug, strlen('person-'));
                $selectedPersonName = (new \App\Service\PersonService())->getDisplayLabel($selectedPersonId);
                break;
            }
        }
        foreach ($currentTags as $t) {
            $slug = (string)($t->slug ?? '');
            if (str_starts_with($slug, 'team_season_roster-')) {
                $selectedRosterId = (int)substr($slug, strlen('team_season_roster-'));
                break;
            }
        }

        $this->set(compact(
            'image',
            'currentTags',
            'teams',
            'teamSeasons',
            'games',
            'sites',
            'opponents',
            'sports',
            'tagString',
            'selectedPersonId',
            'selectedPersonName',
            'selectedRosterId'
        ));

        $this->viewBuilder()->setTemplate('tags');

        return null;
    }

    /**
     * View usage/references for an image.
     */
    public function usage(int $id): void
    {
        $images = $this->fetchTable('Images');
        $image = $images->get($id);

        $usages = $this->fetchTable('ImageUsages')
            ->find()
            ->where(['image_id' => $id])
            ->orderByDesc('created')
            ->all();

        $this->set(compact('image', 'usages'));
    }

    /**
     * Delete an image and all its references.
     */
    public function delete(int $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $images = $this->fetchTable('Images');
        $image = $images->get($id);

        // Delete associations and files
        $this->fetchTable('ImagesImageTags')->deleteAll(['image_id' => $id]);
        $this->fetchTable('ImageUsages')->deleteAll(['image_id' => $id]);
        TaggingService::forImages()->pruneOrphanedTags();

        // Delete physical files
        $this->deleteImageFiles($image);

        // Delete record
        if ($images->delete($image)) {
            $this->Flash->success('Image deleted');
        } else {
            $this->Flash->error('Could not delete image');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete images.
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);

        $ids = $this->request->getData('ids') ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }

        $images = $this->fetchTable('Images');
        $imagesToDelete = $images->find()
            ->whereInList('id', $ids)
            ->all();

        $deleted = 0;
        foreach ($imagesToDelete as $image) {
            // Delete associations
            $this->fetchTable('ImagesImageTags')->deleteAll(['image_id' => $image->id]);
            $this->fetchTable('ImageUsages')->deleteAll(['image_id' => $image->id]);

            // Delete files
            $this->deleteImageFiles($image);

            // Delete record
            if ($images->delete($image)) {
                $deleted++;
            }
        }

        TaggingService::forImages()->pruneOrphanedTags();

        $this->Flash->success("Deleted {$deleted} image(s)");

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Manipulate (crop, rotate, adjust) existing image.
     * GET: Display manipulation form with preview
     * POST: Apply manipulations and save
     *
     * @param int $id Image ID.
     * @return \Cake\Http\Response
     */
    public function manipulate(int $id): Response
    {
        $images = $this->fetchTable('Images');
        $image = $images->get($id);
        /** @var \App\Model\Entity\Image $image */

        \Cake\Log\Log::debug("Manipulate action called for image ID: {$id}");
        \Cake\Log\Log::debug('Image entity: ' . json_encode($image->toArray()));
        $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS;
        $originalPath = $baseDir . $image->storage_path;

        \Cake\Log\Log::debug("Original path: {$originalPath}");

        if ($this->request->is('post')) {
            $mode = (string)($this->request->getData('mode') ?? 'apply');

            // Apply manipulations
            // Verify file existence
            if (!is_file($originalPath)) {
                \Cake\Log\Log::error("Image file not found: {$originalPath}");
                $this->Flash->error('Original image file not found');

                return $this->redirect(['action' => 'index']);
            }

            // Get manipulations from request
            $manipulations = $this->collectManipulations();
            if (empty($manipulations)) {
                $this->Flash->warning('No manipulations specified');

                return $this->redirect(['action' => 'manipulate', $id]);
            }

            // Check for custom thumbnail crop
            $thumbCrop = $this->request->getData('thumb_crop');
            $hasThumbCrop = is_array($thumbCrop)
                && !empty($thumbCrop['width'])
                && !empty($thumbCrop['height']);

            // Process with manipulations by directly working with the file content
            $fileContent = file_get_contents($originalPath);
            $variantConfig = (array)Configure::read('Images.variants', [
                'thumb' => ['fit' => [150,150], 'format' => 'webp'],
                'medium' => ['maxWidth' => 800, 'format' => 'webp'],
                'webp' => ['format' => 'webp'],
            ]);

            // If custom thumbnail crop provided, override thumb variant config
            if ($hasThumbCrop) {
                $variantConfig['thumb'] = [
                    'crop' => [
                        'x' => (int)($thumbCrop['x'] ?? 0),
                        'y' => (int)($thumbCrop['y'] ?? 0),
                        'width' => (int)$thumbCrop['width'],
                        'height' => (int)$thumbCrop['height'],
                    ],
                    'fit' => [150, 150],
                    'format' => 'webp',
                ];
            }

            // Process with manipulations
            try {
                $processor = new ImageProcessor();
                // Call a direct manipulation method instead of process()
                $processed = $processor->manipulateExisting(
                    $fileContent,
                    $image->mime ?? 'image/jpeg',
                    $variantConfig,
                    $manipulations,
                );
            } catch (\Throwable $e) {
                \Cake\Log\Log::error('Image manipulation failed: ' . $e->getMessage());
                $this->Flash->error('Failed to apply manipulations: ' . $e->getMessage());

                return $this->redirect(['action' => 'manipulate', $id]);
            }

            // Save manipulated image (overwrite original)
            try {
                $originalData = $processed['original']['data'];

                // If the image library isn't available, ImageProcessor falls back
                // to returning the original bytes. Make that visible to the user
                // so it doesn't look like the save silently failed.
                $origWidth = (int)($processed['original']['width'] ?? 0);
                $origHeight = (int)($processed['original']['height'] ?? 0);
                if ($origWidth === 0 && $origHeight === 0) {
                    $this->Flash->error(
                        'Server-side image manipulation is unavailable (missing PHP GD/Imagick). '
                        . 'Install `php-gd` (recommended) or `php-imagick`, then restart PHP/Apache.'
                    );

                    return $this->redirect(['action' => 'manipulate', $id]);
                }

                // Save-as-copy mode: keep the original image untouched and persist a new image record.
                if ($mode === 'copy') {
                    $mime = (string)($processed['original']['mime'] ?? $image->mime ?? 'image/jpeg');
                    $ext = (string)($processed['original']['ext'] ?? $image->ext ?? 'jpg');
                    $hash = hash('sha256', $originalData);
                    $copyName = $image->original_name
                        ? $image->original_name . ' (edited)'
                        : $image->filename . ' (edited)';

                    $storage = new ImageStorageService();
                    $new = $storage->persistNewImage($images, $processed, $hash, $mime, $ext, $copyName);
                    if (!$new) {
                        $detail = $storage->getLastError() ?: 'Unable to save image copy';
                        $this->Flash->error($detail);

                        return $this->redirect(['action' => 'manipulate', $id]);
                    }

                    $this->Flash->success('Saved manipulated image as a new copy');

                    return $this->redirect(['action' => 'edit', $new->id]);
                }

                if (file_put_contents($originalPath, $originalData) === false) {
                    throw new \RuntimeException('Failed to write image file');
                }

                // Regenerate variants: prefer existing filenames; generate if missing; update DB metadata.
                $existingVariants = $image->variants;
                if (is_string($existingVariants)) {
                    $existingVariants = json_decode($existingVariants, true);
                }
                $dir = dirname($originalPath);
                $baseName = pathinfo((string)$image->filename, PATHINFO_FILENAME);
                $newVariantsMeta = [];
                foreach ((array)$processed['variants'] as $name => $meta) {
                    $existingFile = null;
                    if (is_array($existingVariants) && isset($existingVariants[$name]['file'])) {
                        $existingFile = (string)$existingVariants[$name]['file'];
                    }
                    $ext = (string)($meta['ext'] ?? $image->ext ?? 'jpg');
                    $targetFile = $existingFile ?: ($baseName . '-' . $name . '.' . $ext);
                    $variantPath = $dir . DS . $targetFile;
                    if (file_put_contents($variantPath, (string)$meta['data']) === false) {
                        throw new \RuntimeException("Failed to write variant {$name}");
                    }
                    $newVariantsMeta[$name] = [
                        'file' => $targetFile,
                        'width' => (int)($meta['width'] ?? null),
                        'height' => (int)($meta['height'] ?? null),
                        'mime' => (string)($meta['mime'] ?? ''),
                    ];
                }

                // Update DB metadata so edit/serve endpoints reflect changes and cache-busting is possible.
                $images->patchEntity($image, [
                    'byte_size' => strlen($originalData),
                    'hash' => hash('sha256', $originalData),
                    'width' => (int)($processed['original']['width'] ?? $image->width),
                    'height' => (int)($processed['original']['height'] ?? $image->height),
                    'modified' => date('Y-m-d H:i:s'),
                    'variants' => json_encode($newVariantsMeta),
                ], ['validate' => false]);
                $images->saveOrFail($image);

                $this->Flash->success('Image manipulations applied successfully');

                return $this->redirect(['action' => 'edit', $id]);
            } catch (\Throwable $e) {
                \Cake\Log\Log::error('Failed to save manipulated image: ' . $e->getMessage());
                $this->Flash->error('Failed to save manipulated image: ' . $e->getMessage());

                return $this->redirect(['action' => 'manipulate', $id]);
            }
        }

        // GET: Display form with preview
        $this->set(compact('image'));

        return $this->render();
    }

    /**
     * Crop the thumbnail variant with custom crop coordinates.
     * Only regenerates the thumb variant without touching the original or other variants.
     * GET: Display crop editor form
     * POST: Apply crop and regenerate thumb variant
     */
    public function cropThumb(int $id): Response
    {
        $images = $this->fetchTable('Images');
        $image = $images->get($id);
        /** @var \App\Model\Entity\Image $image */

        $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS;
        $originalPath = $baseDir . $image->storage_path;

        if ($this->request->is('post')) {
            // Verify file exists
            if (!is_file($originalPath)) {
                \Cake\Log\Log::error("Image file not found: {$originalPath}");
                $this->Flash->error('Original image file not found');

                return $this->redirect(['action' => 'edit', $id]);
            }

            // Get crop coordinates
            $crop = $this->request->getData('crop');
            if (!is_array($crop) || empty($crop['width']) || empty($crop['height'])) {
                $this->Flash->warning('No valid crop area specified');

                return $this->redirect(['action' => 'cropThumb', $id]);
            }

            $cropX = (int)($crop['x'] ?? 0);
            $cropY = (int)($crop['y'] ?? 0);
            $cropWidth = (int)($crop['width'] ?? 0);
            $cropHeight = (int)($crop['height'] ?? 0);

            if ($cropWidth <= 0 || $cropHeight <= 0) {
                $this->Flash->error('Invalid crop dimensions');

                return $this->redirect(['action' => 'cropThumb', $id]);
            }

            try {
                // Read original file
                $fileContent = file_get_contents($originalPath);

                // Build variant config with custom crop for thumb only
                $variantConfig = [
                    'thumb' => [
                        'crop' => [
                            'x' => $cropX,
                            'y' => $cropY,
                            'width' => $cropWidth,
                            'height' => $cropHeight,
                        ],
                        'fit' => [150, 150],
                        'format' => 'webp',
                    ],
                ];

                // Generate new thumb variant
                $processor = new ImageProcessor();
                $processed = $processor->manipulateExisting(
                    $fileContent,
                    $image->mime ?? 'image/jpeg',
                    $variantConfig,
                    [],
                );

                if (!isset($processed['variants']['thumb'])) {
                    throw new \RuntimeException('Thumb variant not generated');
                }

                // Get current variants from DB
                $existingVariants = $image->variants;
                if (is_string($existingVariants)) {
                    $existingVariants = json_decode($existingVariants, true);
                }
                $existingVariants = is_array($existingVariants) ? $existingVariants : [];

                // Write new thumb file to disk
                $dir = dirname($originalPath);
                $meta = $processed['variants']['thumb'];

                // Reuse existing thumb filename or create new one
                $existingFile = $existingVariants['thumb']['file'] ?? null;
                $baseName = pathinfo((string)$image->filename, PATHINFO_FILENAME);
                $ext = (string)($meta['ext'] ?? 'webp');
                $targetFile = $existingFile ?: ($baseName . '-thumb.' . $ext);
                $variantPath = $dir . DS . $targetFile;

                $bytesWritten = file_put_contents($variantPath, (string)$meta['data']);
                if ($bytesWritten === false) {
                    throw new \RuntimeException('Failed to write thumb variant file');
                }

                \Cake\Log\Log::debug("Wrote {$bytesWritten} bytes to thumb variant: {$variantPath}");

                // Update variants JSON with new thumb metadata
                $existingVariants['thumb'] = [
                    'file' => $targetFile,
                    'width' => (int)($meta['width'] ?? 150),
                    'height' => (int)($meta['height'] ?? 150),
                    'mime' => (string)($meta['mime'] ?? 'image/webp'),
                ];

                // Update DB: Change hash to invalidate browser cache
                $thumbHash = hash('sha256', (string)$meta['data']);
                $variantsJson = json_encode($existingVariants);

                \Cake\Log\Log::debug("Before save - Variants JSON: {$variantsJson}");

                $image = $images->patchEntity($image, [
                    'variants' => $variantsJson,
                    'hash' => $thumbHash,
                    'modified' => new \Cake\I18n\DateTime('now'),
                ], ['validate' => false]);

                $images->saveOrFail($image);

                // Verify what was actually saved
                $reloaded = $images->get($id);
                \Cake\Log\Log::debug("After save - Image hash: {$reloaded->hash}");
                \Cake\Log\Log::debug("After save - Image modified: {$reloaded->modified}");
                \Cake\Log\Log::debug('After save - Variants JSON from DB: ' . ($reloaded->variants ?? 'NULL'));

                \Cake\Log\Log::debug("Updated thumb variant for image #{$id}, new hash: {$thumbHash}");

                $this->Flash->success('Thumbnail crop updated successfully');

                return $this->redirect(['action' => 'edit', $id]);
            } catch (\Throwable $e) {
                \Cake\Log\Log::error('Failed to crop thumbnail: ' . $e->getMessage());
                $this->Flash->error('Failed to crop thumbnail: ' . $e->getMessage());

                return $this->redirect(['action' => 'cropThumb', $id]);
            }
        }

        // GET: Display crop editor form
        $this->set(compact('image'));

        return $this->render();
    }

    /**
     * Return roster entries for a given person as JSON (used by AJAX in tag UI).
     * Query param: person_id
     */
    public function rosters(): Response
    {
        $this->request->allowMethod(['get']);
        $personId = (int)$this->request->getQuery('person_id');
        if ($personId <= 0) {
            return $this->json(['success' => true, 'rosters' => []]);
        }

        $rostersTable = $this->fetchTable('TeamSeasonRosters');
        $rows = $rostersTable->find()
            ->contain(['Persons', 'TeamSeasons' => ['Teams', 'Seasons']])
            ->where(['TeamSeasonRosters.person_id' => $personId])
            ->all();

        $out = [];
        foreach ($rows as $r) {
            $pname = trim(($r->person->first ?? '') . ' ' . ($r->person->last ?? '')) ?: '#' . $r->person_id;

            $teamSeason = $r->team_season ?? null;
            $seasonLabel = '';
            if ($teamSeason) {
                $teamName = $teamSeason->team->team_name ?? 'Team';
                if (!empty($teamSeason->season)) {
                    $start = $teamSeason->season->start ?? null;
                    $end = $teamSeason->season->end ?? null;
                    if ($start && $end && $start != $end) {
                        $seasonLabel = " ({$start}-{$end})";
                    } elseif ($start) {
                        $seasonLabel = " ({$start})";
                    }
                }
                $label = $pname . ' — ' . $teamName . $seasonLabel;
            } else {
                $label = $pname;
            }
            $out[] = ['id' => $r->id, 'label' => $label];
        }

        return $this->json(['success' => true, 'rosters' => $out]);
    }

    /**
     * Return person search results for a given query as JSON (used by AJAX in tag UI).
     * Query param: q
     */
    public function persons(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));
        if ($q === '') {
            return $this->json(['success' => true, 'persons' => []]);
        }

        $personsTable = $this->fetchTable('Persons');
        $like = '%' . str_replace('%', '\\%', $q) . '%';
        $rows = $personsTable->find()
            ->select(['id', 'first', 'last', 'full', 'display'])
            ->where([
                'OR' => [
                    ['first LIKE' => $like],
                    ['last LIKE' => $like],
                    ['full LIKE' => $like],
                    ['display LIKE' => $like],
                ],
            ])
            ->orderBy(['last' => 'ASC', 'first' => 'ASC'])
            ->limit(25)
            ->all();

        $out = [];
        $rostersTable = $this->fetchTable('TeamSeasonRosters');
        foreach ($rows as $r) {
            $base = trim((string)($r->display ?? ''))
                ?: trim((string)($r->full ?? '')
                ?: (trim(($r->first ?? '') . ' ' . ($r->last ?? ''))));

            // Try to find a recent roster entry to disambiguate persons with identical names
            $latestRoster = $rostersTable->find()
                ->select(['TeamSeasonRosters.id', 'TeamSeasonRosters.team_season_id'])
                ->where(['TeamSeasonRosters.person_id' => $r->id])
                ->contain(['TeamSeasons' => ['Teams', 'Seasons']])
                ->order(['Seasons.start' => 'DESC', 'TeamSeasonRosters.id' => 'DESC'])
                ->limit(1)
                ->first();

            $extra = '';
            if ($latestRoster) {
                $ts = $latestRoster->team_season ?? null;
                if ($ts) {
                    $teamName = $ts->team->team_name ?? null;
                    $season = $ts->season ?? null;
                    if ($teamName) {
                        $seasonLabel = '';
                        if ($season) {
                            $start = $season->start ?? null;
                            $end = $season->end ?? null;
                            if ($start && $end && $start != $end) {
                                $seasonLabel = " {$start}-{$end}";
                            } elseif ($start) {
                                $seasonLabel = " {$start}";
                            }
                        }
                        $extra = trim($teamName . $seasonLabel);
                    }
                }
            }

            $label = $base . ($extra ? ' — ' . $extra : '');
            $out[] = ['id' => $r->id, 'label' => $label];
        }

        return $this->json(['success' => true, 'persons' => $out]);
    }

    /**
     * Delete physical image files.
     */
    private function deleteImageFiles(\App\Model\Entity\Image $image): void
    {
        $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS;
        if (!$image->storage_path) {
            return;
        }

        $originalPath = $baseDir . $image->storage_path;
        if (is_file($originalPath)) {
            unlink($originalPath);
        }

        // Delete variants
        $variants = $image->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }
        if (is_array($variants)) {
            $dir = dirname($originalPath);
            foreach ($variants as $variantMeta) {
                $file = is_array($variantMeta) ? ($variantMeta['file'] ?? null) : null;
                if (!$file) {
                    continue;
                }
                $variantPath = $dir . DS . $file;
                if (is_file($variantPath)) {
                    unlink($variantPath);
                }
            }
        }
    }

    /**
     * Record usage if context passed.
     */
    private function maybeRecordUsage(int $imageId): void
    {
        [$model, $foreign, $field] = [
            $this->request->getData('model') ?? $this->request->getQuery('model'),
            $this->request->getData('foreign_key') ?? $this->request->getQuery('foreign_key'),
            $this->request->getData('field') ?? $this->request->getQuery('field'),
        ];
        $context = $this->request->getData('context') ?? $this->request->getQuery('context');
        if (!$model || !$foreign || !$field) {
            return;
        }
        $usages = TableRegistry::getTableLocator()->get('ImageUsages');
        $exists = $usages->find()->where([
            'image_id' => $imageId,
            'model' => $model,
            'foreign_key' => (int)$foreign,
            'field' => (string)$field,
            'context' => $context ? (string)$context : null,
        ])->first();
        if ($exists) {
            return;
        }
        $usage = $usages->newEntity([
            'image_id' => $imageId,
            'model' => (string)$model,
            'foreign_key' => (int)$foreign,
            'field' => (string)$field,
            'context' => $context ? (string)$context : null,
        ]);
        $usages->save($usage);
    }

    /**
     * Determine if the current request is authenticated.
     *
     * If the Authentication component is not available we assume this is
     * controlled elsewhere and return true to avoid blocking internal
     * operations during CLI/test runs.
     *
     * @return bool
     */
    private function isAuthenticated(): bool
    {
        // Prefer the Authentication plugin's identity when available (real app use).
        if ($this->components()->has('Authentication')) {
            try {
                $identity = $this->Authentication->getIdentity();
            } catch (\Throwable $e) {
                $identity = null;
            }
            if ($identity !== null) {
                // IdentityInterface in CakePHP provides getIdentifier(). Use it when present.
                if (method_exists($identity, 'getIdentifier')) {
                    $id = $identity->getIdentifier();
                    if (!empty($id)) {
                        return true;
                    }
                }

                // Fallback: try to inspect original data for an 'id' field (entities, arrays, etc).
                if (method_exists($identity, 'getOriginalData')) {
                    $orig = $identity->getOriginalData();
                    if (is_array($orig) && !empty($orig['id'])) {
                        return true;
                    }
                    if (is_object($orig)) {
                        if (property_exists($orig, 'id') && !empty($orig->id)) {
                            return true;
                        }
                        if (method_exists($orig, 'getId') && !empty($orig->getId())) {
                            return true;
                        }
                    }
                }

                return false;
            }
        }

        // Rely exclusively on legacy Auth session by default (test harness uses AuthTestTrait to inject).
        $legacy = $this->getRequest()->getSession()->read('Auth');

        return is_array($legacy) && !empty($legacy['id']);
    }

    /**
     * Build tags for a bulk-uploaded file by index.
     *
     * @param array<string|int,mixed>|string|null $tagsInput
     * @param array<string|int,mixed>|string|null $contextInput
     * @param string $index
     * @return array<int,string|array>
     */
    private function collectBulkTags(
        array|string|null $tagsInput,
        array|string|null $contextInput,
        string $index,
    ): array {
        $tags = [];

        $rawTags = null;
        if (array_key_exists($index, $tagsInput)) {
            $rawTags = $tagsInput[$index];
        } elseif (array_key_exists((int)$index, $tagsInput)) {
            $rawTags = $tagsInput[(int)$index];
        } elseif (is_string($tagsInput)) {
            $rawTags = $tagsInput;
        }

        if (is_string($rawTags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags)), fn($t) => $t !== ''));
        } elseif (is_array($rawTags)) {
            $tags = array_values(array_filter(array_map(fn($t) => trim((string)$t), $rawTags), fn($t) => $t !== ''));
        }

        $contextValue = null;
        if (array_key_exists($index, $contextInput)) {
            $contextValue = $contextInput[$index];
        } elseif (array_key_exists((int)$index, $contextInput)) {
            $contextValue = $contextInput[(int)$index];
        } elseif (is_string($contextInput)) {
            $contextValue = $contextInput;
        }

        if (is_string($contextValue)) {
            $contextValue = trim($contextValue);
            if ($contextValue !== '') {
                $tags[] = [
                    'slug' => 'context-' . Text::slug($contextValue, '-'),
                    'name' => $contextValue,
                ];
            }
        }

        return $tags;
    }

    /**
     * Extract uploaded file from request and normalize to PSR-7 UploadedFile.
     *
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    private function extractUploaded()
    {
        $file = $this->request->getData('upload') ?? $this->request->getData('file');
        if (is_array($file) && isset($file['tmp_name'])) {
            $file = new \Laminas\Diactoros\UploadedFile(
                $file['tmp_name'],
                (int)($file['size'] ?? 0),
                (int)($file['error'] ?? UPLOAD_ERR_OK),
                (string)($file['name'] ?? ''),
                (string)($file['type'] ?? '')
            );
        }

        return $file;
    }

    /**
     * Collect image manipulations from request (crop, rotate, brightness, contrast).
     */
    private function collectManipulations(): array
    {
        $manipulations = [];

        // Crop coordinates
        $crop = $this->request->getData('crop');
        if (is_array($crop) && isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
            $manipulations['crop'] = [
                'x' => (int)$crop['x'],
                'y' => (int)$crop['y'],
                'width' => (int)$crop['width'],
                'height' => (int)$crop['height'],
            ];
        }

        // Rotation angle (allow negatives for straightening)
        $rotate = $this->request->getData('rotate');
        if ($rotate !== null && $rotate !== '') {
            $angle = (int)$rotate;
            // Accept a reasonable range including negatives; server will normalize
            if ($angle >= -180 && $angle <= 180) {
                $manipulations['rotate'] = $angle;
            }
        }

        // Brightness adjustment
        $brightness = $this->request->getData('brightness');
        if ($brightness !== null && $brightness !== '') {
            $value = (int)$brightness;
            if ($value >= -100 && $value <= 100) {
                $manipulations['brightness'] = $value;
            }
        }

        // Contrast adjustment
        $contrast = $this->request->getData('contrast');
        if ($contrast !== null && $contrast !== '') {
            $value = (int)$contrast;
            if ($value >= -100 && $value <= 100) {
                $manipulations['contrast'] = $value;
            }
        }

        // Blur
        $blur = $this->request->getData('blur');
        if ($blur !== null && $blur !== '') {
            $value = (int)$blur;
            if ($value > 0 && $value <= 100) {
                $manipulations['blur'] = $value;
            }
        }

        return $manipulations;
    }

    /**
     * Return a 1x1 transparent PNG response (base64 inline data) used as a safe placeholder.
     */
    private function placeholderTransparentPng(): Response
    {
        // Single pixel transparent PNG
        $data = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMA' .
            'AQAABQABDQottAAAAABJRU5ErkJggg=='
        );

        return $this->response
            ->withType('image/png')
            ->withHeader('Cache-Control', 'public, max-age=60')
            ->withStringBody($data ?: '');
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
        $publicServeUrl = '/images/serve/' . $image->id;
        $directUrl = '/img/storage/' .
            ltrim($image->storage_path ?? ($image->storage_subdir . '/' . $image->filename), '/');

        return [
            'id' => $image->id,
            'filename' => $image->filename,
            'url' => $baseUrl,
            'variants' => $variants,
            'direct_url' => $directUrl,
            'public_url' => $publicServeUrl,
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
