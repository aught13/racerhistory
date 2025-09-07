<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|string[] $sports
 */
?>
<div class="modal fade" id="add-person-modal" tabindex="-1" aria-labelledby="addPersonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPersonModalLabel">Add New Person</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= $this->Form->create(null, ['id' => 'add-person-form', 'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'ajaxAdd']]) ?>
                <fieldset>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->control('first', ['class' => 'form-control', 'label' => 'First Name', 'required' => true]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('last', ['class' => 'form-control', 'label' => 'Last Name', 'required' => true]) ?>
                        </div>
                    </div>
                    <?= $this->Form->control('display', ['class' => 'form-control', 'label' => 'Display Name']) ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->control('birthdate', ['class' => 'form-control', 'type' => 'date', 'label' => 'Birthdate', 'empty' => true]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('gender', ['class' => 'form-select', 'options' => ['Male' => 'Male', 'Female' => 'Female'], 'empty' => 'Select Gender']) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <?= $this->Form->control('hometown', ['class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $this->Form->control('home_state', ['class' => 'form-control', 'label' => 'Home State']) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $this->Form->control('home_country', ['class' => 'form-control', 'label' => 'Home Country']) ?>
                        </div>
                    </div>
                    <?= $this->Form->control('sport_id', ['options' => $sports, 'class' => 'form-select', 'empty' => 'Select Sport']) ?>
                    <?= $this->Form->control('bio', ['class' => 'form-control', 'type' => 'textarea', 'rows' => 3]) ?>
                </fieldset>
                <?= $this->Form->end() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="add-person-form">Save Person</button>
            </div>
        </div>
    </div>
</div>
