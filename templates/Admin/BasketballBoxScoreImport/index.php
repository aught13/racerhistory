<?php
/**
 * NCAA LiveStats basketball box-score importer.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Game $game
 * @var string $rawText
 * @var string $sourceType
 * @var string $sourceUrl
 * @var list<array{id: int, jersey: string, name: string, label: string}> $roster
 * @var list<array<string, mixed>> $teamRows
 * @var list<array<string, mixed>> $opponentRows
 * @var array<string, mixed> $teamBox
 * @var array<string, mixed> $opponentBox
 * @var array<string, mixed> $gameResults
 * @var array<string, array<string, mixed>> $periodBoxes
 * @var list<string> $warnings
 */
$this->assign('title', 'Import Basketball Box Score');
$teamName = (string)($game->team_season?->team?->team_name ?? 'Team');
$opponentName = (string)($game->opponent?->opponent_name ?? 'Opponent');
$gameDate = $game->game_date ?? null;
$hasPreview = isset($parsed) && is_array($parsed);
$gameResults = $gameResults ?? [];
$periodBoxes = $periodBoxes ?? [];
$playerFields = [
    'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB',
    'PF', 'FD', 'PTS', 'AST', 'TRN', 'STL', 'BS', 'BD',
];
$boxFields = [
    'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA', 'ORB', 'DRB', 'RB', 'PF', 'PTS',
    'AST', 'TRN', 'STL', 'BS', 'TF', 'PNT', 'OTO', 'SND', 'FB', 'BN', 'TIED', 'LC',
];
$boxFieldLabels = [
    'PNT' => 'Points in Paint', 'OTO' => 'Points off Turnovers', 'SND' => 'Second-Chance Points',
    'FB' => 'Fast Break Points', 'BN' => 'Bench Points', 'TIED' => 'Times Tied', 'LC' => 'Lead Changes',
];
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
                <?= h($teamName) ?> vs
                <?= h($opponentName) ?>
                <?php if ($gameDate instanceof DateTimeInterface) : ?>
                    on <?= h($gameDate->format('M j, Y')) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'view', $game->id]) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Game
        </a>
    </div>

    <div class="alert alert-info">
        <strong>Workflow:</strong> upload an official box-score PDF, paste extracted text, enter a public box-score URL, or use a completed CSV template. Review the detected values, uncheck any line you want to leave unchanged, then save. Available period totals and game-result details are included when the source provides them.
    </div>

    <?= $this->Form->create(null, [
        'type' => 'file',
        'enctype' => 'multipart/form-data',
        'data-turbo' => 'false',
        'url' => ['action' => 'index', $game->id],
    ]) ?>
    <?= $this->Form->hidden('source_type', ['value' => $sourceType ?? '']) ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">1. Source File</h2>
        </div>
        <div class="card-body">
            <label for="pdf-file" class="form-label">Choose the official PDF</label>
            <input id="pdf-file" name="pdf_file" type="file" accept="application/pdf,.pdf" class="form-control">
            <div class="form-text">Upload the original NCAA LiveStats PDF, up to 20 MB. The server extracts the text temporarily and deletes the uploaded copy after previewing. Legacy Game Totals and visitor/home exports are supported.</div>
            <div class="border-top mt-3 pt-3">
                <label for="source-url" class="form-label">Or paste the public box-score URL</label>
                <input id="source-url" name="source_url" type="url" value="<?= h($sourceUrl ?? '') ?>" class="form-control" placeholder="https://goracers.com/sports/mens-basketball/stats/2021/...">
                <div class="form-text">Use a public goracers.com box-score page. The server fetches the HTML directly and reads the final team tables.</div>
            </div>
            <div class="border-top mt-4 pt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <label for="csv-file" class="form-label mb-0">Or choose a completed CSV template</label>
                    <a href="<?= $this->Url->build(['action' => 'csvTemplate', $game->id]) ?>" class="btn btn-outline-secondary btn-sm" data-turbo="false">
                        <i class="bi bi-download"></i> Download CSV Template
                    </a>
                </div>
                <input id="csv-file" name="csv_file" type="file" accept="text/csv,.csv" class="form-control mt-2">
                <div class="form-text">Use <code>player</code> rows for individual players, <code>totals</code> rows for final team stats, <code>period</code> rows for a team/opponent and period such as 1, 2, or OT, and <code>game</code> rows with <code>side=game</code> for attendance, officials, or period-score fields. Put the game field name in <code>field</code> and its value in <code>value</code>. Leave unused rows and stat columns blank; partial imports are accepted.</div>
            </div>
            <details class="mt-3">
                <summary>Use pasted text instead</summary>
                <label for="raw-text" class="form-label mt-2">Extracted PDF text</label>
                <textarea id="raw-text" name="raw_text" class="form-control font-monospace" rows="8"><?= h($rawText) ?></textarea>
                <div class="form-text">Paste extracted text from a PDF when the PDF upload is unavailable. Use the CSV template instead for image-only PDFs or manual entry.</div>
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
                            <th>Import</th>
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
                                    <input type="checkbox" name="team_rows[<?= (int)$index ?>][import]" value="1" checked aria-label="Import stats for <?= h($row['name']) ?>">
                                </td>
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
                                    <select id="team-roster-<?= (int)$index ?>" name="team_rows[<?= (int)$index ?>][team_season_roster_id]" class="form-select form-select-sm">
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
                            <th>Import</th>
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
                                <td><input type="checkbox" name="opponent_rows[<?= (int)$index ?>][import]" value="1" checked aria-label="Import stats for <?= h($row['name']) ?>"></td>
                                <td><input type="text" name="opponent_rows[<?= (int)$index ?>][jersey]" value="<?= h((string)$row['jersey']) ?>" class="form-control form-control-sm" style="min-width: 4rem;"></td>
                                <td><input type="text" name="opponent_rows[<?= (int)$index ?>][name]" value="<?= h((string)$row['name']) ?>" class="form-control form-control-sm" style="min-width: 10rem;"></td>
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
            <div class="card-header"><h2 class="h5 mb-0">4. Final team totals</h2></div>
            <div class="card-body">
                <p class="form-text">Uncheck a statistic to leave its existing value unchanged.</p>
                <div class="row g-2">
                    <?php foreach (['team' => $teamBox, 'opponent' => $opponentBox] as $side => $box) : ?>
                        <div class="col-lg-6">
                            <h3 class="h6 text-capitalize"><?= h($side) ?> totals</h3>
                            <div class="row g-2">
                                <?php foreach ($boxFields as $field) : ?>
                                    <div class="col-6 col-md-3">
                                        <?php $boxValue = $box[$field] ?? ''; ?>
                                        <?php $boxId = $side . '-box-' . $field; ?>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="<?= h($boxId) ?>-import" name="<?= h($side) ?>_box_selected[<?= h($field) ?>]" value="1" <?= $boxValue !== '' && $boxValue !== null ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="<?= h($boxId) ?>-import">Import <?= h($boxFieldLabels[$field] ?? $field) ?></label>
                                        </div>
                                        <input id="<?= h($side) ?>-box-<?= h($field) ?>" type="text" inputmode="decimal" name="<?= h($side) ?>_box[<?= h($field) ?>]" value="<?= h((string)($box[$field] ?? '')) ?>" class="form-control form-control-sm">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($periodBoxes !== []) : ?>
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">5. Period-by-period team totals</h2></div>
                <div class="card-body">
                    <p class="form-text">Each checked statistic updates only that team and period. Other period values remain unchanged.</p>
                    <div class="row g-3">
                        <?php foreach ($periodBoxes as $rowKey => $box) : ?>
                            <?php
                            [$periodSide, $periodCode] = explode('_', (string)$rowKey, 2);
                            $periodName = str_starts_with($periodCode, 'OT')
                                ? 'Overtime ' . (substr($periodCode, 2) !== '' ? substr($periodCode, 2) : '1')
                                : 'Period ' . $periodCode;
                            $periodSideName = $periodSide === 'team' ? $teamName : $opponentName;
                            ?>
                            <div class="col-lg-6">
                                <h3 class="h6"><?= h($periodName) ?> - <?= h($periodSideName) ?></h3>
                                <div class="row g-2">
                                    <?php foreach ($boxFields as $field) : ?>
                                        <?php $periodValue = $box[$field] ?? ''; ?>
                                        <div class="col-6 col-md-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="<?= h($rowKey . '-' . $field) ?>-import" name="period_boxes_selected[<?= h($rowKey) ?>][<?= h($field) ?>]" value="1" <?= $periodValue !== '' && $periodValue !== null ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="<?= h($rowKey . '-' . $field) ?>-import">Import <?= h($boxFieldLabels[$field] ?? $field) ?></label>
                                            </div>
                                            <input type="text" inputmode="decimal" name="period_boxes[<?= h($rowKey) ?>][<?= h($field) ?>]" value="<?= h((string)$periodValue) ?>" class="form-control form-control-sm" aria-label="<?= h($periodName . ' ' . $periodSideName . ' ' . ($boxFieldLabels[$field] ?? $field)) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $gameResultLabels = [];
        foreach ($gameResults as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ($key === 'attendance') {
                $gameResultLabels[$key] = 'Attendance';
            } elseif (preg_match('/^period_(\d+)_(team|opponent)$/', (string)$key, $matches) === 1) {
                $sideName = $matches[2] === 'team' ? $teamName : $opponentName;
                $gameResultLabels[$key] = 'Period ' . $matches[1] . ' - ' . $sideName . ' points';
            } elseif (preg_match('/^overtime_(\d+)_(team|opponent)$/', (string)$key, $matches) === 1) {
                $sideName = $matches[2] === 'team' ? $teamName : $opponentName;
                $gameResultLabels[$key] = 'Overtime ' . $matches[1] . ' - ' . $sideName . ' points';
            } elseif (preg_match('/^official_(\d+)$/', (string)$key, $matches) === 1) {
                $gameResultLabels[$key] = 'Official ' . $matches[1];
            }
        }
        ?>
        <?php if ($gameResultLabels !== []) : ?>
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">6. Game results</h2></div>
                <div class="card-body">
                    <p class="form-text">Select the attendance, period-score, or official fields to import. Unchecked fields stay unchanged.</p>
                    <div class="row g-2">
                        <?php foreach ($gameResultLabels as $key => $label) : ?>
                            <?php $resultId = 'game-result-' . $key; ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="<?= h($resultId) ?>-import" name="game_results_selected[<?= h($key) ?>]" value="1" checked>
                                    <label class="form-check-label" for="<?= h($resultId) ?>-import">Import <?= h($label) ?></label>
                                </div>
                                <input id="<?= h($resultId) ?>" type="<?= $key === 'attendance' || str_contains((string)$key, 'period_') || str_contains((string)$key, 'overtime_') ? 'number' : 'text' ?>" name="game_results[<?= h($key) ?>]" value="<?= h((string)$gameResults[$key]) ?>" class="form-control form-control-sm" <?= $key === 'attendance' || str_contains((string)$key, 'period_') || str_contains((string)$key, 'overtime_') ? 'min="0" step="1"' : '' ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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
