<?php $this->assign('title', 'Game Details'); ?>
<div class="col-md-12" style="min-height: 500px;">
    <div class="row">
        <span class="h3 col-xl-6 text-center text-nowrap text-break">
            <?= h($game->team_season->team->team_description ?? '') ?> <?= h($game->team_season->season->start ?? '') ?>-<?= h($game->team_season->season->end ?? '') ?>
        </span>
        <span class="h3 col-xl-1 text-center text-nowrap">Vs</span>
        <span class="h3 col-xl-5 text-center text-nowrap">
            <?= h($game->opponent->opponent_name ?? '') ?>
        </span>
        <span class="h3 col-sm-12 text-center text-nowrap">
            <?= h((new \DateTime($game->game_date))->format('l, F jS, Y')) ?>
        </span>
        <hr>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <table class="table text-center">
                    <thead>
                        <tr>
                            <th class="text-center h2"><?= h($game->team_season->team->team_name ?? 'Team') ?></th>
                            <th></th>
                            <th class="text-center h2"><?= h($game->opponent->opponent_name ?? 'Opponent') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size: 40px; font-weight: bold;"><?= h($game->pts_mur ?? '') ?></td>
                            <td></td>
                            <td style="font-size: 40px; font-weight: normal;"><?= h($game->pts_opp ?? '') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4">
                <table class="table table-condensed table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <?php
                            // Determine number of periods from eav template or fallback
                            $periods = 2;
                            foreach ($eav as $k => $v) {
                                if (preg_match('/^period_(\d+)_team$/', $k, $m)) {
                                    $periods = max($periods, (int)$m[1]);
                                }
                            }
                            for ($i = 1; $i <= $periods; $i++) {
                                echo '<th>' . $i . '</th>';
                            }
                            ?>
                            <th>F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>MURRAY</td>
                            <?php for ($i = 1; $i <= $periods; $i++): ?>
                                <td><?= h($eav['period_' . $i . '_team'] ?? '') ?></td>
                            <?php endfor; ?>
                            <td style="font-weight: bold;"><?= h($game->pts_mur ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><?= h($game->opponent->opponent_abbr ?? 'Opponent') ?></td>
                            <?php for ($i = 1; $i <= $periods; $i++): ?>
                                <td><?= h($eav['period_' . $i . '_opponent'] ?? '') ?></td>
                            <?php endfor; ?>
                            <td style="font-weight: normal;"><?= h($game->pts_opp ?? '') ?></td>
                        </tr>
                    </tbody>
                </table>
                <span class=""><?= h($game->game_type->game_type_name ?? '') ?></span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-lg-9 h4">
                        <?= h(($game->site->place->place_city ?? '') . ' ' . ($game->site->site_name ?? '') ) ?>
                    </div>
                    <div class="col-lg-3">
                        Attendance: <?= h($eav['attendance'] ?? '') ?>
                    </div>
                </div>
                <div>
                    Referees: <?= h($eav['official_1'] ?? '') ?><?= !empty($eav['official_2']) ? ', ' . h($eav['official_2']) : '' ?><?= !empty($eav['official_3']) ? ', ' . h($eav['official_3']) : '' ?>
                </div>
                <div>
                    <span><?= h($game->notes ?? '') ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div>
                    <span>Game Time: <?= h($game->game_time ?? '') ?></span>
                </div>
            </div>
        </div>
        <hr>
        <div class="row"><!-- Murray Stats --></div>
        <div class="row"><!-- Opponent Stats --></div>
        <hr>
        <div class="btn-group">
            <button type="button" class="btn" onclick="history.back()">Back</button>
        </div>
    </div>
</div>
