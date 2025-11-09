<?php $this->assign('title', 'View Person'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Person Details</h2>
                    <div class="btn-group" role="group" aria-label="Actions">
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>" class="btn btn-primary btn-sm">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'delete', $person->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>"
                            data-item-type="person">Delete</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Display Name</dt><dd class="col-sm-8"><?= h($person->display) ?></dd>
                                <dt class="col-sm-4">First</dt><dd class="col-sm-8"><?= h($person->first) ?></dd>
                                <dt class="col-sm-4">Last</dt><dd class="col-sm-8"><?= h($person->last) ?></dd>
                                <dt class="col-sm-4">Full</dt><dd class="col-sm-8"><?= h($person->full) ?></dd>
                                <dt class="col-sm-4">Birth</dt><dd class="col-sm-8"><?= h($person->birth) ?></dd>
                                <dt class="col-sm-4">Death</dt><dd class="col-sm-8"><?= h($person->death) ?></dd>
                                <dt class="col-sm-4">Image ID</dt><dd class="col-sm-8"><?= h($person->person_image) ?></dd>
                                <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?= h($person->created_at) ?></dd>
                                <dt class="col-sm-4">Updated</dt><dd class="col-sm-8"><?= h($person->updated_at) ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <?= $this->element('person_image', ['person' => $person, 'size' => 'large']) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($person->bio)) : ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Biography</h5>
                            <div class="person-bio">
                                <?php
                                $bio = (string)($person->bio ?? '');
                                // Basic sanitization: strip script/style tags while allowing common formatting
                                $bioClean = preg_replace('#<\/(script|style)>#i', '', preg_replace('#<(script|style)[^>]*>.*?<\/\1>#is', '', $bio));
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
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">Roster Entries</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($rostersBySport as $sportId => $sportData) : ?>
                        <h4 class="mb-3"><?= h($sportData['sport']->sport_name) ?></h4>
                        <div class="accordion mb-4" id="accordion-sport-<?= h($sportId) ?>">
                            <?php foreach ($sportData['rosters'] as $idx => $rosterData) : ?>
                                <?php
                                $roster = $rosterData['roster'];
                                $teamSeason = $rosterData['teamSeason'];
                                $gameStats = $rosterData['gameStats'];
                                $seasonStats = $rosterData['seasonStats'];
                                $accordionId = "roster-{$sportId}-{$idx}";
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= h($accordionId) ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= h($accordionId) ?>" aria-expanded="false" aria-controls="collapse-<?= h($accordionId) ?>">
                                            <?= h($teamSeason->team->team_name) ?> (<?= h($teamSeason->season->start) ?>-<?= h($teamSeason->season->end) ?>)
                                            <?php if ($roster->roster_number) :
                                                ?> - #<?= h($roster->roster_number) ?><?php
                                            endif; ?>
                                            <?php if ($roster->roster_position) :
                                                ?> - <?= h($roster->roster_position) ?><?php
                                            endif; ?>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= h($accordionId) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= h($accordionId) ?>" data-bs-parent="#accordion-sport-<?= h($sportId) ?>">
                                        <div class="accordion-body">
                                            <!-- Roster Details -->
                                            <dl class="row mb-3">
                                                <dt class="col-sm-3">Number</dt><dd class="col-sm-3"><?= h($roster->roster_number ?? 'N/A') ?></dd>
                                                <dt class="col-sm-3">Position</dt><dd class="col-sm-3"><?= h($roster->roster_position ?? 'N/A') ?></dd>
                                                <dt class="col-sm-3">Height</dt><dd class="col-sm-3"><?= h($roster->roster_height ?? 'N/A') ?></dd>
                                                <dt class="col-sm-3">Weight</dt><dd class="col-sm-3"><?= h($roster->roster_weight ?? 'N/A') ?></dd>
                                            </dl>

                                            <?php if ($sportId === 1 && !empty($gameStats)) : // Basketball ?>
                                                <!-- Game Stats Table -->
                                                <h5>Game Stats</h5>
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
                                                                    <td><?= $game->game_date ? h($game->game_date->format('M j, Y')) : 'N/A' ?></td>
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
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">Career Statistics</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($careerStatsBySport as $sportId => $careerData) : ?>
                        <h4 class="mb-3"><?= h($careerData['sport']->sport_name) ?> Career Totals</h4>
                        <?php if ($sportId === 1) : // Basketball ?>
                            <?php $totals = $careerData['totals']; ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
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
                                        <tr>
                                            <td><?= h($totals['GP']) ?></td>
                                            <td><?= h($totals['GS']) ?></td>
                                            <td><?= h($totals['MIN']) ?></td>
                                            <td><?= h($totals['FGM']) ?>-<?= h($totals['FGA']) ?></td>
                                            <td><?= $totals['FGA'] > 0 ? number_format($totals['FGM'] / $totals['FGA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['TPM']) ?>-<?= h($totals['TPA']) ?></td>
                                            <td><?= $totals['TPA'] > 0 ? number_format($totals['TPM'] / $totals['TPA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['FTM']) ?>-<?= h($totals['FTA']) ?></td>
                                            <td><?= $totals['FTA'] > 0 ? number_format($totals['FTM'] / $totals['FTA'] * 100, 1) : '0.0' ?>%</td>
                                            <td><?= h($totals['RB']) ?></td>
                                            <td><?= h($totals['AST']) ?></td>
                                            <td><?= h($totals['STL']) ?></td>
                                            <td><?= h($totals['BS']) ?></td>
                                            <td><?= h($totals['TRN']) ?></td>
                                            <td><?= h($totals['PF']) ?></td>
                                            <td><?= h($totals['PTS']) ?></td>
                                            <td><?= $totals['GP'] > 0 ? number_format($totals['PTS'] / $totals['GP'], 1) : '0.0' ?></td>
                                        </tr>
                                    </tbody>
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
