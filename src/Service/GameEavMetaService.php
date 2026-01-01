<?php
declare(strict_types=1);

namespace App\Service;

/**
 * GameEavMetaService
 *
 * Prepares payloads and view variables for the Games EAV meta AJAX endpoint.
 */
class GameEavMetaService
{
    private GameService $gameService;
    private GameEavUiService $gameEavUi;

    /**
     * Constructor.
     *
     * @param \App\Service\GameService|null $gameService Game service
     * @param \App\Service\GameEavUiService|null $gameEavUi EAV UI helper
     */
    public function __construct(?GameService $gameService = null, ?GameEavUiService $gameEavUi = null)
    {
        $this->gameService = $gameService ?? new GameService();
        $this->gameEavUi = $gameEavUi ?? new GameEavUiService();
    }

    /**
     * Get metadata and JSON payload for the ajaxGameEavMeta endpoint.
     *
     * @param int|null $gameId Game id (optional)
     * @param int|null $teamSeasonId Team season id (optional)
     * @return array{payload: array<string,mixed>, metadata: array<string,mixed>|null}
     */
    public function getMetadataResult(?int $gameId, ?int $teamSeasonId): array
    {
        if (!$gameId && !$teamSeasonId) {
            return [
                'payload' => ['success' => false],
                'metadata' => null,
            ];
        }

        try {
            $metadata = $this->gameService->getGameEavMetadata($gameId ?: null, $teamSeasonId ?: null);

            return [
                'payload' => [
                    'success' => true,
                    'sportId' => $metadata['sportId'],
                    'sportName' => $metadata['sportName'],
                    'configs' => $metadata['configs'],
                    'eavTemplate' => $metadata['eavTemplate'],
                    'values' => $metadata['values'],
                ],
                'metadata' => $metadata,
            ];
        } catch (\Throwable $e) {
            return [
                'payload' => [
                    'success' => false,
                    'error' => 'Lookup failed',
                ],
                'metadata' => null,
            ];
        }
    }

    /**
     * Build variables expected by the Admin/Games sport-specific fields element.
     *
     * @param array<string,mixed> $metadata Metadata from GameService::getGameEavMetadata()
     * @return array{eavTemplate: array, eav: array, legacyMappedEav: array, sportName: string}
     */
    public function buildSportSpecificFieldsElementVars(array $metadata): array
    {
        $eavTemplate = $metadata['eavTemplate'] ?? [];
        $eav = $metadata['values'] ?? [];
        $legacyMappedEav = $this->gameEavUi->mapLegacyKeys($eav);
        $sportName = (string)($metadata['sportName'] ?? '');

        return compact('eavTemplate', 'eav', 'legacyMappedEav', 'sportName');
    }
}
