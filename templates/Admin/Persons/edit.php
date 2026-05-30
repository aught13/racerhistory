<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Person $person
 */
$initialImageId = !empty($person->person_image) ? (string)(int)$person->person_image : '';
$initialPreviewUrl = $initialImageId !== '' ? $this->ImageServe->url((int)$initialImageId, ['variant' => 'thumb']) : '';
?>
<?php $this->assign('title', 'Edit Person'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Edit Person</h2>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($person, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id],
                        'class' => 'needs-validation',
                        'novalidate' => true,
                    ]) ?>
                    <?php $this->Form->unlockField('birth_place_id'); ?>

                    <div class="row g-3" data-controller="person-form" data-person-form-initial-image-id-value="<?= h($initialImageId) ?>" data-person-form-initial-preview-url-value="<?= h($initialPreviewUrl) ?>" data-person-form-images-upload-url-value="/admin/images/upload">
                        <div class="col-md-6">
                            <?= $this->Form->control('first', ['class' => 'form-control', 'label' => ['text' => 'First Name', 'class' => 'form-label'], 'maxlength' => 30]); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('last', ['class' => 'form-control', 'label' => ['text' => 'Last Name', 'class' => 'form-label'], 'maxlength' => 30]); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('display', ['class' => 'form-control', 'label' => ['text' => 'Display Name', 'class' => 'form-label'], 'maxlength' => 162]); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('full', ['class' => 'form-control', 'label' => ['text' => 'Full Name', 'class' => 'form-label'], 'maxlength' => 162]); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('birth', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Birth Date', 'class' => 'form-label']]); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('death', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Death Date', 'class' => 'form-label']]); ?>
                        </div>
                        <div class="col-md-6" data-controller="place-search" data-place-search-search-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch'])) ?>">
                            <label class="form-label">Birth Place</label>
                            <div class="input-group">
                                <input type="text" id="birth-place-search" class="form-control" placeholder="Search places..." autocomplete="off" data-place-search-target="input" data-action="input->place-search#search">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#add-birth-place-modal" title="Add New Place"><i class="bi bi-plus-circle"></i> New</button>
                            </div>
                            <?= $this->Form->control('birth_place_id', ['type' => 'hidden', 'id' => 'birth-place-id-field', 'data-place-search-target' => 'hidden']); ?>
                            <div id="birth-place-results" class="mt-1" data-place-search-target="results"></div>
                            <div id="birth-place-selected" class="small mt-1" data-place-search-target="selected">
                                <?php if (!empty($person->birth_place)) : ?>
                                    <span class="badge bg-primary me-1"><?= h($person->birth_place->place_city . ($person->birth_place->place_state ? ', ' . $person->birth_place->place_state : '')) ?>
                                        <button type="button" class="btn-close btn-close-white ms-1 clear-birth-place" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button>
                                    </span>
                                <?php else : ?>
                                    <span class="text-muted fst-italic">None selected</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->control('person_previous', ['class' => 'form-control', 'label' => ['text' => 'Previous School', 'class' => 'form-label'], 'maxlength' => 162]); ?>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Person Image</label>
                            <div class="row">
                                <div class="col-md-8">
                                    <?= $this->Form->control('person_image', [
                                        'class' => 'form-control',
                                        'label' => false,
                                        'placeholder' => 'Image ID',
                                        'id' => 'person-image-field',
                                        'data-person-form-target' => 'imageField',
                                    ]); ?>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-secondary form-control" data-bs-toggle="modal" data-bs-target="#person-image-selector">
                                        Select/Upload Image
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div id="person-image-preview" class="mt-2" style="display: none;" data-person-form-target="imagePreview">
                                        <img src="" alt="Profile Image Preview" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12"> <?= $this->Form->control('bio', [
                                'type' => 'textarea',
                                'rows' => 8,
                                'class' => 'form-control',
                                'id' => 'bio-editor',
                                'data-person-form-target' => 'bioEditor',
                                'label' => ['text' => 'Biography', 'class' => 'form-label'],
                                'templates' => [
                                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                                ],
                            ]); ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <?= $this->Form->button('Update Person', ['class' => 'btn btn-success']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>

                    <div class="mt-3">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                            data-bs-target="#confirm-delete-modal"
                            data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'delete', $person->id]) ?>"
                            data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>"
                            data-item-type="person">Delete Person</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>

<!-- Hidden form for FormProtection tokens (place ajaxAdd endpoint) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd'],
        'id' => 'hidden-birth-place-form',
    ]) ?>
    <?= $this->Form->control('place_country', ['type' => 'text']) ?>
    <?= $this->Form->control('place_city', ['type' => 'text']) ?>
    <?= $this->Form->control('place_state', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>

<?= $this->element('Admin/popup_form', [
    'popupId' => 'add-birth-place-modal',
    'title' => 'Add New Place',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxAdd']),
    'hiddenFormId' => 'hidden-birth-place-form',
    'successCallback' => 'handleBirthPlaceAdded',
    'fields' => [
        ['name' => 'place_country', 'type' => 'text', 'label' => 'Country (ISO 3166 alpha-3)', 'required' => true],
        ['name' => 'place_city', 'type' => 'text', 'label' => 'Locality (city, town, or village)', 'required' => true],
        ['name' => 'place_state', 'type' => 'text', 'label' => 'Subdivision (state, province, or region)'],
    ],
]) ?>

<?php
// Image selector modal for person images
$modalId = 'person-image-selector';
$targetFieldId = 'person-image-field';
$tagFilter = 'person-' . $person->id;
$uploadContext = ['type' => 'person', 'id' => $person->id];
$aspectRatio = 1; // Square aspect ratio for profile images
echo $this->element('Admin/image_selector_modal', compact('modalId', 'targetFieldId', 'tagFilter', 'uploadContext', 'aspectRatio'));
?>

<?php
echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
?>
