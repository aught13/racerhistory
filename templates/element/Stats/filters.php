<?php
declare(strict_types=1);

/**
 * Stats filter form element.
 *
 * @var \App\View\AppView $this
 * @var array{seasons: array, teams: array} $filterOptions
 * @var array $filters Current active filters
 * @var string $statType Current stat type slug
 * @var string $currentSport Current sport name
 */
$actionMap = [
    'player-season' => 'playerSeason',
    'team-season' => 'teamSeason',
    'team-season-opponent' => 'teamSeasonOpponent',
    'player-career' => 'playerCareer',
    'player-game' => 'playerGame',
    'opponent-player-game' => 'opponentPlayerGame',
];
$action = $actionMap[$statType] ?? 'index';
?>
<form method="get" action="<?= $this->Url->build(['controller' => 'Stats', 'action' => $action]) ?>" class="row g-2 mb-4 align-items-end" id="stats-filter-form">
    <input type="hidden" name="sport" value="<?= h($currentSport) ?>">

    <div class="col-md-3">
        <label for="filter-season" class="form-label">Season</label>
        <select name="season_id" id="filter-season" class="form-select form-select-sm">
            <option value="">All Seasons</option>
            <?php foreach ($filterOptions['seasons'] as $id => $label) : ?>
                <option value="<?= (int)$id ?>" <?= isset($filters['season_id']) && (int)$filters['season_id'] === (int)$id ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label for="filter-team" class="form-label">Team</label>
        <select name="team_id" id="filter-team" class="form-select form-select-sm">
            <option value="">All Teams</option>
            <?php foreach ($filterOptions['teams'] as $id => $name) : ?>
                <option value="<?= (int)$id ?>" <?= isset($filters['team_id']) && (int)$filters['team_id'] === (int)$id ? 'selected' : '' ?>>
                    <?= h($name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <label for="filter-sort" class="form-label">Sort By</label>
        <select name="sort" id="filter-sort" class="form-select form-select-sm">
            <?php
            $sortOptions = ['PTS' => 'Points', 'RB' => 'Rebounds', 'AST' => 'Assists', 'STL' => 'Steals',
                'BS' => 'Blocks', 'GP' => 'Games Played', 'MIN' => 'Minutes', 'FGM' => 'Field Goals Made',
                'TPM' => '3-Pointers Made', 'FTM' => 'Free Throws Made', 'TRN' => 'Turnovers'];
            foreach ($sortOptions as $val => $lbl) : ?>
                <option value="<?= h($val) ?>" <?= isset($filters['sort']) && $filters['sort'] === $val ? 'selected' : '' ?>>
                    <?= h($lbl) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <label for="filter-direction" class="form-label">Order</label>
        <select name="direction" id="filter-direction" class="form-select form-select-sm">
            <option value="DESC" <?= isset($filters['direction']) && strtoupper($filters['direction']) === 'DESC' ? 'selected' : '' ?>>High to Low</option>
            <option value="ASC" <?= isset($filters['direction']) && strtoupper($filters['direction']) === 'ASC' ? 'selected' : '' ?>>Low to High</option>
        </select>
    </div>

    <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search me-1"></i> Search
        </button>
    </div>
</form>
