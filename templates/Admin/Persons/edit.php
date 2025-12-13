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
                        <div class="col-12 mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <?= $this->Form->control('person_image', [
                                        'class' => 'form-control',
                                        'label' => ['text' => 'Image ID', 'class' => 'form-label'],
                                        'maxlength' => 162,
                                        'id' => 'person-image-field',
                                    ]); ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-secondary form-control" id="select-person-image">
                                        Select Image
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div id="person-image-preview" class="mt-2" style="display: none;">
                                        <img src="" alt="Profile Image Preview" class="img-thumbnail" style="max-height: 150px;">
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

<?php
echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);

$previewQsJson = json_encode($this->ImageServe->query(['w' => 150, 'h' => 150, 'fit' => 'cover'])) ?: '""';
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

    // Person image selector
    const selectImageBtn = document.getElementById('select-person-image');
    const imageField = document.getElementById('person-image-field');
    const imagePreview = document.getElementById('person-image-preview');

    if (selectImageBtn && imageField) {
        selectImageBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Create and use a file input to trigger the browser's file picker
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function() {
                if (!input.files || !input.files[0]) return;

                const file = input.files[0];
                const formData = new FormData();
                formData.append('upload', file);

                // Show loading state
                selectImageBtn.disabled = true;
                selectImageBtn.textContent = 'Uploading...';

                // Use TinyMCE's upload handler (reusing the same endpoint)
                fetch('/admin/images/upload', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrfToken"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.image) {
                        alert('Upload failed: ' + (data.error || 'Unknown error'));
                        return;
                    }

                    // Store the image ID in the person_image field
                    imageField.value = data.image.id;

                    // Update the preview
                    const previewImg = imagePreview.querySelector('img');
                    previewImg.src = '/images/serve/' + data.image.id + previewQs + '&_ts=' + Date.now();
                    imagePreview.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error uploading image:', error);
                    alert('Upload failed: ' + error.message);
                })
                .finally(() => {
                    // Reset button state
                    selectImageBtn.disabled = false;
                    selectImageBtn.textContent = 'Select Image';
                });
            };
            input.click();
        });

        // Show preview if image ID is already set
        if (imageField.value) {
            const imageId = imageField.value.trim();
            if (imageId && !isNaN(parseInt(imageId, 10))) {
                const previewImg = imagePreview.querySelector('img');
                previewImg.src = '/images/serve/' + imageId + previewQs;
                imagePreview.style.display = 'block';
            }
        }
    }
});
JS, ['block' => true]);
?>
