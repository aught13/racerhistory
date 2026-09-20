<?php
/**
 * NCAA LiveStats basketball box-score importer.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Game $game
 * @var string $rawText
 * @var list<array{id: int, jersey: string, name: string, label: string}> $roster
 * @var list<array<string, mixed>> $teamRows
 * @var list<array<string, mixed>> $opponentRows
 * @var array<string, mixed> $teamBox
 * @var array<string, mixed> $opponentBox
 * @var list<string> $warnings
 */
$this->assign('title', 'Import Basketball Box Score');
$hasPreview = isset($teamRows) && $teamRows !== [];
$playerFields = [
    'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
    'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL', 'BS', 'BD',
];
$boxFields = ['FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL', 'BS', 'TF'];
?>
<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>">Game Details</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Import Box Score</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-1">Import Basketball Box Score</h1>
            <p class="text-muted mb-0">
                <?= h($game->team_season->team->team_name ?? 'Team') ?> vs
                <?= h($game->opponent->opponent_name ?? 'Opponent') ?>
                <?php if ($game->game_date) : ?>
                    on <?= h($game->game_date->format('M j, Y')) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Game
        </a>
    </div>

    <div class="alert alert-info">
        <strong>Workflow:</strong> upload an official NCAA box-score PDF, preview the detected rows, correct any roster mapping, then save the import. The importer reads final player and team totals; play-by-play and shot-chart pages are ignored.
    </div>

    <?= $this->Form->create(null, [
        'type' => 'file',
        'enctype' => 'multipart/form-data',
        'data-turbo' => 'false',
        'url' => ['action' => 'index', $game->id],
    ]) ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">1. LiveStats PDF</h2>
        </div>
        <div class="card-body">
            <label for="pdf-file" class="form-label">Choose the official PDF</label>
            <input id="pdf-file" name="pdf_file" type="file" accept="application/pdf,.pdf" class="form-control">
            <div class="form-text">Upload the original NCAA LiveStats PDF, up to 20 MB. The server extracts the text temporarily and deletes the uploaded copy after previewing.</div>
            <details class="mt-3">
                <summary>Use pasted text instead</summary>
                <label for="raw-text" class="form-label mt-2">Extracted PDF text</label>
                <textarea id="raw-text" name="raw_text" class="form-control font-monospace" rows="8"><?= h($rawText) ?></textarea>
                <div class="form-text">This fallback is useful for PDFs that contain scanned images rather than selectable text.</div>
            </details>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" name="intent" value="preview" class="btn btn-primary">
                <i class="bi bi-search"></i> Preview Import
            </button>
        </div>
    </div>

    <?php if ($hasPreview) : ?>
        <?php if (!empty($warnings)) : ?>
            <div class="alert alert-warning">
                <h2 class="h6">Review before saving</h2>
                <ul class="mb-0">
                    <?php foreach ($warnings as $warning) : ?>
                        <li><?= h($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">2. Team player rows</h2>
                <span class="badge text-bg-primary"><?= count($teamRows) ?> detected</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>PDF Player</th>
                            <th>Roster Mapping</th>
                            <?php foreach ($playerFields as $field) : ?>
                                <th><?= h($field) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamRows as $index => $row) : ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="team_rows[<?= (int)$index ?>][jersey]" value="<?= h($row['jersey']) ?>">
                                    <input type="hidden" name="team_rows[<?= (int)$index ?>][name]" value="<?= h($row['name']) ?>">
                                    <strong>#<?= h($row['jersey']) ?></strong> <?= h($row['name']) ?>
                                    <?php if (($row['match_type'] ?? 'none') !== 'none') : ?>
                                        <small class="d-block text-muted">Matched by <?= h($row['match_type']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <label class="visually-hidden" for="team-roster-<?= (int)$index ?>">Roster mapping for <?= h($row['name']) ?></label>
                                    <select id="team-roster-<?= (int)$index ?>" name="team_rows[<?= (int)$index ?>][team_season_roster_id]" class="form-select form-select-sm" required>
                                        <option value="">Select roster player</option>
                                        <?php foreach ($roster as $rosterRow) : ?>
                                            <option value="<?= (int)$rosterRow['id'] ?>" <?= (string)($row['team_season_roster_id'] ?? '') === (string)$rosterRow['id'] ? 'selected' : '' ?>><?= h($rosterRow['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <?php foreach ($playerFields as $field) : ?>
                                    <td>
                                        <label class="visually-hidden" for="team-<?= (int)$index ?>-<?= h($field) ?>"><?= h($field) ?> for <?= h($row['name']) ?></label>
                                        <input id="team-<?= (int)$index ?>-<?= h($field) ?>" type="text" inputmode="decimal" name="team_rows[<?= (int)$index ?>][<?= h($field) ?>]" value="<?= h((string)($row[$field] ?? '')) ?>" class="form-control form-control-sm" style="min-width: 4.5rem;">
                                    </td>
                                <?php endforeach; ?>
                                <input type="hidden" name="team_rows[<?= (int)$index ?>][period]" value="Z">
                                <input type="hidden" name="team_rows[<?= (int)$index ?>][GP]" value="1">
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">3. Opponent player rows</h2>
                <span class="badge text-bg-danger"><?= count($opponentRows) ?> detected</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Jersey</th>
                            <th>Name</th>
                            <?php foreach ($playerFields as $field) : ?>
                                <th><?= h($field) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($opponentRows as $index => $row) : ?>
                            <tr>
                                <td><input type="text" name="opponent_rows[<?= (int)$index ?>][jersey]" value="<?= h((string)$row['jersey']) ?>" class="form-control form-control-sm" style="min-width: 4rem;"></td>
                                <td><input type="text" name="opponent_rows[<?= (int)$index ?>][name]" value="<?= h((string)$row['name']) ?>" class="form-control form-control-sm" style="min-width: 10rem;" required></td>
                                <?php foreach ($playerFields as $field) : ?>
                                    <td><input type="text" inputmode="decimal" name="opponent_rows[<?= (int)$index ?>][<?= h($field) ?>]" value="<?= h((string)($row[$field] ?? '')) ?>" class="form-control form-control-sm" style="min-width: 4.5rem;"></td>
                                <?php endforeach; ?>
                                <input type="hidden" name="opponent_rows[<?= (int)$index ?>][period]" value="Z">
                                <input type="hidden" name="opponent_rows[<?= (int)$index ?>][GP]" value="1">
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2 class="h5 mb-0">4. Team totals</h2></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach (['team' => $teamBox, 'opponent' => $opponentBox] as $side => $box) : ?>
                        <div class="col-lg-6">
                            <h3 class="h6 text-capitalize"><?= h($side) ?> totals</h3>
                            <div class="row g-2">
                                <?php foreach ($boxFields as $field) : ?>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small" for="<?= h($side) ?>-box-<?= h($field) ?>"><?= h($field) ?></label>
                                        <input id="<?= h($side) ?>-box-<?= h($field) ?>" type="text" inputmode="decimal" name="<?= h($side) ?>_box[<?= h($field) ?>]" value="<?= h((string)($box[$field] ?? '')) ?>" class="form-control form-control-sm">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="form-check">
                    <input type="checkbox" name="add_to_totals" value="1" class="form-check-input" id="add-to-totals">
                    <label class="form-check-label" for="add-to-totals"><strong>Update season totals</strong></label>
                    <div class="form-text">Check this only when these final rows have not already been added to the season totals.</div>
                </div>
                <div class="mt-3" style="max-width: 12rem;">
                    <label class="form-label" for="team-minutes">Team minutes</label>
                    <input id="team-minutes" type="number" name="team_minutes" value="200" min="0" step="1" class="form-control">
                    <div class="form-text">Use 200 for regulation, plus 50 for each overtime period.</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="submit" name="intent" value="save" class="btn btn-success">
                <i class="bi bi-database-check"></i> Save Import
            </button>
            <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    <?php endif; ?>

    <?= $this->Form->end() ?>
</div>
