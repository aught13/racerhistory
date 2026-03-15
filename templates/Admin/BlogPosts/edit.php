<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost $post */

$this->assign('title', isset($post->id) ? 'Edit Blog Post' : 'Add Blog Post');
$previewQsJson = json_encode($this->ImageServe->query(['w' => 300, 'h' => 300, 'fit' => 'cover'])) ?: '""';
$heroModalId = 'hero-image-selector';
$inlineModalId = 'inline-image-selector';
$heroFieldId = 'hero-image-field';
$inlineFieldId = 'inline-image-field';
$uploadContext = isset($post->id) ? ['type' => 'blogpost', 'id' => $post->id] : null;
?>
<div class="container py-4">
    <?= $this->Form->create($post, [
        'url' => isset($post->id) ? ['action' => 'edit', $post->id] : ['action' => 'add'],
        'class' => 'needs-validation',
        'novalidate' => true,
    ]) ?>
    <?php $this->Form->unlockField('is_published'); ?>
    <div class="row">
        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-header"><h2 class="h5 mb-0">Post Details</h2></div>
                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <?= $this->Form->control('title', [
                                'label' => ['text' => 'Title', 'class' => 'form-label'],
                                'class' => 'form-control',
                                'maxlength' => 190,
                            ]) ?>
                        </div>
                        <div class="col-md-4">
                            <?= $this->Form->control('slug', [
                                'label' => ['text' => 'Slug', 'class' => 'form-label'],
                                'class' => 'form-control',
                                'maxlength' => 190,
                                'placeholder' => 'auto-generated if blank',
                            ]) ?>
                        </div>
                        <div class="col-12">
                            <?= $this->Form->control('excerpt', [
                                'type' => 'textarea',
                                'rows' => 3,
                                'label' => ['text' => 'Excerpt', 'class' => 'form-label'],
                                'class' => 'form-control',
                            ]) ?>
                        </div>
                        <div class="col-12">
                            <?= $this->Form->control('body', [
                                'type' => 'textarea',
                                'rows' => 12,
                                'label' => ['text' => 'Body', 'class' => 'form-label'],
                                'class' => 'form-control',
                                'id' => 'body-editor',
                                'templates' => [
                                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
                                ],
                            ]) ?>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                        <?= $this->Form->button('Save Post', ['class' => 'btn btn-success']) ?>
                        <?php if (isset($post->id)) : ?>
                            <a class="btn btn-outline-secondary" href="<?= $this->Url->build(['action' => 'index']) ?>">Back</a>
                        <?php else : ?>
                            <a class="btn btn-outline-secondary" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Publish</h3></div>
                <div class="card-body">
                    <?= $this->Form->control('is_published', [
                        'type' => 'checkbox',
                        'label' => ['text' => 'Make this post publicly visible', 'class' => 'form-check-label'],
                        'class' => 'form-check-input',
                        'div' => ['class' => 'form-check form-switch mb-3'],
                    ]) ?>
                    <?= $this->Form->control('is_pinned', [
                        'type' => 'checkbox',
                        'label' => ['text' => 'Pin this post', 'class' => 'form-check-label'],
                        'class' => 'form-check-input',
                        'div' => ['class' => 'form-check form-switch mb-3'],
                    ]) ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <?= $this->Form->control('pinned_rank', [
                                'type' => 'number',
                                'label' => ['text' => 'Pin Rank', 'class' => 'form-label'],
                                'class' => 'form-control',
                                'placeholder' => 'Higher ranks first',
                                'min' => 0,
                            ]) ?>
                        </div>
                        <div class="col-6">
                            <?= $this->Form->control('pinned_until', [
                                'type' => 'datetime',
                                'label' => ['text' => 'Pin Until', 'class' => 'form-label'],
                                'class' => 'form-control',
                                'empty' => true,
                            ]) ?>
                        </div>
                    </div>
                    <div class="form-text mb-3">Published posts are visible on the public blog. Uncheck to keep the post a draft.</div>
                    <?php if (!empty($post->slug) && ($post->is_published ?? false)) :
                        $viewUrl = '/blog/' . rawurlencode((string)$post->slug);
                    ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?= $this->Html->link('View published post', $viewUrl, [
                                'class' => 'btn btn-sm btn-outline-primary',
                                'target' => '_blank',
                                'rel' => 'noopener',
                            ]) ?>
                            <span class="text-muted small align-self-center">Opens in a new tab</span>
                        </div>
                    <?php endif; ?>
                    <?= $this->Form->control('published_at', [
                        'type' => 'datetime',
                        'label' => ['text' => 'Publish At', 'class' => 'form-label'],
                        'class' => 'form-control',
                        'empty' => true,
                    ]) ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Hero Image</h3></div>
                <div class="card-body">
                    <?= $this->Form->control('hero_image_id', [
                        'type' => 'text',
                        'label' => ['text' => 'Image ID', 'class' => 'form-label'],
                        'id' => $heroFieldId,
                        'class' => 'form-control',
                        'placeholder' => 'Select image',
                    ]) ?>
                    <button type="button" class="btn btn-secondary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#<?= h($heroModalId) ?>">Select/Upload Image</button>
                    <div id="hero-image-preview" class="mt-2" style="display: none;">
                        <img src="" alt="Hero preview" class="img-fluid rounded border" style="max-height: 200px;">
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Insert Inline Image</h3></div>
                <div class="card-body">
                    <input type="text" id="<?= h($inlineFieldId) ?>" class="form-control mb-2" placeholder="Image ID to insert">
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#<?= h($inlineModalId) ?>">Choose Image</button>
                    <div class="form-text">On selection, the image will be inserted at the cursor in the editor.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="h6 mb-0">Tags</h3></div>
                <div class="card-body">
                    <?= $this->element('Admin/tag_selection', [
                        'teams' => $teams,
                        'teamSeasons' => $teamSeasons,
                        'games' => $games,
                        'sites' => $sites,
                        'opponents' => $opponents,
                        'sports' => $sports,
                        'currentTags' => $currentTags,
                        'tagString' => $tagString ?? '',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<?php
$tagSelectionOptions = compact('teams', 'teamSeasons', 'games', 'sites', 'opponents', 'sports');
?>
<?= $this->element('Admin/image_selector_modal', [
    'modalId' => $heroModalId,
    'targetFieldId' => $heroFieldId,
    'tagFilter' => null,
    'uploadContext' => $uploadContext,
    'aspectRatio' => 16 / 9,
    'tagSelectionOptions' => $tagSelectionOptions,
]) ?>

<?= $this->element('Admin/image_selector_modal', [
    'modalId' => $inlineModalId,
    'targetFieldId' => $inlineFieldId,
    'tagFilter' => null,
    'uploadContext' => $uploadContext,
    'aspectRatio' => null,
    'tagSelectionOptions' => $tagSelectionOptions,
]) ?>

<?php
echo $this->Html->script('/js/tinymce/tinymce.min.js?v=1', ['block' => true]);
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
echo $this->Html->script('/js/image-selector.js', ['block' => true]);

$previewVar = $previewQsJson;
$existingHeroId = (int)($post->hero_image_id ?? 0);
$selectedRosterId = (int)($selectedRosterId ?? 0);
?>

<?= $this->Html->scriptBlock(<<<JS
(function() {
    document.addEventListener('DOMContentLoaded', function () {
        const previewQs = {$previewVar};
        const heroField = document.getElementById('{$heroFieldId}');
        const heroPreview = document.getElementById('hero-image-preview');
        function updateHeroPreview() {
            if (!heroField || !heroPreview) return;
            const val = (heroField.value || '').trim();
            if (val && !isNaN(parseInt(val, 10))) {
                const img = heroPreview.querySelector('img');
                img.src = '/images/serve/' + val + previewQs + '&_ts=' + Date.now();
                heroPreview.style.display = 'block';
            } else {
                heroPreview.style.display = 'none';
            }
        }
        heroField?.addEventListener('change', updateHeroPreview);
        if ({$existingHeroId} > 0 && heroField) { heroField.value = {$existingHeroId}; }
        updateHeroPreview();

        // TinyMCE init (match person edit)
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                license_key: 'gpl',
                selector: '#body-editor',
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
                            if (e.lengthComputable) { progress(e.loaded / e.total * 100); }
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
        }

        // Inline image insertion when selector sets value
        const inlineField = document.getElementById('{$inlineFieldId}');
        function insertInlineImage() {
            const val = inlineField?.value?.trim();
            if (!val || isNaN(parseInt(val, 10))) { return false; }
            const url = '/images/serve/' + val + '?w=800&fit=contain&_ts=' + Date.now();
            const editor = window.tinymce?.activeEditor;
            if (editor) {
                editor.insertContent('<p><img src="' + url + '" alt="" /></p>');
                return true;
            }
            return false;
        }
        inlineField?.addEventListener('change', () => {
            if (insertInlineImage()) {
                inlineField.value = '';
            }
        });

    });
})();
JS, ['block' => true]);
?>
