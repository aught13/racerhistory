<?php $this->assign('title', 'Add Person'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Add New Person</h2>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($person, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'add'],
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
                                    // Ensure we keep the textarea markup simple for JS replacement
                                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                                ],
                            ]); ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <?= $this->Form->button('Save Person', ['class' => 'btn btn-success']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'index']) ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
echo $this->Html->script('/js/image-selector.js', ['block' => true]);

// Note: For add action, we can't use person-specific tagging since the person doesn't exist yet
// Modal for general image selection (no auto-tagging on add)
$modalId = 'person-image-selector';
$targetFieldId = 'person-image-field';
$tagFilter = null; // No filter since person doesn't exist yet
$uploadContext = null; // No auto-tagging on add
$aspectRatio = 1; // Square aspect ratio for profile images
echo $this->element('Admin/image_selector_modal', compact('modalId', 'targetFieldId', 'tagFilter', 'uploadContext', 'aspectRatio'));

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
        setup: function (editor) {
            editor.on('BeforeUpload', function(e){ /* hook if needed */ });
        },
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
                xhr.setRequestHeader('X-CSRF-Token', document.querySelector('meta[name="csrfToken"]').getAttribute('content'));
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
?>

