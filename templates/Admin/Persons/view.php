<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $careerStatsBySport
 * @var mixed $rostersBySport
 * @var \App\Model\Entity\Person $person
 */

$personsIndexUrl = $this->Url->build([
    'prefix' => 'Admin',
    'controller' => 'Persons',
    'action' => 'index',
]);
$personsViewBaseUrl = $this->Url->build([
    'prefix' => 'Admin',
    'controller' => 'Persons',
    'action' => 'view',
]);
?>
<?php $this->assign('title', 'View Person'); ?>
<div class="container-fluid py-3 py-md-4"
    data-controller="back-navigation"
    data-back-navigation-index-url-value="<?= h($personsIndexUrl) ?>"
    data-back-navigation-index-path-value="<?= h($personsIndexUrl) ?>"
    data-back-navigation-view-path-value="<?= h($personsViewBaseUrl) ?>">
    <!-- Navigation Bar -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <div
                    class="d-flex flex-column flex-sm-row justify-content-between
                    align-items-start align-items-sm-center gap-2"
                >
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-secondary btn-sm" id="back-button"
                            data-back-navigation-target="backButton"
                            data-action="click->back-navigation#goBack">
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
                                            $bio,
                                        ),
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

                                            <?php
                                                // Render sport-specific person stats via elements
                                                $sportName = strtolower((string)($sportData['sport']->sport_name ?? ''));
                                                $elementName = 'Admin/' . $sportName . '_person_stats';
                                            if (!empty($gameStats) || !empty($seasonStats)) {
                                                echo $this->element($elementName, compact('gameStats', 'seasonStats'));
                                            } else {
                                                echo $this->element('Admin/person_stats_empty');
                                            }
                                            ?>
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
                    <?php foreach ($careerStatsBySport as $careerData) : ?>
                        <h4 class="mb-3 text-success"><i class="bi bi-star"></i> <?= h($careerData['sport']->sport_name) ?> Career Totals</h4>
                        <?php
                            // Career totals rendered by sport-specific element when available
                            $sportName = strtolower((string)($careerData['sport']->sport_name ?? ''));
                            $careerElement = 'Admin/' . $sportName . '_person_career_stats';
                            $totals = $careerData['totals'];
                        ?>
                            <?php
                                $seasons = $careerData['seasons'];
                            if (!empty($seasons)) {
                                echo $this->element($careerElement, [
                                    'seasons' => $seasons,
                                    'totals' => $totals,
                                    'minYear' => $careerData['minYear'] ?? null,
                                    'maxYear' => $careerData['maxYear'] ?? null,
                                ]);
                            } else {
                                echo $this->element('Admin/person_career_empty');
                            }
                            ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>
