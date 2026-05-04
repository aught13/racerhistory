<?php
declare(strict_types=1);

/**
 * @var array<\App\Model\Entity\TeamSeason> $teamSeasons
 * @var array<int,array<string,array<string,mixed>>> $recordSummaries
 * @var string|null $teamFilter
 * @var string|null $viewMode
 * @var \App\View\AppView $this
 */

echo $this->element('seasons/table_frame', [
    'mode' => $viewMode ?? 'standard',
    'teamSeasons' => $teamSeasons,
    'recordSummaries' => $recordSummaries,
    'teamFilter' => $teamFilter ?? 'all',
]);
