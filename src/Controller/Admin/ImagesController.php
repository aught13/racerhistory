<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameService;
use App\Service\ImageBrowseService;
use App\Service\ImageEditService;
use App\Service\ImageStorageService;
use App\Service\PersonService;
use App\Service\TaggingService;
use App\Service\TeamSeasonRosterService;
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
        $this->getRequest()->allowMethod(['post']);
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
            $tags = $tagging->parseTagsFromRequest($this->getRequest());
            $manipulations = $this->collectManipulations();

            $storage = new ImageStorageService(null, $tagging);
            $result = $storage->upload($file, $tags, $manipulations);

            if (!empty($result['success'])) {
                /** @var \App\Model\Entity\Image $image */
                $image = $result['image'];

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
        $request = $this->getRequest();
        $request->allowMethod(['get', 'head']);
        $variant = (string)$request->getQuery('variant');
        // Support legacy query-based sizing by mapping to a prebuilt variant.
        // If callers pass w/h/fit without an explicit variant, serve the "thumb".
        if ($variant === '') {
            $w = $request->getQuery('w');
            $h = $request->getQuery('h');
            $fit = $request->getQuery('fit');
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
        $response = $this->getResponse()
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
        $this->getRequest()->allowMethod(['get']);
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
        $request = $this->getRequest();
        $request->allowMethod(['get']);
        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }

        $tag = $request->getQuery('tag');
        $limit = $request->getQuery('limit');

        $payload = (new ImageBrowseService())->browse(
            is_string($tag) ? $tag : null,
            $limit !== null ? (int)$limit : null,
        );

        return $this->json($payload);
    }

    /**
     * Display image upload form with basic manipulation preview.
     */
    public function uploadForm(): void
    {
        $this->getRequest()->allowMethod(['get']);
        // Template: templates/Admin/Images/upload.php
    }

    /**
     * Render bulk upload UI for multiple images with per-file tags/context.
     */
    public function bulkUploadForm(): void
    {
        $this->getRequest()->allowMethod(['get']);

        // Load entity data using service layer
        $teamSeasonService = new \App\Service\TeamSeasonService();
        $teamSeasonLabels = $teamSeasonService->getTeamSeasonsForSelect();

        $gameLabels = (new GameService())->getRecentGamesForSelect(200);

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
        $request = $this->getRequest();
        $request->allowMethod(['post']);

        // Prevent stray output from corrupting JSON
        if (ob_get_length()) {
            ob_clean();
        }

        if (!$this->isAuthenticated()) {
            return $this->json(['success' => false, 'error' => 'Unauthenticated']);
        }

        try {
            $files = $request->getData('uploads') ?? [];
            if (!is_array($files) || $files === []) {
                return $this->json(['success' => false, 'error' => 'No files uploaded']);
            }

            $tagsInput = $request->getData('tags') ?? [];
            $contextInput = $request->getData('context') ?? [];

            // Collect common entity tags that will apply to all files
            $commonEntityTags = $this->buildCommonEntityTags((array)$request->getData());

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
        $request = $this->getRequest();
        if ($request->is(['post', 'put', 'patch'])) {
            $data = (array)$request->getData();
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
        $request = $this->getRequest();
        $request->allowMethod(['get', 'post']);

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

        if ($request->is(['post'])) {
            $data = $request->getData();
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
     * Delete an image and all its references.
     */
    public function delete(int $id): Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $images = $this->fetchTable('Images');
        $image = $images->get($id);

        // Delete associations and files
        $this->fetchTable('ImagesImageTags')->deleteAll(['image_id' => $id]);
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
        $request = $this->getRequest();
        $request->allowMethod(['post']);

        $ids = $request->getData('ids') ?? [];
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

        $request = $this->getRequest();

        \Cake\Log\Log::debug("Manipulate action called for image ID: {$id}");
        \Cake\Log\Log::debug('Image entity: ' . json_encode($image->toArray()));
        $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS;
        $originalPath = $baseDir . $image->storage_path;

        \Cake\Log\Log::debug("Original path: {$originalPath}");

        if ($request->is('post')) {
            $mode = (string)($request->getData('mode') ?? 'apply');

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
            $thumbCrop = $request->getData('thumb_crop');
            try {
                $result = (new ImageEditService())->manipulateImage(
                    $images,
                    $image,
                    $manipulations,
                    $mode,
                    is_array($thumbCrop) ? $thumbCrop : null,
                );

                if (empty($result['success']) && ($result['status'] ?? null) === 'missing_library') {
                    $this->Flash->error(
                        'Server-side image manipulation is unavailable (missing PHP GD/Imagick). '
                        . 'Install `php-gd` (recommended) or `php-imagick`, then restart PHP/Apache.'
                    );

                    return $this->redirect(['action' => 'manipulate', $id]);
                }

                if (!empty($result['success']) && ($result['status'] ?? null) === 'copied') {
                    $this->Flash->success('Saved manipulated image as a new copy');

                    return $this->redirect(['action' => 'edit', (int)$result['new_image_id']]);
                }

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

        $request = $this->getRequest();

        $baseDir = WWW_ROOT . 'img' . DS . 'storage' . DS;
        $originalPath = $baseDir . $image->storage_path;

        if ($request->is('post')) {
            // Verify file exists
            if (!is_file($originalPath)) {
                \Cake\Log\Log::error("Image file not found: {$originalPath}");
                $this->Flash->error('Original image file not found');

                return $this->redirect(['action' => 'edit', $id]);
            }

            // Get crop coordinates
            $crop = $request->getData('crop');
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
                (new ImageEditService())->cropThumbVariant($images, $image, [
                    'x' => $cropX,
                    'y' => $cropY,
                    'width' => $cropWidth,
                    'height' => $cropHeight,
                ]);

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
        $request = $this->getRequest();
        $request->allowMethod(['get']);
        $personId = (int)$request->getQuery('person_id');
        if ($personId <= 0) {
            return $this->json(['success' => true, 'rosters' => []]);
        }

        $service = new TeamSeasonRosterService();
        $out = $service->getRostersForPersonLookup($personId, 200);

        return $this->json(['success' => true, 'rosters' => $out]);
    }

    /**
     * Return person search results for a given query as JSON (used by AJAX in tag UI).
     * Query param: q
     */
    public function persons(): Response
    {
        $request = $this->getRequest();
        $request->allowMethod(['get']);
        $q = trim((string)$request->getQuery('q'));
        if ($q === '') {
            return $this->json(['success' => true, 'persons' => []]);
        }

        $service = new PersonService();
        $out = $service->searchPersonsForImageTagging($q, 25);

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
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags)), fn($t) => $t !== ''));
        } elseif (is_array($rawTags)) {
            $tags = array_values(array_filter(array_map(fn($t) => trim((string)$t), $rawTags), fn($t) => $t !== ''));
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
        $request = $this->getRequest();
        $file = $request->getData('upload') ?? $request->getData('file');
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

        $request = $this->getRequest();

        // Crop coordinates
        $crop = $request->getData('crop');
        if (is_array($crop) && isset($crop['x'], $crop['y'], $crop['width'], $crop['height'])) {
            $manipulations['crop'] = [
                'x' => (int)$crop['x'],
                'y' => (int)$crop['y'],
                'width' => (int)$crop['width'],
                'height' => (int)$crop['height'],
            ];
        }

        // Rotation angle (allow negatives for straightening)
        $rotate = $request->getData('rotate');
        if ($rotate !== null && $rotate !== '') {
            $angle = (int)$rotate;
            // Accept a reasonable range including negatives; server will normalize
            if ($angle >= -180 && $angle <= 180) {
                $manipulations['rotate'] = $angle;
            }
        }

        // Brightness adjustment
        $brightness = $request->getData('brightness');
        if ($brightness !== null && $brightness !== '') {
            $value = (int)$brightness;
            if ($value >= -100 && $value <= 100) {
                $manipulations['brightness'] = $value;
            }
        }

        // Contrast adjustment
        $contrast = $request->getData('contrast');
        if ($contrast !== null && $contrast !== '') {
            $value = (int)$contrast;
            if ($value >= -100 && $value <= 100) {
                $manipulations['contrast'] = $value;
            }
        }

        // Blur
        $blur = $request->getData('blur');
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

        return $this->getResponse()
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
        return $this->getResponse()->withType('application/json')->withStringBody(json_encode($payload));
    }
}
