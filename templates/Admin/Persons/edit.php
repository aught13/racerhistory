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

                    <div class="row g-3">
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
                        <div class="col-md-6">
                            <label class="form-label">Birth Place</label>
                            <div class="input-group">
                                <input type="text" id="birth-place-search" class="form-control" placeholder="Search places..." autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#add-birth-place-modal" title="Add New Place"><i class="bi bi-plus-circle"></i> New</button>
                            </div>
                            <?= $this->Form->control('birth_place_id', ['type' => 'hidden', 'id' => 'birth-place-id-field']); ?>
                            <div id="birth-place-results" class="mt-1"></div>
                            <div id="birth-place-selected" class="small mt-1">
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
                                    <div id="person-image-preview" class="mt-2" style="display: none;">
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
echo $this->Html->script('/js/image-selector.js', ['block' => true]);

$previewQsJson = json_encode($this->ImageServe->query(['w' => 200, 'h' => 200, 'fit' => 'cover'])) ?: '""';
echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    const previewQs = {$previewQsJson};
    var el = document.getElementById('bio-editor');
    if (!el || typeof tinymce === 'undefined') { return; }
    tinymce.init({
        license_key: 'gpl',
        selector: '#bio-editor',
        menubar: false,
        plugins: 'image code lists advlist media preview quickbars save visualblocks visualchars',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | image media | code preview | save',
        quickbars_selection_toolbar: 'bold italic underline | quicklink blockquote | bullist numlist',
        image_title: true,
        automatic_uploads: true,
        images_upload_url: '/admin/images/upload',
        images_upload_credentials: true,
        convert_urls: false,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '/admin/images/upload');
                xhr.withCredentials = true;
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        progress(e.loaded / e.total * 100);
                    }
                };
                xhr.onload = function () {
                    if (xhr.status < 200 || xhr.status >= 300) { return reject('HTTP Error: ' + xhr.status); }
                    var raw = xhr.responseText;
                    var json;
                    try { json = JSON.parse(raw); } catch (err) {
                        console.error('TinyMCE upload invalid JSON response:', raw);
                        return reject('Invalid JSON');
                    }
                    if (!json.success || !json.image || !json.image.url) {
                        console.error('TinyMCE upload server response (error path):', json);
                        return reject(json.error || 'Upload failed');
                    }
                    resolve(json.image.url);
                };
                xhr.onerror = function () { reject('Image upload failed'); };
                var formData = new FormData();
                formData.append('upload', blobInfo.blob(), blobInfo.filename());
                var csrf = document.querySelector('meta[name="csrfToken"]');
                if (csrf) { xhr.setRequestHeader('X-CSRF-Token', csrf.getAttribute('content')); }
                xhr.send(formData);
            });
        }
    });

    // Person image preview handler
    const imageField = document.getElementById('person-image-field');
    const imagePreview = document.getElementById('person-image-preview');

    function updateImagePreview() {
        const imageId = imageField.value.trim();
        if (imageId && !isNaN(parseInt(imageId, 10))) {
            const previewImg = imagePreview.querySelector('img');
            previewImg.src = '/images/serve/' + imageId + previewQs + '&_ts=' + Date.now();
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
        }
    }

    // Listen for changes to the image field (from modal or manual entry)
    if (imageField) {
        imageField.addEventListener('change', updateImagePreview);

        // Show initial preview if image ID is set
        updateImagePreview();
    }
});
JS, ['block' => true]);

$placeSearchUrl = json_encode($this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'ajaxSearch']));
echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('birth-place-search');
    var resultsDiv = document.getElementById('birth-place-results');
    var selectedDiv = document.getElementById('birth-place-selected');
    var hiddenInput = document.getElementById('birth-place-id-field');
    if (!searchInput || !hiddenInput) return;
    var placeSearchUrl = {$placeSearchUrl};
    var debounce = null;
    function setSelected(id, text) {
        hiddenInput.value = id;
        if (selectedDiv) {
            selectedDiv.innerHTML = '<span class="badge bg-primary me-1">' + text + ' <button type="button" class="btn-close btn-close-white ms-1 clear-birth-place" aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
            selectedDiv.querySelector('.clear-birth-place').addEventListener('click', function() { hiddenInput.value = ''; selectedDiv.innerHTML = '<span class="text-muted fst-italic">None selected</span>'; });
        }
        if (resultsDiv) resultsDiv.innerHTML = '';
        searchInput.value = '';
    }
    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        var q = this.value.trim();
        if (q.length < 2) { if (resultsDiv) resultsDiv.innerHTML = ''; return; }
        debounce = setTimeout(function() {
            fetch(placeSearchUrl + '?q=' + encodeURIComponent(q), {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.results || !data.results.length) { resultsDiv.innerHTML = '<div class="text-muted small">No results</div>'; return; }
                    var html = '<div class="list-group list-group-flush" style="max-height:200px;overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,.15)">';
                    data.results.forEach(function(r) {
                        var label = r.place_city + (r.place_state ? ', ' + r.place_state : '');
                        html += '<button type="button" class="list-group-item list-group-item-action py-1 small" data-id="' + r.id + '" data-text="' + label.replace(/"/g,'&quot;') + '">' + label + '</button>';
                    });
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                    resultsDiv.querySelectorAll('button').forEach(function(btn) { btn.addEventListener('click', function() { setSelected(btn.dataset.id, btn.dataset.text); }); });
                })
                .catch(function() { resultsDiv.innerHTML = '<div class="text-danger small">Error</div>'; });
        }, 300);
    });
    // Clear button for pre-populated birth place
    var clearBtn = document.querySelector('.clear-birth-place');
    if (clearBtn) { clearBtn.addEventListener('click', function() { hiddenInput.value = ''; selectedDiv.innerHTML = '<span class="text-muted fst-italic">None selected</span>'; }); }
    document.addEventListener('click', function(e) { if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) { resultsDiv.innerHTML = ''; } });

    // Callback for popup_form after a new place is added
    window.handleBirthPlaceAdded = function(data) {
        if (data && data.place && data.place.id) {
            var label = (data.place.place_city || '') + (data.place.place_state ? ', ' + data.place.place_state : '');
            setSelected(data.place.id, label);
        }
    };
});
JS, ['block' => true]);
?>
