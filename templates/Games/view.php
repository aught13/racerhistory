<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Game $game
 * @var array|null $boxScore
 * @var array<int,\App\Model\Entity\Image> $images
 * @var array<int,\App\Model\Entity\BlogPost> $blogPosts
 */
$this->assign('title', 'Game Details');
?>
<div class="container py-4">
    <!-- Game Header -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= $this->Url->build(['controller' => 'Games', 'action' => 'index']) ?>">Games</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= h($game->game_date?->format('M j, Y')) ?>
                    </li>
                </ol>
            </nav>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <h3><?= h($game->team_season->team->team_name ?? 'Team') ?></h3>
                            <?php if (!empty($game->mur_rk)) : ?>
                                <p class="text-muted">#<?= h($game->mur_rk) ?></p>
                            <?php endif; ?>
                            <h1 class="display-3"><?= h($game->pts_mur ?? '-') ?></h1>
                            <?php if (!empty($game->result_flag)) : ?>
                                <span class="badge bg-<?= $game->result_flag === 'W' ? 'success' : ($game->result_flag === 'L' ? 'danger' : 'secondary') ?> fs-5">
                                    <?= h($game->result_flag) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-center">
                            <h3><?= h($game->opponent->opponent_name ?? 'Opponent') ?></h3>
                            <?php if (!empty($game->opp_rk)) : ?>
                                <p class="text-muted">#<?= h($game->opp_rk) ?></p>
                            <?php endif; ?>
                            <h1 class="display-3"><?= h($game->pts_opp ?? '-') ?></h1>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <p class="mb-1">
                            <strong><?= h($game->game_date?->format('l, F j, Y')) ?></strong>
                        </p>
                        <p class="mb-1">
                            <?= h($game->place_name ?? '') ?><?php if (!empty($game->place_state)) : ?>, <?= h($game->place_state) ?><?php endif; ?>
                        </p>
                        <?php if (!empty($game->site_name)) : ?>
                            <p class="mb-1 text-muted small"><?= h($game->site_name) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($game->game_type->type_name)) : ?>
                            <p class="mb-0">
                                <span class="badge bg-info"><?= h($game->game_type->type_name) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Box Score -->
    <?php if (!empty($boxScore)) : ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">Box Score</h3>

                <!-- Team Stats -->
                <?php if (!empty($boxScore['teamStats'])) : ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Team Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Team</th>
                                            <th>FG</th>
                                            <th>FG%</th>
                                            <th>3PT</th>
                                            <th>3PT%</th>
                                            <th>FT</th>
                                            <th>FT%</th>
                                            <th>REB</th>
                                            <th>AST</th>
                                            <th>TO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong><?= h($game->team_season->team->team_name ?? 'Team') ?></strong></td>
                                            <td><?= h($boxScore['teamStats']['fgm'] ?? 0) ?>-<?= h($boxScore['teamStats']['fga'] ?? 0) ?></td>
                                            <td><?= h($boxScore['teamStats']['fg_pct'] ?? '0.0') ?>%</td>
                                            <td><?= h($boxScore['teamStats']['fg3m'] ?? 0) ?>-<?= h($boxScore['teamStats']['fg3a'] ?? 0) ?></td>
                                            <td><?= h($boxScore['teamStats']['fg3_pct'] ?? '0.0') ?>%</td>
                                            <td><?= h($boxScore['teamStats']['ftm'] ?? 0) ?>-<?= h($boxScore['teamStats']['fta'] ?? 0) ?></td>
                                            <td><?= h($boxScore['teamStats']['ft_pct'] ?? '0.0') ?>%</td>
                                            <td><?= h($boxScore['teamStats']['reb'] ?? 0) ?></td>
                                            <td><?= h($boxScore['teamStats']['ast'] ?? 0) ?></td>
                                            <td><?= h($boxScore['teamStats']['tov'] ?? 0) ?></td>
                                        </tr>
                                        <?php if (!empty($boxScore['opponentStats'])) : ?>
                                            <tr>
                                                <td><strong><?= h($game->opponent->opponent_name ?? 'Opponent') ?></strong></td>
                                                <td><?= h($boxScore['opponentStats']['fgm'] ?? 0) ?>-<?= h($boxScore['opponentStats']['fga'] ?? 0) ?></td>
                                                <td><?= h($boxScore['opponentStats']['fg_pct'] ?? '0.0') ?>%</td>
                                                <td><?= h($boxScore['opponentStats']['fg3m'] ?? 0) ?>-<?= h($boxScore['opponentStats']['fg3a'] ?? 0) ?></td>
                                                <td><?= h($boxScore['opponentStats']['fg3_pct'] ?? '0.0') ?>%</td>
                                                <td><?= h($boxScore['opponentStats']['ftm'] ?? 0) ?>-<?= h($boxScore['opponentStats']['fta'] ?? 0) ?></td>
                                                <td><?= h($boxScore['opponentStats']['ft_pct'] ?? '0.0') ?>%</td>
                                                <td><?= h($boxScore['opponentStats']['reb'] ?? 0) ?></td>
                                                <td><?= h($boxScore['opponentStats']['ast'] ?? 0) ?></td>
                                                <td><?= h($boxScore['opponentStats']['tov'] ?? 0) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Player Stats -->
                <?php if (!empty($boxScore['playerStats'])) : ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0"><?= h($game->team_season->team->team_name ?? 'Team') ?> Player Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Player</th>
                                            <th>MIN</th>
                                            <th>PTS</th>
                                            <th>REB</th>
                                            <th>AST</th>
                                            <th>FG</th>
                                            <th>3PT</th>
                                            <th>FT</th>
                                            <th>STL</th>
                                            <th>BLK</th>
                                            <th>TO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($boxScore['playerStats'] as $stat) : ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= $this->Url->build(['controller' => 'People', 'action' => 'view', $stat['person_id'] ?? 0]) ?>">
                                                        <?= h($stat['player_name'] ?? 'Unknown') ?>
                                                    </a>
                                                </td>
                                                <td><?= h($stat['min'] ?? 0) ?></td>
                                                <td><strong><?= h($stat['pts'] ?? 0) ?></strong></td>
                                                <td><?= h($stat['reb'] ?? 0) ?></td>
                                                <td><?= h($stat['ast'] ?? 0) ?></td>
                                                <td><?= h($stat['fgm'] ?? 0) ?>-<?= h($stat['fga'] ?? 0) ?></td>
                                                <td><?= h($stat['fg3m'] ?? 0) ?>-<?= h($stat['fg3a'] ?? 0) ?></td>
                                                <td><?= h($stat['ftm'] ?? 0) ?>-<?= h($stat['fta'] ?? 0) ?></td>
                                                <td><?= h($stat['stl'] ?? 0) ?></td>
                                                <td><?= h($stat['blk'] ?? 0) ?></td>
                                                <td><?= h($stat['tov'] ?? 0) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Images and Stories -->
    <?php if (!empty($images) || !empty($blogPosts)) : ?>
        <div class="row">
            <?php if (!empty($images)) : ?>
                <div class="col-md-6 mb-4">
                    <h4 class="mb-3">Images</h4>
                    <div class="row g-2">
                        <?php foreach (array_slice($images, 0, 4) as $image) : ?>
                            <div class="col-6">
                                <img src="/images/serve/<?= h($image->id) ?>?w=300&h=300&fit=cover"
                                     class="img-fluid rounded"
                                     alt="<?= h($image->filename) ?>"
                                     loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($blogPosts)) : ?>
                <div class="col-md-6 mb-4">
                    <h4 class="mb-3">Related Stories</h4>
                    <div class="list-group">
                        <?php foreach ($blogPosts as $post) : ?>
                            <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'view', $post->slug]) ?>"
                               class="list-group-item list-group-item-action">
                                <h6 class="mb-1"><?= h($post->title) ?></h6>
                                <small class="text-muted"><?= h($post->published_at?->format('F j, Y')) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
