<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $opponentsList
 * @var mixed $places
 * @var \App\Model\Entity\Opponent $opponent
 */
?>
<?php $this->assign('title', 'Add Opponent'); ?>
<div class="container py-4" data-controller="opponent-name-preview">
    <h1 class="mb-3">Add Opponent</h1>
    <?= $this->Form->create($opponent) ?>
    <div class="row">
        <div class="col-md-8">
            <?= $this->Form->control('opponent_name', [
                'label' => 'Official Opponent Name',
                'class' => 'form-control',
                'required' => true,
                'data-opponent-name-preview-target' => 'opponentName',
                'data-action' => 'input->opponent-name-preview#update change->opponent-name-preview#update',
            ]) ?>
            <div class="form-text">Enter the official/full name used in Full Opponent Name.</div>

            <?= $this->Form->control('opponent_mascot', [
                'label' => 'Mascot / Nickname',
                'class' => 'form-control',
                'data-opponent-name-preview-target' => 'opponentMascot',
                'data-action' => 'input->opponent-name-preview#update change->opponent-name-preview#update',
            ]) ?>
            <div class="form-text">Enter the mascot or nickname appended to the opponent name variants.</div>

            <?= $this->Form->control('opponent_short', [
                'label' => 'Short Opponent Name',
                'class' => 'form-control',
                'maxlength' => 30,
                'data-opponent-name-preview-target' => 'opponentShort',
                'data-action' => 'input->opponent-name-preview#update change->opponent-name-preview#update',
            ]) ?>
            <div class="form-text">Enter the short name used with the mascot in the default display (maximum 30 characters).</div>

            <?= $this->Form->control('opponent_abbr', [
                'label' => 'Scorebug Abbreviation',
                'class' => 'form-control',
                'maxlength' => 30,
            ]) ?>
            <div class="form-text">Enter the abbreviation used on score displays (maximum 30 characters).</div>

            <?= $this->Form->control('place_id', [
                'type' => 'select',
                'label' => 'Location',
                'options' => $places,
                'empty' => 'Choose...',
                'class' => 'form-select',
            ]) ?>
            <div class="form-text">Select the opponent's location to help distinguish similar records.</div>

            <?= $this->Form->control('opponent_current', [
                'type' => 'select',
                'label' => 'Current Opponent (if renamed)',
                'options' => $opponentsList,
                'empty' => 'None',
                'class' => 'form-select',
                'default' => null,
            ]) ?>
            <div class="form-text">Choose the current record only if this opponent has been renamed; otherwise leave None selected.</div>

            <div class="mt-3">
                <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Display Previews</h5>
                </div>
                <div class="card-body">
                    <p><strong>Full Opponent Name:</strong><br>
                        <span data-opponent-name-preview-target="fullOpponentName"><?= h($opponent->getFullDisplayName()) ?></span>
                    </p>
                    <p><strong>Opponent Name (default):</strong><br>
                        <span data-opponent-name-preview-target="defaultOpponentName"><?= h($opponent->getDisplayName()) ?></span>
                    </p>
                    <p class="mb-0"><strong>Opponent:</strong><br>
                        <span data-opponent-name-preview-target="opponent"><?= h($opponent->getMascotDisplayName()) ?></span>
                    </p>
                    <small class="text-muted">
                        Existing records are not rewritten automatically. Update the short name and mascot as needed; previews reflect the current fields.
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>
