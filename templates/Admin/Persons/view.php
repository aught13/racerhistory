<?php $this->assign('title', 'View Person'); ?>
<div class="container-fluid py-3 py-md-4">
    <!-- Navigation Bar -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <div
                    class="d-flex flex-column flex-sm-row justify-content-between
                    align-items-start align-items-sm-center gap-2"
                >
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-secondary btn-sm" id="back-button" onclick="handleBackButton()">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <a
                            href="<?= $this->Url->build([
                                'prefix' => 'Admin',
                                'controller' => 'Persons',
                                'action' => 'index',
                            ]) ?>"
                            class="btn btn-outline-primary btn-sm"
                        >
                            <i class="bi bi-people"></i> All People
                        </a>
                    </div>
                    <div class="btn-group" role="group" aria-label="Actions">
                        <a
                            href="<?= $this->Url->build([
                                'prefix' => 'Admin',
                                'controller' => 'Persons',
                                'action' => 'edit',
                                $person->id,
                            ]) ?>"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build([
                                'prefix' => 'Admin',
                                'controller' => 'Persons',
                                'action' => 'delete',
                                $person->id,
                            ]) ?>"
                            data-edit-url="<?= $this->Url->build([
                                'prefix' => 'Admin',
                                'controller' => 'Persons',
                                'action' => 'edit',
                                $person->id,
                            ]) ?>"
                            data-item-type="person"
                        >
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <!-- Person Details Card -->
            <div class="card mb-3 mb-md-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0 h4"><?= h($person->display) ?></h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-8">
                            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Personal Information</h5>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">First Name</small>
                                        <strong><?= h($person->first ?: 'N/A') ?></strong>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Last Name</small>
                                        <strong><?= h($person->last ?: 'N/A') ?></strong>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Full Name</small>
                                        <strong><?= h($person->full ?: 'N/A') ?></strong>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Display Name</small>
                                        <strong><?= h($person->display) ?></strong>
                                    </div>
                                </div>
                                <?php if ($person->birth || $person->death) : ?>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Birth</small>
                                        <strong><?= h($person->birth ?: 'N/A') ?></strong>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted d-block">Death</small>
                                        <strong><?= h($person->death ?: 'N/A') ?></strong>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-center sticky-lg-top" style="top: 1rem;">
                                <?= $this->element('person_image', ['person' => $person, 'size' => 'large']) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($person->bio)) : ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="bi bi-file-text"></i> Biography</h5>
                            <div class="person-bio p-3 bg-light rounded">
                                <?php
                                $bio = (string)($person->bio ?? '');
                                // Basic sanitization: strip script/style tags while allowing common formatting
                                    $bioClean = preg_replace(
                                        '#</(script|style)>#i',
                                        '',
                                        preg_replace(
                                            '#<(script|style)[^>]*>.*?</\\1>#is',
                                            '',
                                            $bio
                                        )
                                    );
                                echo $bioClean;
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($rostersBySport)) : ?>
            <!-- Roster Entries Section -->
            <div class="card mb-3 mb-md-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h3 class="mb-0 h5"><i class="bi bi-clipboard-check"></i> Roster Entries</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($rostersBySport as $sportId => $sportData) : ?>
                        <h4 class="mb-3 text-primary"><i class="bi bi-trophy"></i> <?= h($sportData['sport']->sport_name) ?></h4>
                        <div class="accordion mb-4" id="accordion-sport-<?= h($sportId) ?>">
                            <?php foreach ($sportData['rosters'] as $idx => $rosterData) : ?>
                                <?php
                                $roster = $rosterData['roster'];
                                $teamSeason = $rosterData['teamSeason'];
                                $gameStats = $rosterData['gameStats'];
                                $seasonStats = $rosterData['seasonStats'];
                                $accordionId = "roster-{$sportId}-{$idx}";
                                ?>
                                <div class="accordion-item border">
                                    <h2 class="accordion-header" id="heading-<?= h($accordionId) ?>">
                                        <button
                                            class="accordion-button collapsed fw-semibold"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapse-<?= h($accordionId) ?>"
                                            aria-expanded="false"
                                            aria-controls="collapse-<?= h($accordionId) ?>"
                                        >
                                            <span class="me-2"><i class="bi bi-calendar3"></i></span>
                                            <?= h($teamSeason->team->team_name) ?>
                                            <span class="badge bg-info text-dark ms-2">
                                                <?= h($teamSeason->season->start) ?>-<?= h($teamSeason->season->end) ?>
                                            </span>
                                            <?php if ($roster->roster_number) : ?>
                                                <span class="badge bg-secondary ms-2">
                                                    #<?= h($roster->roster_number) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($roster->roster_position) : ?>
                                                <span class="badge bg-primary ms-2">
                                                    <?= h($roster->roster_position) ?>
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </h2>
                                    <div
                                        id="collapse-<?= h($accordionId) ?>"
                                        class="accordion-collapse collapse"
                                        aria-labelledby="heading-<?= h($accordionId) ?>"
                                        data-bs-parent="#accordion-sport-<?= h($sportId) ?>"
                                    >
                                        <div class="accordion-body bg-light">
                                            <!-- Roster Details -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-6 col-sm-3">
                                                    <div class="p-2 bg-white rounded border">
                                                        <small class="text-muted d-block">Number</small>
                                                        <strong><?= h($roster->roster_number ?? 'N/A') ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <div class="p-2 bg-white rounded border">
                                                        <small class="text-muted d-block">Position</small>
                                                        <strong><?= h($roster->roster_position ?? 'N/A') ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <div class="p-2 bg-white rounded border">
                                                        <small class="text-muted d-block">Height</small>
                                                        <strong><?= h($roster->roster_height ?? 'N/A') ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-sm-3">
                                                    <div class="p-2 bg-white rounded border">
                                                        <small class="text-muted d-block">Weight</small>
                                                        <strong><?= h($roster->roster_weight ?? 'N/A') ?></strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($sportId === 1 && !empty($gameStats)) : // Basketball ?>
                                                <!-- Game Stats Table -->
                                                <h5 class="mb-3"><i class="bi bi-graph-up"></i> Game Statistics</h5>
                                                <div class="table-responsive mb-3">
                                                    <table class="table table-sm table-striped table-bordered">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Opponent</th>
                                                                <th>MIN</th>
                                                                <th>FGM-A</th>
                                                                <th>3PM-A</th>
                                                                <th>FTM-A</th>
                                                                <th>REB</th>
                                                                <th>AST</th>
                                                                <th>STL</th>
                                                                <th>BLK</th>
                                                                <th>TO</th>
                                                                <th>PF</th>
                                                                <th>PTS</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($gameStats as $gameData) : ?>
                                                                <?php
                                                                $game = $gameData['game'];
                                                                $stats = $gameData['stats'];
                                                                // Sum stats if multiple periods
                                                                $totals = [
                                                                    'MIN' => 0, 'FGM' => 0, 'FGA' => 0,
                                                                    'TPM' => 0, 'TPA' => 0, 'FTM' => 0, 'FTA' => 0,
                                                                    'RB' => 0, 'AST' => 0, 'STL' => 0, 'BS' => 0,
                                                                    'TRN' => 0, 'PF' => 0, 'PTS' => 0,
                                                                ];
                                                                foreach ($stats as $stat) {
                                                                    foreach ($totals as $field => &$value) {
                                                                        $value += is_numeric($stat->$field) ? (int)$stat->$field : 0;
                                                                    }
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td>
                                                                        <?= $game->game_date
                                                                            ? h($game->game_date->format('M j, Y'))
                                                                            : 'N/A' ?>
                                                                    </td>
                                                                    <td><?= h($game->opponent->opponent_name ?? 'Unknown') ?></td>
                                                                    <td><?= h($totals['MIN']) ?></td>
                                                                    <td><?= h($totals['FGM']) ?>-<?= h($totals['FGA']) ?></td>
                                                                    <td><?= h($totals['TPM']) ?>-<?= h($totals['TPA']) ?></td>
                                                                    <td><?= h($totals['FTM']) ?>-<?= h($totals['FTA']) ?></td>
                                                                    <td><?= h($totals['RB']) ?></td>
                                                                    <td><?= h($totals['AST']) ?></td>
                                                                    <td><?= h($totals['STL']) ?></td>
                                                                    <td><?= h($totals['BS']) ?></td>
                                                                    <td><?= h($totals['TRN']) ?></td>
                                                                    <td><?= h($totals['PF']) ?></td>
                                                                    <td><?= h($totals['PTS']) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <?php if ($seasonStats) : ?>
                                                        <tfoot class="table-secondary fw-bold">
                                                            <tr>
                                                                <td colspan="2">Season Totals</td>
                                                                <td><?= h($seasonStats->MIN ?? 0) ?></td>
                                                                <td><?= h($seasonStats->FGM ?? 0) ?>-<?= h($seasonStats->FGA ?? 0) ?></td>
                                                                <td><?= h($seasonStats->TPM ?? 0) ?>-<?= h($seasonStats->TPA ?? 0) ?></td>
                                                                <td><?= h($seasonStats->FTM ?? 0) ?>-<?= h($seasonStats->FTA ?? 0) ?></td>
                                                                <td><?= h($seasonStats->RB ?? 0) ?></td>
                                                                <td><?= h($seasonStats->AST ?? 0) ?></td>
                                                                <td><?= h($seasonStats->STL ?? 0) ?></td>
                                                                <td><?= h($seasonStats->BS ?? 0) ?></td>
                                                                <td><?= h($seasonStats->TRN ?? 0) ?></td>
                                                                <td><?= h($seasonStats->PF ?? 0) ?></td>
                                                                <td><?= h($seasonStats->PTS ?? 0) ?></td>
                                                            </tr>
                                                        </tfoot>
                                                        <?php endif; ?>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($careerStatsBySport)) : ?>
            <!-- Career Stats Section -->
            <div class="card mb-3 mb-md-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0 h5"><i class="bi bi-award"></i> Career Statistics</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($careerStatsBySport as $sportId => $careerData) : ?>
                        <h4 class="mb-3 text-success"><i class="bi bi-star"></i> <?= h($careerData['sport']->sport_name) ?> Career Totals</h4>
                        <?php if ($sportId === 1) : // Basketball ?>
                            <?php $totals = $careerData['totals']; ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Season</th>
                                            <th>GP</th>
                                            <th>GS</th>
                                            <th>MIN</th>
                                            <th>FGM-A</th>
                                            <th>FG%</th>
                                            <th>3PM-A</th>
                                            <th>3P%</th>
                                            <th>FTM-A</th>
                                            <th>FT%</th>
                                            <th>REB</th>
                                            <th>AST</th>
                                            <th>STL</th>
                                            <th>BLK</th>
                                            <th>TO</th>
                                            <th>PF</th>
                                            <th>PTS</th>
                                            <th>PPG</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($careerData['seasons'] as $seasonData) : ?>
                                            <?php $stats = $seasonData['stats']; ?>
                                            <?php $ts = $seasonData['teamSeason']; ?>
                                            <tr>
                                                <td class="fw-semibold"><?= h($ts->season->start ?? '????') ?>-<?= h($ts->season->end ?? '????') ?></td>
                                                <td><?= h($stats->GP ?? 0) ?></td>
                                                <td><?= h($stats->GS ?? 0) ?></td>
                                                <td><?= h($stats->MIN ?? 0) ?></td>
                                                <td><?= h($stats->FGM ?? 0) ?>-<?= h($stats->FGA ?? 0) ?></td>
                                                <td class="text-primary">
                                                    <?= ($stats->FGA ?? 0) > 0
                                                        ? number_format(($stats->FGM ?? 0) / ($stats->FGA ?? 0) * 100, 1)
                                                        : '0.0' ?>%
                                                </td>
                                                <td><?= h($stats->TPM ?? 0) ?>-<?= h($stats->TPA ?? 0) ?></td>
                                                <td class="text-primary">
                                                    <?= ($stats->TPA ?? 0) > 0
                                                        ? number_format(($stats->TPM ?? 0) / ($stats->TPA ?? 0) * 100, 1)
                                                        : '0.0' ?>%
                                                </td>
                                                <td><?= h($stats->FTM ?? 0) ?>-<?= h($stats->FTA ?? 0) ?></td>
                                                <td class="text-primary">
                                                    <?= ($stats->FTA ?? 0) > 0
                                                        ? number_format(($stats->FTM ?? 0) / ($stats->FTA ?? 0) * 100, 1)
                                                        : '0.0' ?>%
                                                </td>
                                                <td><?= h($stats->RB ?? 0) ?></td>
                                                <td><?= h($stats->AST ?? 0) ?></td>
                                                <td><?= h($stats->STL ?? 0) ?></td>
                                                <td><?= h($stats->BS ?? 0) ?></td>
                                                <td><?= h($stats->TRN ?? 0) ?></td>
                                                <td><?= h($stats->PF ?? 0) ?></td>
                                                <td class="text-success"><?= h($stats->PTS ?? 0) ?></td>
                                                <td class="text-success">
                                                    <?= ($stats->GP ?? 0) > 0
                                                        ? number_format(($stats->PTS ?? 0) / ($stats->GP ?? 0), 1)
                                                        : '0.0' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr class="fw-bold">
                                            <td class="text-center"><?= ($careerData['minYear'] ?? '????') ?>-<?= ($careerData['maxYear'] ?? '????') ?></td>
                                            <td><?= h($totals['GP']) ?></td>
                                            <td><?= h($totals['GS']) ?></td>
                                            <td><?= h($totals['MIN']) ?></td>
                                            <td><?= h($totals['FGM']) ?>-<?= h($totals['FGA']) ?></td>
                                            <td class="text-warning"><?= $totals['FGA'] > 0 ? number_format($totals['FGM'] / $totals['FGA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['TPM']) ?>-<?= h($totals['TPA']) ?></td>
                                            <td class="text-warning"><?= $totals['TPA'] > 0 ? number_format($totals['TPM'] / $totals['TPA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['FTM']) ?>-<?= h($totals['FTA']) ?></td>
                                            <td class="text-warning"><?= $totals['FTA'] > 0 ? number_format($totals['FTM'] / $totals['FTA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['RB']) ?></td>
                                            <td><?= h($totals['AST']) ?></td>
                                            <td><?= h($totals['STL']) ?></td>
                                            <td><?= h($totals['BS']) ?></td>
                                            <td><?= h($totals['TRN']) ?></td>
                                            <td><?= h($totals['PF']) ?></td>
                                            <td class="text-warning"><?= h($totals['PTS']) ?></td>
                                            <td class="text-warning"><?= $totals['GP'] > 0 ? number_format($totals['PTS'] / $totals['GP'], 1) : '0.0' ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>

<?php $this->append('script'); ?>
<script>
function handleBackButton() {
    // Check if the previous page in history is from /admin/persons (index)
    const referrer = document.referrer;
    <?php $personsIndexUrl = $this->Url->build([
        'prefix' => 'Admin',
        'controller' => 'Persons',
        'action' => 'index',
    ]); ?>
    const personsIndexUrl = '<?= h($personsIndexUrl) ?>';

    // If referrer contains /admin/persons but not /admin/persons/view, hide the back button
    if (referrer && referrer.includes('/admin/persons') && !referrer.includes('/admin/persons/view')) {
        // Redirect to persons index instead of going back
        window.location.href = personsIndexUrl;
    } else if (window.history.length > 1) {
        // Otherwise, go back in history
        window.history.back();
    } else {
        // No history, go to persons index
        window.location.href = personsIndexUrl;
    }
}

// Hide back button if coming from persons index
document.addEventListener('DOMContentLoaded', function() {
    const referrer = document.referrer;
    const backButton = document.getElementById('back-button');

    if (backButton && referrer && referrer.includes('/admin/persons') && !referrer.includes('/admin/persons/view')) {
        backButton.style.display = 'none';
    }
});
</script>
<?php $this->end(); ?>
