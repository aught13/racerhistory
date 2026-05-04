<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $tableSchema
 * @var \App\Model\Entity\SportStatRegistry $sportStatRegistry
 */
?>
<?php $this->assign('title', h($sportStatRegistry->display_name) . ' Configuration'); ?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $this->Url->build(['action' => 'index']) ?>">Sport Stats Registry</a></li>
                    <li class="breadcrumb-item active"><?= h($sportStatRegistry->display_name) ?></li>
                </ol>
            </nav>
            <h1 class="mb-3"><?= h($sportStatRegistry->display_name) ?></h1>
            <div class="d-flex gap-2 mb-3">
                <a href="<?= $this->Url->build([
                    'action' => 'edit', $sportStatRegistry->id,
                ]) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Configuration
                </a>
                <a href="<?= $this->Url->build([
                    'action' => 'index', $sportStatRegistry->sport_id,
                ]) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to
                    <?= h($sportStatRegistry->sport->sport_name) ?> Stat Tables
                </a>
                <?= $this->Form->postLink(
                    '<i class="bi bi-trash"></i> Delete',
                    ['action' => 'delete', $sportStatRegistry->id],
                    [
                        'confirm' => 'Are you sure you want to delete this configuration?',
                        'class' => 'btn btn-danger ms-auto',
                        'escape' => false,
                    ],
                ) ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Configuration Details</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th scope="row" class="w-25">Sport</th>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $sportStatRegistry->sport_id]) ?>">
                                    <?= h($sportStatRegistry->sport->sport_name) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Context</th>
                            <td><?= ucfirst(h($sportStatRegistry->context)) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Entity Type</th>
                            <td><?= ucfirst(h($sportStatRegistry->entity_type)) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Table Name</th>
                            <td><code><?= h($sportStatRegistry->table_name) ?></code></td>
                        </tr>
                        <tr>
                            <th scope="row">Primary Key</th>
                            <td><code><?= h($sportStatRegistry->primary_key ?: 'id') ?></code></td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>
                                <?php if ($sportStatRegistry->created instanceof DateTimeInterface) : ?>
                                    <?= h($sportStatRegistry->created->format('M j, Y g:i A')) ?>
                                <?php else : ?>
                                    <?= h($sportStatRegistry->created) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Modified</th>
                            <td>
                                <?php if ($sportStatRegistry->modified instanceof DateTimeInterface) : ?>
                                    <?= h($sportStatRegistry->modified->format('M j, Y g:i A')) ?>
                                <?php else : ?>
                                    <?= h($sportStatRegistry->modified) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Usage Information</h5>
                </div>
                <div class="card-body">
                    <p>
                        This configuration defines the mapping between database columns and
                        their display properties for the
                        <code><?= h($sportStatRegistry->table_name) ?></code> table.
                    </p>

                    <h6 class="mt-3">Service Access:</h6>
                    <pre class="bg-light p-3 code-snippet"><code>// Example code to access this configuration:
$sportConfigService = $this->getService(SportConfigService::class);
$tables = $sportConfigService->getStatTablesForSport(
    $sportId,
    '<?= h($sportStatRegistry->context) ?>',
    '<?= h($sportStatRegistry->entity_type) ?>',
);

// To get field mapping:
$mapping = $sportConfigService->getFieldMapping(
    $sportId,
    '<?= h($sportStatRegistry->table_name) ?>',
);</code></pre>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Field Mapping</h5>
                </div>
                <div class="card-body">
                    <?php
                    $fieldMapping = [];
                    if (!empty($sportStatRegistry->field_mapping)) {
                        $decodedMapping = json_decode($sportStatRegistry->field_mapping, true);
                        if (is_array($decodedMapping)) {
                            $fieldMapping = $decodedMapping;
                        }
                    }

                    if (empty($fieldMapping)) : ?>
                        <div class="alert alert-warning">
                            No field mappings have been defined for this table yet.
                            <a href="<?= $this->Url->build([
                                'action' => 'edit', $sportStatRegistry->id,
                            ]) ?>" class="btn btn-sm btn-warning ms-3">
                                <i class="bi bi-pencil"></i> Add Field Mappings
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Database Column</th>
                                        <th>Display Label</th>
                                        <th>Data Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fieldMapping as $column => $details) : ?>
                                    <tr>
                                        <td><code><?= h($column) ?></code></td>
                                        <td><?= h($details['label'] ?? $column) ?></td>
                                        <td>
                                            <?php
                                            $typeClass = 'badge bg-secondary';
                                            $typeName = 'Unknown';

                                            switch ($details['type'] ?? 'text') {
                                                case 'numeric':
                                                    $typeClass = 'badge bg-primary';
                                                    $typeName = 'Numeric';
                                                    break;
                                                case 'percentage':
                                                    $typeClass = 'badge bg-info';
                                                    $typeName = 'Percentage';
                                                    break;
                                                case 'text':
                                                    $typeClass = 'badge bg-success';
                                                    $typeName = 'Text';
                                                    break;
                                                case 'boolean':
                                                    $typeClass = 'badge bg-warning';
                                                    $typeName = 'Boolean';
                                                    break;
                                                case 'date':
                                                    $typeClass = 'badge bg-dark';
                                                    $typeName = 'Date';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $typeClass ?>"><?= $typeName ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Database Schema Information</h5>
                </div>
                <div class="card-body">
                    <p>
                        The following shows the database schema for the
                        <code><?= h($sportStatRegistry->table_name) ?></code> table:
                    </p>

                    <div class="table-responsive">
                        <?php if (!empty($tableSchema) && is_array($tableSchema)) : ?>
                            <table class="table table-sm table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Column</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Default</th>
                                        <th>Key</th>
                                        <th>Extra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableSchema as $column) : ?>
                                    <tr>
                                        <td><code><?= h($column['Field']) ?></code></td>
                                        <td><?= h($column['Type']) ?></td>
                                        <td><?= h($column['Null']) ?></td>
                                        <td><?= h($column['Default'] ?? '<em>NULL</em>') ?></td>
                                        <td><?= h($column['Key']) ?></td>
                                        <td><?= h($column['Extra']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <div class="alert alert-danger">
                                <h6 class="alert-heading">Table Not Found</h6>
                                <p>
                                    The specified table
                                    <code><?= h($sportStatRegistry->table_name) ?></code>
                                    could not be found in the database or is not accessible.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
