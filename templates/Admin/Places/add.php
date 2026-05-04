<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Place $place
 */
?>
<?php $this->assign('title', 'Add Place'); ?>
<div class="container py-4">
    <h1 class="mb-3">Add Place</h1>
    <?= $this->Form->create($place) ?>
    <div class="row g-3">
        <div class="col-md-4"><?= $this->Form->control('place_country', ['class' => 'form-control', 'label' => 'Country (ISO 3166 alpha-3)', 'maxlength' => 3, 'required' => true]) ?></div>
        <div class="col-md-4"><?= $this->Form->control('place_city', ['class' => 'form-control', 'label' => 'Locality (city, town, or village)', 'required' => true]) ?></div>
        <div class="col-md-4"><?= $this->Form->control('place_state', ['class' => 'form-control', 'label' => 'Subdivision (state, province, or region)']) ?></div>
    </div>
    <div class="mt-3">
        <a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save</button>
    </div>
    <?= $this->Form->end() ?>
</div>
