<?php
declare(strict_types=1);

/**
 * @var array<int,array{game:object,stats:array<int,object>}> $gameLogRows
 * @var \App\Model\Entity\TeamSeason|null $teamSeason
 */

$teamName = $teamSeason?->team?->team_name ?? 'Team';
$seasonStart = (string)($teamSeason?->season?->start ?? '');
$seasonEnd = (string)($teamSeason?->season?->end ?? '');
$seasonLabel = '';
if ($seasonStart !== '' || $seasonEnd !== '') {
    $suffix = $seasonEnd !== '' ? substr($seasonEnd, -2) : '';
    $seasonLabel = trim($seasonStart . '-' . $suffix, '-');
}

$formatValue = static function ($value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    return (string)$value;
};
?>
<?php if (empty($gameLogRows)) : ?>
    <p class="text-muted mb-0">No game stats available for this season.</p>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Game</th>
                    <th>GS</th>
                    <th>MIN</th>
                    <th>FG</th>
                    <th>3P</th>
                    <th>FT</th>
                    <th>OR</th>
                    <th>DR</th>
                    <th>TOT</th>
                    <th>PF</th>
                    <th>AS</th>
                    <th>TO</th>
                    <th>ST</th>
                    <th>BS</th>
                    <th>PTS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gameLogRows as $row) : ?>
                    <?php
                    $game = $row['game'] ?? null;
                    $statsRows = $row['stats'] ?? [];
                    $final = null;
                    foreach ($statsRows as $statRow) {
                        if ((string)($statRow->period ?? '') === 'Z') {
                            $final = $statRow;
                            break;
                        }
                    }
                    if (!$final && !empty($statsRows)) {
                        $final = $statsRows[0];
                    }

                    $gameDate = $game?->game_date?->format('m-d-Y') ?? '';
                    $opponentName = $game?->opponent?->opponent_name ?? 'Unknown';
                    $locationPrefix = '@';
                    if (!empty($game?->hrn) && (int)$game->hrn === 1) {
                        $locationPrefix = 'Vs';
                    } elseif (!empty($game?->hrn) && (int)$game->hrn === 3) {
                        $locationPrefix = 'vs';
                    }
                    $gameLabel = trim($gameDate . ' ' . $locationPrefix . ' ' . $opponentName);
                    $gameUrl = $game?->id
                        ? $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id])
                        : null;
                    ?>
                    <tr>
                        <td>
                            <?php if ($gameUrl) : ?>
                                <a href="<?= h($gameUrl) ?>">
                                    <?= h($gameLabel) ?>
                                </a>
                                <?php if ($seasonLabel !== '') : ?>
                                    <div class="text-muted small">
                                        <?= h($teamName) ?> <?= h($seasonLabel) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else : ?>
                                <?= h($gameLabel) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= h($formatValue($final?->GS ?? null)) ?></td>
                        <td><?= h($formatValue($final?->MIN ?? null)) ?></td>
                        <td>
                            <?= h($formatValue($final?->FGM ?? null)) ?>-<?= h($formatValue($final?->FGA ?? null)) ?>
                        </td>
                        <td>
                            <?= h($formatValue($final?->TPM ?? null)) ?>-<?= h($formatValue($final?->TPA ?? null)) ?>
                        </td>
                        <td>
                            <?= h($formatValue($final?->FTM ?? null)) ?>-<?= h($formatValue($final?->FTA ?? null)) ?>
                        </td>
                        <td><?= h($formatValue($final?->ORB ?? null)) ?></td>
                        <td><?= h($formatValue($final?->DRB ?? null)) ?></td>
                        <td><?= h($formatValue($final?->RB ?? null)) ?></td>
                        <td><?= h($formatValue($final?->PF ?? null)) ?></td>
                        <td><?= h($formatValue($final?->AST ?? null)) ?></td>
                        <td><?= h($formatValue($final?->TRN ?? null)) ?></td>
                        <td><?= h($formatValue($final?->STL ?? null)) ?></td>
                        <td><?= h($formatValue($final?->BS ?? null)) ?></td>
                        <td><?= h($formatValue($final?->PTS ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
