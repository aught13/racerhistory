<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var mixed $teams
 */
$teamsIterable = is_iterable($teams ?? []) ? $teams : [];
$selectedValue = isset($selectedValue) ? (int)$selectedValue : null;
$showId = !empty($showId);

$getTeamValue = function ($team, string $key) {
    if (is_array($team)) {
        return $team[$key] ?? null;
    }
    if (is_object($team)) {
        return $team->{$key} ?? null;
    }

    return null;
};

foreach ($teamsIterable as $team) :
    $teamId = (int)($getTeamValue($team, 'id') ?? 0);
    $label = $getTeamValue($team, 'label') ?? $getTeamValue($team, 'team_name') ?? 'Team #' . $teamId;
    if ($showId && $teamId > 0) {
        $label .= ' (' . $teamId . ')';
    }
    $isSelected = $selectedValue !== null && $teamId === $selectedValue;
    ?>
<option value="<?= h($teamId) ?>" <?= $isSelected ? 'selected' : '' ?>><?= h($label) ?></option>
<?php endforeach; ?>
