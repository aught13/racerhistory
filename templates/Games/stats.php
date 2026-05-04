<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\Game $game
 * @var array $teamBoxStats
 * @var array $opponentBoxStats
 * @var array $teamPeriodStats
 * @var array $opponentPeriodStats
 * @var \Cake\Collection\CollectionInterface|null $playerStats
 * @var \Cake\Collection\CollectionInterface|null $opponentPlayerStats
 * @var object|null $teamTeamStats
 * @var object|null $opponentTeamStats
 * @var bool $hasPeriodStats
 * @var array<string,string> $fieldLabels
 * @var string|null $statsElement
 * @var \App\View\AppView $this
 */
?>
<turbo-frame id="game-stats-frame">
    <?php if (!empty($statsElement)) : ?>
        <?= $this->element($statsElement, [
            'game' => $game,
            'teamBoxStats' => $teamBoxStats ?? [],
            'opponentBoxStats' => $opponentBoxStats ?? [],
            'teamPeriodStats' => $teamPeriodStats ?? [],
            'opponentPeriodStats' => $opponentPeriodStats ?? [],
            'playerStats' => $playerStats ?? null,
            'opponentPlayerStats' => $opponentPlayerStats ?? null,
            'teamTeamStats' => $teamTeamStats ?? null,
            'opponentTeamStats' => $opponentTeamStats ?? null,
            'hasPeriodStats' => $hasPeriodStats ?? false,
            'fieldLabels' => $fieldLabels ?? [],
        ]) ?>
    <?php else : ?>
        <p class="text-muted mb-0">Stats are not available for this sport yet.</p>
    <?php endif; ?>
</turbo-frame>
