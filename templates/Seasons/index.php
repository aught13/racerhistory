<?php
declare(strict_types=1);

/**
 * @var array<\App\Model\Entity\TeamSeason> $teamSeasons
 * @var array<int,array<string,int|float|null>> $seasonStats
 * @var array<int,array<string,array<string,mixed>>> $recordSummaries
 * @var \App\View\AppView $this
 * @var mixed $teamFilter
 * @var mixed $viewMode
 */
$this->assign('title', 'Team Seasons');

echo $this->element('seasons/table_assets');
echo $this->element('seasons/table_frame', [
    'mode' => $viewMode ?? 'standard',
    'teamSeasons' => $teamSeasons,
    'recordSummaries' => $recordSummaries,
    'teamFilter' => $teamFilter ?? 'all',
]);
