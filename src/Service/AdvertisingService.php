<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Advertisement;
use App\Model\Entity\BlogPost;
use App\Model\Entity\Game;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use App\Model\Table\AdvertisementsTable;
use App\Model\Table\AdvertisingSettingsTable;
use App\Model\Table\GamesTable;
use App\Model\Table\TeamSeasonsTable;
use Cake\ORM\TableRegistry;

/**
 * Owns advertising campaigns, provider settings, and page targeting context.
 */
class AdvertisingService
{
    /**
     * @var array<string,string>
     */
    private const PLACEMENT_OPTIONS = [
        'below_nav' => 'Below Navigation',
        'below_content' => 'Below Content',
        'footer' => 'Footer',
        'homepage_mid' => 'Homepage Mid-Page',
        'news_after_first' => 'News After First Post',
        'news_every_fifth' => 'News Every Fifth Post',
        'news_sidebar_1' => 'News Sidebar 1',
        'news_sidebar_2' => 'News Sidebar 2',
    ];

    private AdvertisementsTable $advertisementsTable;

    private AdvertisingSettingsTable $settingsTable;

    private GamesTable $gamesTable;

    private TeamSeasonsTable $teamSeasonsTable;

    private PublicSportScopeService $publicSportScopeService;

    /**
     * @param \App\Model\Table\AdvertisementsTable|null $advertisementsTable
     * @param \App\Model\Table\AdvertisingSettingsTable|null $settingsTable
     * @param \App\Model\Table\GamesTable|null $gamesTable
     * @param \App\Model\Table\TeamSeasonsTable|null $teamSeasonsTable
     * @param \App\Service\PublicSportScopeService|null $publicSportScopeService
     */
    public function __construct(
        ?AdvertisementsTable $advertisementsTable = null,
        ?AdvertisingSettingsTable $settingsTable = null,
        ?GamesTable $gamesTable = null,
        ?TeamSeasonsTable $teamSeasonsTable = null,
        ?PublicSportScopeService $publicSportScopeService = null,
    ) {
        /** @var \App\Model\Table\AdvertisementsTable $resolvedAdvertisementsTable */
        $resolvedAdvertisementsTable = $advertisementsTable
            ?? TableRegistry::getTableLocator()->get('Advertisements');
        /** @var \App\Model\Table\AdvertisingSettingsTable $resolvedSettingsTable */
        $resolvedSettingsTable = $settingsTable
            ?? TableRegistry::getTableLocator()->get('AdvertisingSettings');
        /** @var \App\Model\Table\GamesTable $resolvedGamesTable */
        $resolvedGamesTable = $gamesTable ?? TableRegistry::getTableLocator()->get('Games');
        /** @var \App\Model\Table\TeamSeasonsTable $resolvedTeamSeasonsTable */
        $resolvedTeamSeasonsTable = $teamSeasonsTable
            ?? TableRegistry::getTableLocator()->get('TeamSeasons');

        $this->advertisementsTable = $resolvedAdvertisementsTable;
        $this->settingsTable = $resolvedSettingsTable;
        $this->gamesTable = $resolvedGamesTable;
        $this->teamSeasonsTable = $resolvedTeamSeasonsTable;
        $this->publicSportScopeService = $publicSportScopeService ?? new PublicSportScopeService();
    }

    /**
     * @return array<string,string>
     */
    public function getPlacementOptions(): array
    {
        return self::PLACEMENT_OPTIONS;
    }

    /**
     * @return array<string,string>
     */
    public function getAvailableSports(): array
    {
        return $this->publicSportScopeService->getAvailableSportOptions();
    }

    /**
     * @return array<int,array{label:string,sportKey:string}>
     */
    public function getTeamOptions(): array
    {
        return $this->publicSportScopeService->getTeamOptions();
    }

    /**
     * Return campaigns ordered by placement and display order.
     *
     * @return array<int,\App\Model\Entity\Advertisement>
     */
    public function getAdvertisements(): array
    {
        $rows = $this->advertisementsTable
            ->find()
            ->orderByAsc('placement')
            ->orderByAsc('display_order')
            ->orderByAsc('id')
            ->all()
            ->toArray();
        $advertisements = [];
        foreach ($rows as $row) {
            if ($row instanceof Advertisement) {
                $advertisements[] = $row;
            }
        }

        return $advertisements;
    }

    /**
     * Find one campaign by id.
     *
     * @param int $id Campaign id.
     */
    public function getAdvertisement(int $id): ?Advertisement
    {
        if ($id < 1) {
            return null;
        }

        $advertisement = $this->advertisementsTable
            ->find()
            ->where(['id' => $id])
            ->first();

        return $advertisement instanceof Advertisement ? $advertisement : null;
    }

    /**
     * Build a new campaign with safe initial values.
     */
    public function newAdvertisement(): Advertisement
    {
        return $this->advertisementsTable->newEntity([
            'title' => '',
            'placement' => 'below_nav',
            'target_type' => 'site',
            'sport_key' => null,
            'team_id' => null,
            'is_active' => false,
            'html' => '',
            'mode' => 'custom',
            'sizes_desktop' => '',
            'sizes_mobile' => '',
            'gpt_unit_path' => '',
            'display_order' => 0,
        ]);
    }

    /**
     * Normalize and patch the submitted campaign form data.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Existing or new campaign.
     * @param array<string,mixed> $data Submitted form data.
     */
    public function patchAdvertisement(Advertisement $advertisement, array $data): Advertisement
    {
        $fields = [
            'title',
            'placement',
            'target_type',
            'sport_key',
            'html',
            'mode',
            'sizes_desktop',
            'sizes_mobile',
            'gpt_unit_path',
        ];
        $normalized = [];
        $invalidFields = [];

        foreach ($fields as $field) {
            $rawValue = $data[$field] ?? $advertisement->get($field);
            if (
                $rawValue === null
                && in_array($field, ['sport_key', 'sizes_desktop', 'sizes_mobile', 'gpt_unit_path'], true)
            ) {
                $normalized[$field] = null;
                continue;
            }
            if (!is_scalar($rawValue)) {
                $normalized[$field] = '';
                $invalidFields[$field] = 'Enter a single text value.';
                continue;
            }

            $normalized[$field] = trim((string)$rawValue);
        }

        $normalized['placement'] = strtolower((string)$normalized['placement']);
        $normalized['target_type'] = strtolower((string)$normalized['target_type']);
        $normalized['mode'] = strtolower((string)$normalized['mode']);

        $sportKey = $normalized['sport_key'];
        $normalized['sport_key'] = is_string($sportKey) && trim($sportKey) !== ''
            ? strtolower(trim($sportKey))
            : null;

        $rawTeamId = $data['team_id'] ?? $advertisement->get('team_id');
        if ($rawTeamId === null || $rawTeamId === '') {
            $normalized['team_id'] = null;
        } elseif (
            is_scalar($rawTeamId)
            && ctype_digit(trim((string)$rawTeamId))
            && (int)$rawTeamId > 0
        ) {
            $normalized['team_id'] = (int)$rawTeamId;
        } else {
            $normalized['team_id'] = null;
            $invalidFields['team_id'] = 'Choose a valid team.';
        }

        $rawActive = $data['is_active'] ?? $advertisement->get('is_active');
        if (is_scalar($rawActive)) {
            $normalized['is_active'] = $this->toBoolean($rawActive);
        } else {
            $normalized['is_active'] = false;
            $invalidFields['is_active'] = 'Choose whether this campaign is active.';
        }

        $rawOrder = $data['display_order'] ?? $advertisement->get('display_order');
        $validatedOrder = is_scalar($rawOrder) ? filter_var($rawOrder, FILTER_VALIDATE_INT) : false;
        if ($validatedOrder === false || $validatedOrder < 0) {
            $normalized['display_order'] = 0;
            $invalidFields['display_order'] = 'Enter a non-negative whole number.';
        } else {
            $normalized['display_order'] = $validatedOrder;
        }

        if ($normalized['target_type'] === 'site') {
            $normalized['sport_key'] = null;
            $normalized['team_id'] = null;
        }

        $advertisement = $this->advertisementsTable->patchEntity($advertisement, $normalized);
        foreach ($invalidFields as $field => $message) {
            $advertisement->setError($field, ['invalid' => $message]);
        }

        return $advertisement;
    }

    /**
     * Validate targeting and delivery requirements, then save a campaign.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Campaign to save.
     */
    public function saveAdvertisement(Advertisement $advertisement): bool
    {
        if ($advertisement->target_type === 'site') {
            $advertisement->set('sport_key', null);
            $advertisement->set('team_id', null);
        } elseif ($advertisement->target_type === 'content') {
            $this->validateContentTarget($advertisement);
        }

        if ($advertisement->is_active && in_array($advertisement->mode, ['custom', 'google'], true)) {
            if (trim((string)$advertisement->html) === '') {
                $advertisement->setError('html', [
                    'required' => 'Enter the ad markup before activating this campaign.',
                ]);
            }
        }

        if ($advertisement->is_active && $advertisement->mode === 'gpt') {
            if (trim((string)$advertisement->gpt_unit_path) === '') {
                $advertisement->setError('gpt_unit_path', [
                    'required' => 'Enter the Google Publisher Tag unit path before activating this campaign.',
                ]);
            }
        }

        if ($advertisement->getErrors() !== []) {
            return false;
        }

        return $this->advertisementsTable->save($advertisement) !== false;
    }

    /**
     * Delete a saved campaign.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Campaign to delete.
     */
    public function deleteAdvertisement(Advertisement $advertisement): bool
    {
        return $this->advertisementsTable->delete($advertisement) !== false;
    }

    /**
     * Return shared provider settings.
     *
     * @return array{global_script:string,publisher_id:string}
     */
    public function getSettings(): array
    {
        $settings = $this->settingsTable->find()->where(['id' => 1])->first();

        return [
            'global_script' => $settings !== null ? trim((string)$settings->get('global_script')) : '',
            'publisher_id' => $settings !== null ? trim((string)$settings->get('publisher_id')) : '',
        ];
    }

    /**
     * Save shared provider settings.
     *
     * @param array<string,mixed> $data Submitted settings.
     */
    public function saveSettings(array $data): bool
    {
        $current = $this->getSettings();
        $globalScript = $this->scalarText($data['global_script'] ?? $current['global_script']);
        $publisherId = $this->scalarText($data['publisher_id'] ?? $current['publisher_id']);
        if ($globalScript === null || $publisherId === null) {
            return false;
        }

        $settings = $this->settingsTable->find()->where(['id' => 1])->first();
        if ($settings === null) {
            $settings = $this->settingsTable->newEntity(['id' => 1]);
        }
        $settings = $this->settingsTable->patchEntity($settings, [
            'id' => 1,
            'global_script' => $globalScript,
            'publisher_id' => $publisherId,
        ]);

        return $this->settingsTable->save($settings) !== false;
    }

    /**
     * Return global campaigns and content campaigns matching the current page.
     *
     * @param string $placement Placement key.
     * @param array<string,mixed> $context Sport/team context.
     * @return array<int,\App\Model\Entity\Advertisement>
     */
    public function getAdvertisementsForPlacement(string $placement, array $context = []): array
    {
        if (!isset(self::PLACEMENT_OPTIONS[$placement])) {
            return [];
        }

        $resolvedContext = $this->normalizeContext($context);
        $advertisements = $this->advertisementsTable
            ->find()
            ->where([
                'placement' => $placement,
                'is_active' => true,
            ])
            ->orderByAsc('display_order')
            ->orderByAsc('id')
            ->all();

        $matchingAdvertisements = [];
        foreach ($advertisements as $advertisement) {
            if (!$advertisement instanceof Advertisement) {
                continue;
            }
            if ($this->matchesContext($advertisement, $resolvedContext)) {
                $matchingAdvertisements[] = $advertisement;
            }
        }

        return $matchingAdvertisements;
    }

    /**
     * Resolve sport/team context from the page's view variables.
     *
     * @param array<string,mixed> $viewVars Template variables.
     * @return array{sportKey:string|null,teamId:int|null}
     */
    public function resolvePageContext(array $viewVars): array
    {
        $fallback = $viewVars['advertisingContext'] ?? $viewVars['publicScope'] ?? [];
        $fallback = is_array($fallback) ? $fallback : [];
        $currentSport = $viewVars['currentSport'] ?? null;
        if (
            is_scalar($currentSport)
            && trim((string)$currentSport) !== ''
            && !isset($fallback['sportKey'])
        ) {
            $fallback['sportKey'] = $currentSport;
        }
        $fallback = $this->normalizeContext($fallback);

        $game = $viewVars['game'] ?? null;
        if ($game instanceof Game) {
            $team = $game->team_season->team ?? null;
            $context = $this->contextForTeam($team);
            if ($context['teamId'] !== null || $context['sportKey'] !== null) {
                return $context;
            }
        }

        $teamSeason = $viewVars['teamSeason'] ?? null;
        if ($teamSeason instanceof TeamSeason) {
            $context = $this->contextForTeam($teamSeason->team ?? null);
            if ($context['teamId'] !== null || $context['sportKey'] !== null) {
                return $context;
            }
        }

        $post = $viewVars['post'] ?? null;
        if ($post instanceof BlogPost) {
            return $this->getBlogPostContext($post, $fallback);
        }

        return $fallback;
    }

    /**
     * Resolve a blog post's sport/team context from its game or team-season tags.
     *
     * @param \App\Model\Entity\BlogPost $post Blog post with loaded tags.
     * @param array<string,mixed> $fallbackContext Context from the public filters.
     * @return array{sportKey:string|null,teamId:int|null}
     */
    public function getBlogPostContext(BlogPost $post, array $fallbackContext = []): array
    {
        foreach ($post->blog_tags ?? [] as $tag) {
            $slug = (string)($tag->slug ?? '');
            if (preg_match('/^teamseason-([1-9][0-9]*)$/', $slug, $matches) === 1) {
                $teamSeason = $this->teamSeasonsTable
                    ->find()
                    ->contain(['Teams'])
                    ->where(['TeamSeasons.id' => (int)$matches[1]])
                    ->first();
                if ($teamSeason instanceof TeamSeason) {
                    $context = $this->contextForTeam($teamSeason->team ?? null);
                    if ($context['teamId'] !== null || $context['sportKey'] !== null) {
                        return $context;
                    }
                }
            }

            if (preg_match('/^game-([1-9][0-9]*)$/', $slug, $matches) === 1) {
                $game = $this->gamesTable
                    ->find()
                    ->contain(['TeamSeason.Teams'])
                    ->where(['Games.id' => (int)$matches[1]])
                    ->first();
                if ($game instanceof Game) {
                    $context = $this->contextForTeam($game->team_season->team ?? null);
                    if ($context['teamId'] !== null || $context['sportKey'] !== null) {
                        return $context;
                    }
                }
            }
        }

        return $this->normalizeContext($fallbackContext);
    }

    /**
     * Normalize publisher IDs for AdSense markup and ads.txt output.
     *
     * @param string $publisherId Submitted or legacy publisher ID.
     */
    public function normalizePublisherId(string $publisherId): string
    {
        $candidate = trim($publisherId);
        if ($candidate === '') {
            return '';
        }

        if (ctype_digit($candidate)) {
            return 'ca-pub-' . $candidate;
        }
        if (preg_match('/^ca-pub-\d+$/i', $candidate) === 1) {
            return strtolower($candidate);
        }
        if (preg_match('/^pub-\d+$/i', $candidate) === 1) {
            return 'ca-' . strtolower($candidate);
        }
        if (preg_match('/ca-pub-\d+/i', $candidate, $matches) === 1) {
            return strtolower((string)$matches[0]);
        }

        return $candidate;
    }

    /**
     * Write a minimal ads.txt file into the supplied webroot.
     *
     * @param string $webroot Destination directory.
     */
    public function writeAdsTxt(string $webroot): bool
    {
        $publisherId = $this->normalizePublisherId($this->getSettings()['publisher_id']);
        if (preg_match('/^ca-pub-\d+$/i', $publisherId) !== 1) {
            return false;
        }

        $path = rtrim($webroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ads.txt';
        $line = sprintf("google.com, %s, DIRECT, f08c47fec0942fa0\n", $publisherId);

        return file_put_contents($path, $line, LOCK_EX) !== false;
    }

    /**
     * Validate campaign filters against the configured sport and team lists.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Campaign entity.
     */
    private function validateContentTarget(Advertisement $advertisement): void
    {
        $sportKey = trim((string)($advertisement->sport_key ?? ''));
        $teamId = $advertisement->team_id !== null ? (int)$advertisement->team_id : null;

        if ($sportKey === '' && ($teamId === null || $teamId < 1)) {
            $advertisement->setError('sport_key', [
                'required' => 'Choose a sport, a team, or both for a content sponsor.',
            ]);

            return;
        }

        if ($sportKey !== '' && !array_key_exists($sportKey, $this->getAvailableSports())) {
            $advertisement->setError('sport_key', [
                'valid' => 'Choose a sport available on this site.',
            ]);
        }

        if ($teamId !== null && $teamId > 0) {
            $teamOptions = $this->publicSportScopeService->getTeamOptions(
                $sportKey !== '' ? $sportKey : null,
            );
            if (!isset($teamOptions[$teamId])) {
                $advertisement->setError('team_id', [
                    'valid' => $sportKey !== ''
                        ? 'Choose a team that belongs to the selected sport.'
                        : 'Choose a team available on this site.',
                ]);
            }
        }
    }

    /**
     * Match a campaign's optional criteria against one normalized page context.
     *
     * @param \App\Model\Entity\Advertisement $advertisement Campaign to evaluate.
     * @param array{sportKey:string|null,teamId:int|null} $context Resolved page context.
     */
    private function matchesContext(Advertisement $advertisement, array $context): bool
    {
        if ($advertisement->target_type === 'site') {
            return true;
        }
        if ($advertisement->target_type !== 'content') {
            return false;
        }

        $targetSportKey = trim((string)($advertisement->sport_key ?? ''));
        $targetTeamId = $advertisement->team_id !== null ? (int)$advertisement->team_id : null;
        if ($targetSportKey === '' && ($targetTeamId === null || $targetTeamId < 1)) {
            return false;
        }
        if ($targetSportKey !== '' && $context['sportKey'] !== $targetSportKey) {
            return false;
        }
        if ($targetTeamId !== null && $context['teamId'] !== $targetTeamId) {
            return false;
        }

        return true;
    }

    /**
     * Normalize request or entity context through the public scope rules.
     *
     * @param array<string,mixed> $context Input context.
     * @return array{sportKey:string|null,teamId:int|null}
     */
    private function normalizeContext(array $context): array
    {
        $sport = $context['sportKey'] ?? $context['sport_key'] ?? $context['sport'] ?? 'all';
        $team = $context['teamId'] ?? $context['team_id'] ?? $context['team'] ?? 'all';
        if (!is_scalar($sport) || !is_scalar($team)) {
            return ['sportKey' => null, 'teamId' => null];
        }

        $resolved = $this->publicSportScopeService->resolve([
            'sport' => $sport,
            'team' => $team,
        ]);
        if (!$resolved['valid']) {
            return ['sportKey' => null, 'teamId' => null];
        }

        return [
            'sportKey' => $resolved['sportKey'],
            'teamId' => $resolved['teamId'],
        ];
    }

    /**
     * Get context from a team entity, falling back through public scope rules.
     *
     * @param mixed $team Team entity or null.
     * @return array{sportKey:string|null,teamId:int|null}
     */
    private function contextForTeam(mixed $team): array
    {
        if (!$team instanceof Team) {
            return ['sportKey' => null, 'teamId' => null];
        }

        return $this->normalizeContext([
            'sportKey' => $team->sport_key ?? 'all',
            'teamId' => (int)$team->id,
        ]);
    }

    /**
     * Convert supported form values to booleans.
     *
     * @param mixed $value Submitted checkbox value.
     */
    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Return scalar text or null for malformed form input.
     *
     * @param mixed $value Submitted value.
     */
    private function scalarText(mixed $value): ?string
    {
        if ($value === null) {
            return '';
        }
        if (!is_scalar($value)) {
            return null;
        }

        return trim((string)$value);
    }
}
