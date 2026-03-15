<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\TeamSeason[] $teamSeasons
 * @var array<int,array<string,array<string,mixed>>> $recordSummaries
 * @var string|null $teamFilter
 */
$this->assign('title', 'Season Splits');

echo $this->element('seasons/table_assets');
echo $this->element('seasons/table_frame', [
    'mode' => 'splits',
    'teamSeasons' => $teamSeasons,
    'recordSummaries' => $recordSummaries,
    'teamFilter' => $teamFilter ?? 'all',
]);
