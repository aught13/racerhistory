<?php
/**
 * Element: sport_specific_fields
 * Expects: $eavTemplate (array), $eav (array) optionally, $legacyMappedEav (array) optionally, $sportName (string)
 *
 * @var \App\View\AppView $this
 * @var array $eav
 * @var mixed $eavTemplate
 * @var array $legacyMappedEav
 * @var mixed $sportName
 */
?>
<div>
    <h5>Sport-Specific Details<?= !empty($sportName) ? ' (' . h($sportName) . ')' : '' ?></h5>
    <?php
    $fieldsByGroup = [];
    foreach ($eavTemplate as $field) {
        $group = $field['field_group'] ?? 'general';
        $fieldsByGroup[$group][] = $field;
    }
    ?>

    <?php foreach ($fieldsByGroup as $groupName => $fields) : ?>
        <div class="card mt-2">
            <div class="card-header">
                <h6 class="mb-0"><?= h(ucfirst($groupName)) ?></h6>
            </div>
            <div class="card-body">
                <?php
                $fieldsPerRow = 4;
                $chunks = array_chunk($fields, $fieldsPerRow);
                ?>
                <?php foreach ($chunks as $fieldChunk) : ?>
                    <div class="row g-3 mb-2">
                        <?php foreach ($fieldChunk as $field) : ?>
                            <div class="col-md-<?= 12 / min(count($fieldChunk), $fieldsPerRow) ?>">
                                <?php
                                $fieldName = $field['field_name'];
                                $displayLabel = $field['display_label'] ?? '';
                                $fieldType = $field['field_type'] ?? 'text';
                                $currentValue = $legacyMappedEav[$fieldName] ?? $eav[$fieldName] ?? $field['default_value'] ?? '';

                                $options = [
                                    'label' => $displayLabel,
                                    'class' => 'form-control',
                                    'value' => $currentValue,
                                ];
                                if ($fieldType === 'number') {
                                    $options['type'] = 'number';
                                    if (isset($field['min'])) {
                                        $options['min'] = $field['min'];
                                    }
                                    if (isset($field['max'])) {
                                        $options['max'] = $field['max'];
                                    }
                                }
                                ?>
                                <?= $this->Form->control($fieldName, $options) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
