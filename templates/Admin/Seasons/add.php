<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Season $season
 */

$this->assign('title', 'Add Season'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a
                            href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>">Seasons</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Add Season</li>
                </ol>
            </nav>
            <h1 class="mb-3">Add New Season</h1>
            <p class="text-muted">
                Create a new season period. Seasons organize teams into competitive time periods.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Season Information</h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($season, ['novalidate' => true]) ?>

                    <div class="mb-3">
                        <label for="start-year" class="form-label">Start Year *</label>
                        <?= $this->Form->control('start', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'start-year',
                            'placeholder' => 'e.g., 2023',
                        ]) ?>
                        <div class="form-text">The starting year of the season (e.g., 2023 for 2023-2024 season).</div>
                    </div>

                    <div class="mb-3">
                        <label for="end-year" class="form-label">End Year *</label>
                        <?= $this->Form->control('end', [
                            'type' => 'text',
                            'class' => 'form-control',
                            'label' => false,
                            'required' => true,
                            'id' => 'end-year',
                            'placeholder' => 'e.g., 2024',
                        ]) ?>
                        <div class="form-text">The ending year of the season (e.g., 2024 for 2023-2024 season).</div>
                    </div>

                    <div class="d-flex gap-2">
                        <?= $this->Form->button(__('Save Season'), [
                            'type' => 'submit',
                            'class' => 'btn btn-success',
                        ]) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Season Guidelines</h4>
                </div>
                <div class="card-body">
                    <h5>Season Format</h5>
                    <p class="small text-muted">
                        Seasons typically span academic years, like "2023-2024" for the 2023-2024 school year.
                    </p>

                    <h5>Examples</h5>
                    <ul class="small text-muted">
                        <li>Start: 2023, End: 2024</li>
                        <li>Start: 2022, End: 2023</li>
                        <li>Start: 2024, End: 2025</li>
                    </ul>

                    <h5>Requirements</h5>
                    <ul class="small text-muted">
                        <li>Both start and end years are required</li>
                        <li>Years should be sequential (end typically follows start)</li>
                        <li>Each season becomes available for team assignment</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-populate end year when start year is entered
    $('#start-year').on('blur', function() {
    var startYear = parseInt($(this).val(), 10);
        var endYearField = $('#end-year');

        if (startYear && !endYearField.val()) {
            endYearField.val(startYear + 1);
        }
    });
});
</script>

<!-- Hidden form to generate FormProtection tokens for AJAX requests -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Seasons', 'action' => 'ajaxAdd'],
        'id' => 'hidden-season-form',
    ]) ?>
    <?= $this->Form->control('start', ['type' => 'text']) ?>
    <?= $this->Form->control('end', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>
