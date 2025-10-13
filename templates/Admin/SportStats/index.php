<?php $this->assign('title', 'Manage Sport Stat Configurations'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Sport Statistics Registry</h1>
            <p class="text-muted mb-3">
                Configure the database tables and field mappings used for different sports' statistics.
                Each sport can have multiple stat tables for various contexts (game, season) and entity types (team, player, opponent).
            </p>
            <div class="d-flex gap-2">
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'SportStats', 'action' => 'add']) ?>"
                   class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Stat Configuration
                </a>

                <?php if (isset($sport) && $sport) : ?>
                    <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'SportStats', 'action' => 'index']) ?>"
                       class="btn btn-outline-secondary mb-3">
                        <i class="bi bi-funnel"></i> Show All Sports
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!isset($sport) && !isset($sportId)) : ?>
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Filter by Sport</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($sports as $id => $name) : ?>
                            <a href="<?= $this->Url->build(['action' => 'index', $id]) ?>" class="list-group-item list-group-item-action">
                                <?= h($name) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($sport) && $sport) : ?>
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0"><?= h($sport->sport_name) ?> Stat Tables</h2>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $sport->id]) ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Sport
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col">
            <?php if (!empty($statRegistries->toArray())) : ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th><?= $this->Paginator->sort('sport_id', 'Sport') ?></th>
                                <th><?= $this->Paginator->sort('context', 'Context') ?></th>
                                <th><?= $this->Paginator->sort('entity_type', 'Entity Type') ?></th>
                                <th><?= $this->Paginator->sort('display_name', 'Display Name') ?></th>
                                <th><?= $this->Paginator->sort('table_name', 'Table Name') ?></th>
                                <th>Field Mapping</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statRegistries as $registry) : ?>
                            <tr>
                                <td>
                                    <a href="<?= $this->Url->build(['action' => 'index', $registry->sport_id]) ?>">
                                        <?= h($registry->sport->sport_name) ?>
                                    </a>
                                </td>
                                <td><?= ucfirst(h($registry->context)) ?></td>
                                <td><?= ucfirst(h($registry->entity_type)) ?></td>
                                <td><?= h($registry->display_name) ?></td>
                                <td><code><?= h($registry->table_name) ?></code></td>
                                <td>
                                    <?php
                                    $mappedFields = [];
                                    if (!empty($registry->field_mapping)) {
                                        $mapping = json_decode($registry->field_mapping, true);
                                        if (is_array($mapping)) {
                                            $mappedFields = $mapping;
                                        }
                                    }
                                    echo count($mappedFields) . ' field(s)';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= $this->Url->build(['action' => 'view', $registry->id]) ?>" class="btn btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= $this->Url->build(['action' => 'edit', $registry->id]) ?>" class="btn btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?= $this->Form->postLink(
                                            '<i class="bi bi-trash"></i>',
                                            ['action' => 'delete', $registry->id],
                                            [
                                                'confirm' => 'Are you sure you want to delete this stat configuration?',
                                                'class' => 'btn btn-danger',
                                                'escape' => false,
                                            ]
                                        ) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?= $this->Paginator->first('<< First') ?>
                            <?= $this->Paginator->prev('< Previous') ?>
                            <?= $this->Paginator->numbers() ?>
                            <?= $this->Paginator->next('Next >') ?>
                            <?= $this->Paginator->last('Last >>') ?>
                        </ul>
                    </nav>
                </div>
                <p class="text-center text-muted">
                    <?= $this->Paginator->counter('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total') ?>
                </p>
            <?php else : ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Stat Configurations Found</h4>
                    <?php if (isset($sport)) : ?>
                        <p>No stat tables have been configured for <?= h($sport->sport_name) ?> yet.</p>
                        <hr>
                        <p class="mb-0">
                            <a href="<?= $this->Url->build(['action' => 'add', $sport->id]) ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add First Stat Configuration
                            </a>
                        </p>
                    <?php else : ?>
                        <p>No sport stat tables have been configured in the system.</p>
                        <hr>
                        <p class="mb-0">
                            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add First Stat Configuration
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
