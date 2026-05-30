<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $sports
 * @var object $statRegistry
 */
?>
<?php $this->assign('title', 'Edit Sport Stat Configuration'); ?>
<div class="container py-4" data-controller="field-mapping">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Edit Sport Stat Configuration</h1>
            <p class="text-muted mb-3">
                Update the configuration for a sport statistics table. You can modify the display name,
                field mappings and other properties.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <?= $this->Form->create($statRegistry, ['class' => 'row g-3']) ?>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('sport_id', 'Sport', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('sport_id', [
                                'options' => $sports,
                                'class' => 'form-select',
                                'empty' => '-- Select Sport --',
                                'required' => true,
                                'label' => false,
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('context', 'Context', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('context', [
                                'options' => [
                                    'game' => 'Game',
                                    'season' => 'Season',
                                    'career' => 'Career',
                                ],
                                'class' => 'form-select',
                                'empty' => '-- Select Context --',
                                'required' => true,
                                'label' => false,
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('entity_type', 'Entity Type', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('entity_type', [
                                'options' => [
                                    'team' => 'Team',
                                    'player' => 'Player',
                                    'opponent' => 'Opponent',
                                ],
                                'class' => 'form-select',
                                'empty' => '-- Select Entity Type --',
                                'required' => true,
                                'label' => false,
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('display_name', 'Display Name', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('display_name', [
                                'class' => 'form-control',
                                'placeholder' => 'e.g., "Basketball Game Stats"',
                                'required' => true,
                                'label' => false,
                            ]) ?>
                            <div class="form-text">A human-readable name for this stat table</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('table_name', 'Database Table Name', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('table_name', [
                                'class' => 'form-control',
                                'placeholder' => 'e.g., "stat_basket_games"',
                                'required' => true,
                                'label' => false,
                                'readonly' => !empty($statRegistry->table_name), // Don't allow changing table name if it's already set
                            ]) ?>
                            <div class="form-text">The actual database table name (without prefix)</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <?= $this->Form->label('primary_key', 'Primary Key Column', ['class' => 'form-label']) ?>
                            <?= $this->Form->control('primary_key', [
                                'class' => 'form-control',
                                'placeholder' => 'e.g., "id"',
                                'required' => true,
                                'label' => false,
                                'value' => $statRegistry->primary_key ?: 'id',
                            ]) ?>
                            <div class="form-text">The primary key column name in this table</div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="mb-0">Field Mapping</h5>
                                <div class="form-text">Define what each column in the table represents</div>
                            </div>
                            <div class="card-body">
                                <div id="field-mapping-container" class="mb-3" data-field-mapping-target="container">
                                    <?php
                                    $fieldMapping = [];
                                    if (!empty($statRegistry->field_mapping)) {
                                        $decodedMapping = json_decode($statRegistry->field_mapping, true);
                                        if (is_array($decodedMapping)) {
                                            $fieldMapping = $decodedMapping;
                                        }
                                    }

                                    if (empty($fieldMapping)) :
                                        ?>
                                    <div class="row g-3 mb-3 field-mapping-row">
                                        <div class="col-md-4">
                                            <input type="text" name="field_keys[]"
                                                   class="form-control"
                                                   placeholder="Database Column" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="field_labels[]"
                                                   class="form-control"
                                                   placeholder="Display Label" required>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="field_types[]" class="form-select">
                                                <option value="numeric">Numeric</option>
                                                <option value="percentage">Percentage</option>
                                                <option value="text">Text</option>
                                                <option value="boolean">Boolean</option>
                                                <option value="date">Date</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button"
                                                    class="btn btn-danger remove-field"
                                                    disabled>
                                                <i class="bi bi-dash-circle"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php else : ?>
                                        <?php foreach ($fieldMapping as $key => $field) : ?>
                                        <div class="row g-3 mb-3 field-mapping-row">
                                            <div class="col-md-4">
                                                <input type="text" name="field_keys[]"
                                                       class="form-control"
                                                       placeholder="Database Column"
                                                       value="<?= h($key) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" name="field_labels[]"
                                                       class="form-control"
                                                       placeholder="Display Label"
                                                       value="<?= h($field['label'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="field_types[]" class="form-select">
                                                    <option value="numeric"
                                                        <?= ($field['type'] ?? '') === 'numeric' ? 'selected' : '' ?>>
                                                        Numeric
                                                    </option>
                                                    <option value="percentage"
                                                        <?= ($field['type'] ?? '') === 'percentage' ? 'selected' : '' ?>>
                                                        Percentage
                                                    </option>
                                                    <option value="text"
                                                        <?= ($field['type'] ?? '') === 'text' ? 'selected' : '' ?>>
                                                        Text
                                                    </option>
                                                    <option value="boolean"
                                                        <?= ($field['type'] ?? '') === 'boolean' ? 'selected' : '' ?>>
                                                        Boolean
                                                    </option>
                                                    <option value="date"
                                                        <?= ($field['type'] ?? '') === 'date' ? 'selected' : '' ?>>
                                                        Date
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button"
                                                        class="btn btn-danger remove-field">
                                                    <i class="bi bi-dash-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add-field" class="btn btn-secondary" data-field-mapping-target="addButton">
                                    <i class="bi bi-plus-circle"></i> Add Field
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="d-flex gap-2">
                            <?= $this->Form->button(
                                'Save Changes',
                                ['class' => 'btn btn-primary'],
                            ) ?>
                            <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
                            <?= $this->Form->postLink(
                                'Delete Configuration',
                                ['action' => 'delete', $statRegistry->id],
                                [
                                    'confirm' => 'Are you sure you want to delete this stat configuration? '
                                        . 'This action cannot be undone.',
                                    'class' => 'btn btn-danger ms-auto',
                                ],
                            ) ?>
                        </div>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

