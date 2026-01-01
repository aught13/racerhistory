<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Table\GamesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

/**
 * GameUpsertService
 *
 * Encapsulates the orchestration logic for creating and updating Games,
 * including EAV metadata preparation and period-score validation.
 */
class GameUpsertService
{
    private GamesTable $gamesTable;
    private GameService $gameService;
    private SportConfigService $sportConfigService;
    private GameEavUiService $gameEavUi;

    /**
     * Constructor.
     *
     * @param \App\Model\Table\GamesTable|null $gamesTable Games table
     * @param \App\Service\GameService|null $gameService Game service
     * @param \App\Service\SportConfigService|null $sportConfigService Sport config service
     * @param \App\Service\GameEavUiService|null $gameEavUi EAV UI helper
     */
    public function __construct(
        ?GamesTable $gamesTable = null,
        ?GameService $gameService = null,
        ?SportConfigService $sportConfigService = null,
        ?GameEavUiService $gameEavUi = null,
    ) {
        /** @var \App\Model\Table\GamesTable $table */
        $table = $gamesTable ?? TableRegistry::getTableLocator()->get('Games');

        $this->gamesTable = $table;
        $this->gameService = $gameService ?? new GameService();
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
        $this->gameEavUi = $gameEavUi ?? new GameEavUiService();
    }

    /**
     * Build view variables for the add form.
     *
     * @param int $teamSeasonId Team season id
     * @return array<string,mixed>
     */
    public function getAddViewData(int $teamSeasonId): array
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->gamesTable->newEmptyEntity();
        $game->set('team_season_id', $teamSeasonId);

        $metadata = $this->gameService->getGameEavMetadata(null, $teamSeasonId);

        return $this->buildUpsertViewData($game, $metadata, []);
    }

    /**
     * Process POST for add.
     *
     * @param int $teamSeasonId Team season id
     * @param array $data Request data
     * @return array<string,mixed>
     */
    public function processAdd(int $teamSeasonId, array $data): array
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->gamesTable->newEmptyEntity();
        $game->set('team_season_id', $teamSeasonId);

        $metadata = $this->gameService->getGameEavMetadata(null, $teamSeasonId);
        $sportId = (int)$metadata['sportId'];

        $newOpponentId = null;
        $trackNewOpponent = !empty($data['new_opponent']['opponent_name']);
        $this->gameService->normalizeAssociatedInlineCreate($data);
        if ($trackNewOpponent && !empty($data['opponent_id'])) {
            $newOpponentId = (int)$data['opponent_id'];
        }

        $data = $this->gameService->calculateWinLoss($data);

        $eavErrors = $this->sportConfigService->validatePeriodScores($sportId, $data);
        if (!empty($eavErrors)) {
            $viewData = $this->buildUpsertViewData($game, $metadata, $data);

            return [
                'success' => false,
                'flashErrors' => $eavErrors,
                'flashSuccess' => null,
                'redirect' => null,
                'placeId' => null,
                'viewData' => $viewData,
            ];
        }

        $game = $this->gamesTable->patchEntity($game, $data);
        if ($this->gamesTable->save($game)) {
            $this->gameService->saveGameEavFromRequest((int)$game->get('id'), $data);

            $redirect = $newOpponentId
                ? [
                    'prefix' => 'Admin',
                    'controller' => 'Opponents',
                    'action' => 'edit',
                    $newOpponentId,
                ]
                : [
                    'prefix' => 'Admin',
                    'controller' => 'TeamSeasons',
                    'action' => 'view',
                    $teamSeasonId,
                ];

            return [
                'success' => true,
                'flashErrors' => [],
                'flashSuccess' => 'The game has been saved.',
                'redirect' => $redirect,
                'placeId' => null,
                'viewData' => [],
            ];
        }

        $viewData = $this->buildUpsertViewData($game, $metadata, []);

        return [
            'success' => false,
            'flashErrors' => ['The game could not be saved. Please, try again.'],
            'flashSuccess' => null,
            'redirect' => null,
            'placeId' => null,
            'viewData' => $viewData,
        ];
    }

    /**
     * Build view variables for the edit form.
     *
     * @param int $gameId Game id
     * @return array<string,mixed>
     */
    public function getEditViewData(int $gameId): array
    {
        $game = $this->getGameForEdit($gameId);
        $metadata = $this->gameService->getGameEavMetadata($gameId, null);

        return $this->buildUpsertViewData($game, $metadata, $metadata['values'] ?? []);
    }

    /**
     * Process POST/PUT/PATCH for edit.
     *
     * @param int $gameId Game id
     * @param array $data Request data
     * @return array<string,mixed>
     */
    public function processEdit(int $gameId, array $data): array
    {
        $game = $this->getGameForEdit($gameId);
        $metadata = $this->gameService->getGameEavMetadata($gameId, null);

        $this->gameService->normalizeAssociatedInlineCreate($data);
        $data = $this->gameService->calculateWinLoss($data);

        $sportId = $game->team_season?->team?->sport?->id;

        $eavErrors = $sportId ? $this->sportConfigService->validatePeriodScores((int)$sportId, $data) : [];
        if (!empty($eavErrors)) {
            $eav = $this->gameEavUi->mergePostedPeriodAndOvertimeFields($metadata['values'] ?? [], $data);
            $viewData = $this->buildUpsertViewData($game, $metadata, $eav);

            return [
                'success' => false,
                'flashErrors' => $eavErrors,
                'flashSuccess' => null,
                'redirect' => null,
                'placeId' => $game->place_id,
                'viewData' => $viewData,
            ];
        }

        $game = $this->gamesTable->patchEntity($game, $data);
        if ($this->gamesTable->save($game)) {
            $this->gameService->saveGameEavFromRequest((int)$game->get('id'), $data);

            $teamSeasonId = $game->get('team_season_id');
            $redirect = $teamSeasonId
                ? [
                    'prefix' => 'Admin',
                    'controller' => 'TeamSeasons',
                    'action' => 'view',
                    $teamSeasonId,
                ]
                : ['action' => 'index'];

            return [
                'success' => true,
                'flashErrors' => [],
                'flashSuccess' => 'The game has been saved.',
                'redirect' => $redirect,
                'placeId' => null,
                'viewData' => [],
            ];
        }

        $viewData = $this->buildUpsertViewData($game, $metadata, $metadata['values'] ?? []);

        return [
            'success' => false,
            'flashErrors' => ['The game could not be saved. Please, try again.'],
            'flashSuccess' => null,
            'redirect' => null,
            'placeId' => $game->place_id,
            'viewData' => $viewData,
        ];
    }

    /**
     * Load the game record used for edit.
     *
     * @param int $gameId Game id
     * @return \App\Model\Entity\Game
     */
    private function getGameForEdit(int $gameId): Game
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->gamesTable->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']]])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        return $game;
    }

    /**
     * Build common view variables for add/edit.
     *
     * @param \Cake\Datasource\EntityInterface $game Game entity
     * @param array $metadata EAV metadata array from GameService
     * @param array $eav EAV values to display
     * @return array<string,mixed>
     */
    private function buildUpsertViewData(EntityInterface $game, array $metadata, array $eav): array
    {
        $sportId = (int)($metadata['sportId'] ?? 0);
        $sportName = (string)($metadata['sportName'] ?? '');
        $sportConfigs = $metadata['configs'] ?? [];
        $eavTemplate = $metadata['eavTemplate'] ?? [];

        $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);

        return [
            'game' => $game,
            'eav' => $eav,
            'sportId' => $sportId,
            'sportName' => $sportName,
            'sportConfigs' => $sportConfigs,
            'eavTemplate' => $eavTemplate,
            'legacyMappedEav' => $legacyMappedEav,
        ];
    }
}
