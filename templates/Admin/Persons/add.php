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
                        'novalidate' => true
                    ]) ?>

                    <div class="row g-3">
                        <div class="col-md-6"> <?= $this->Form->control('first', ['class' => 'form-control', 'label' => ['text' => 'First Name', 'class' => 'form-label'], 'maxlength' => 30]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('last', ['class' => 'form-control', 'label' => ['text' => 'Last Name', 'class' => 'form-label'], 'maxlength' => 30]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('display', ['class' => 'form-control', 'label' => ['text' => 'Display Name', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('full', ['class' => 'form-control', 'label' => ['text' => 'Full Name', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('birth', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Birth Date', 'class' => 'form-label']]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('death', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Death Date', 'class' => 'form-label']]); ?> </div>
                        <div class="col-12"> <?= $this->Form->control('person_image', ['class' => 'form-control', 'label' => ['text' => 'Image Path', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-12"> <?= $this->Form->control('bio', [
                                'type' => 'textarea',
                                'rows' => 8,
                                'class' => 'form-control',
                                'id' => 'bio-editor',
                                'label' => ['text' => 'Biography', 'class' => 'form-label'],
                                'templates' => [
                                    // Ensure we keep the textarea markup simple for JS replacement
                                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>'
                                ]
                            ]); ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <?= $this->Form->button('Save Person', ['class' => 'btn btn-success']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
<?php
// Load self-hosted TinyMCE (installed via composer tinymce/tinymce) and initialize.
// We expect the TinyMCE distribution to be published under /js/tinymce/ (see deployment notes).
echo $this->Html->script('/js/tinymce/tinymce.min.js', ['block' => true]);
echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('bio-editor');
    if (!el || typeof tinymce === 'undefined') { return; }
    tinymce.init({
        selector: '#bio-editor',
        menubar: false,
        plugins: 'image code lists liststyles media preview quickbars save visualblocks visualchars',
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
                    var json;
                    try { json = JSON.parse(xhr.responseText); } catch (err) { return reject('Invalid JSON'); }
                    if (!json.success || !json.image || !json.image.url) { return reject(json.error || 'Upload failed'); }
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
});
JS, ['block' => true]);
?>
</div>
