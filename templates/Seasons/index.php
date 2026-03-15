<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\TeamSeason[] $teamSeasons
 * @var array<int,array<string,int|float|null>> $seasonStats
 * @var array<int,array<string,array<string,mixed>>> $recordSummaries
 */
$this->assign('title', 'Team Seasons');

echo $this->element('seasons/table_assets');
echo $this->element('seasons/table_frame', [
    'mode' => $viewMode ?? 'standard',
    'teamSeasons' => $teamSeasons,
    'recordSummaries' => $recordSummaries,
    'teamFilter' => $teamFilter ?? 'all',
]);
