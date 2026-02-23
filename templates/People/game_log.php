<?php
declare(strict_types=1);

/**
 * @var \App\Model\Entity\Person $person
 * @var \App\Model\Entity\TeamSeason|null $teamSeason
 * @var \App\Model\Entity\Sport|null $sport
 * @var array<int,array{game:object,stats:array<int,object>}> $gameLogRows
 * @var string|null $gameLogElement
 * @var string $frameId
 */
?>
<turbo-frame id="<?= h($frameId) ?>">
    <?php if (!empty($gameLogElement)) : ?>
        <?= $this->element($gameLogElement, [
            'gameLogRows' => $gameLogRows,
            'teamSeason' => $teamSeason,
            'sport' => $sport,
            'person' => $person,
        ]) ?>
    <?php else : ?>
        <p class="text-muted mb-0">Game log is not available for this sport.</p>
    <?php endif; ?>
</turbo-frame>
