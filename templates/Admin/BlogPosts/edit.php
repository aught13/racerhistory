<?php
declare(strict_types=1);

/**
 * Admin Blog Post Edit/Add Template
 *
 * Provides a comprehensive blog post editor with:
 * - TinyMCE WYSIWYG editor with Bootstrap styling
 * - Hero image selection with cropping
 * - Inline image insertion with WebP support
 * - Tag management for categorization
 *
 * @var \App\Model\Entity\BlogPost $post
 * @var \App\View\AppView $this
 * @var mixed $currentTags
 * @var mixed $games
 * @var mixed $opponents
 * @var mixed $sites
 * @var mixed $sports
 * @var mixed $tagString
 * @var mixed $teamSeasons
 * @var mixed $teams
 */

$this->assign('title', isset($post->id) ? 'Edit Blog Post' : 'Add Blog Post');
$heroModalId = 'hero-image-selector';
$inlineModalId = 'inline-image-selector';
$heroFieldId = 'hero-image-field';
$inlineFieldId = 'inline-image-field';
$uploadContext = isset($post->id) ? ['type' => 'blogpost', 'id' => $post->id] : null;
?>
<?php $this->start('css'); ?>
<?= $this->Html->css('blog-content') ?>
<?php $this->end(); ?>

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
                                'placeholder' => 'Brief summary for listings (auto-generated from body if blank)',
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
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-secondary flex-grow-1" data-bs-toggle="modal" data-bs-target="#<?= h($heroModalId) ?>">Select/Upload Image</button>
                        <button type="button" id="unset-hero-btn" class="btn btn-outline-danger" title="Remove hero image" style="display: none;" data-action="unset-hero">&times; Remove</button>
                    </div>
                    <a
                        id="hero-variant-btn"
                        class="btn btn-outline-primary w-100 mt-2"
                        href="#"
                        target="_blank"
                        rel="noopener"
                        style="display: none;"
                    >Edit Hero Crop</a>
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

$existingHeroId = (int)($post->hero_image_id ?? 0);
$existingHeroUrl = $existingHeroId > 0 ? $this->ImageServe->url($existingHeroId, ['variant' => 'hero']) : '';
$existingHeroUrlJson = json_encode($existingHeroUrl) ?: "''";
?>

<?= $this->Html->scriptBlock(<<<JS
(function() {
    const heroFieldId = '{$heroFieldId}';
    const inlineFieldId = '{$inlineFieldId}';
    const existingHeroId = {$existingHeroId};
    const existingHeroUrl = {$existingHeroUrlJson};

    function withCacheBust(url) {
        if (!url) {
            return '';
        }

        return url + (url.indexOf('?') === -1 ? '?' : '&') + '_ts=' + Date.now();
    }

    // Destroy existing TinyMCE instances before reinitializing
    function destroyTinyMCE() {
        if (typeof tinymce !== 'undefined' && tinymce.get('body-editor')) {
            tinymce.get('body-editor').remove();
        }
    }

    function initBlogEditor() {
        // First destroy any existing instance
        destroyTinyMCE();

        const heroField = document.getElementById(heroFieldId);
        const heroPreview = document.getElementById('hero-image-preview');

        const unsetHeroBtn = document.getElementById('unset-hero-btn');
        const heroVariantBtn = document.getElementById('hero-variant-btn');

        function updateHeroVariantButton() {
            if (!heroField || !heroVariantBtn) return;

            const imageId = parseInt(heroField.value.trim(), 10);
            if (Number.isFinite(imageId) && imageId > 0) {
                heroVariantBtn.href = '/admin/images/crop-hero/' + imageId;
                heroVariantBtn.style.display = 'block';
            } else {
                heroVariantBtn.style.display = 'none';
            }
        }

        // Hero image preview handling
        function updateHeroPreview() {
            if (!heroField || !heroPreview) return;
            const val = (heroField.value || '').trim();
            const selectedUrl = heroField.dataset.selectedImageHeroUrl || heroField.dataset.selectedImageThumbnailUrl || heroField.dataset.selectedImageUrl || '';
            const previewUrl = selectedUrl || (parseInt(val, 10) === existingHeroId ? existingHeroUrl : '');
            if (val && !isNaN(parseInt(val, 10)) && previewUrl) {
                const img = heroPreview.querySelector('img');
                img.src = withCacheBust(previewUrl);
                heroPreview.style.display = 'block';
                if (unsetHeroBtn) unsetHeroBtn.style.display = 'inline-block';
            } else {
                heroPreview.style.display = 'none';
                if (unsetHeroBtn) unsetHeroBtn.style.display = 'none';
            }
        }
        heroField?.addEventListener('change', function () {
            updateHeroPreview();
            updateHeroVariantButton();
        });
        if (existingHeroId > 0 && heroField) {
            heroField.value = existingHeroId;
        }
        updateHeroPreview();
        updateHeroVariantButton();

        // Unset hero image
        unsetHeroBtn?.addEventListener('click', function () {
            if (heroField) heroField.value = '';
            updateHeroPreview();
            updateHeroVariantButton();
        });

        // Check if textarea exists before initializing
        const textArea = document.getElementById('body-editor');
        if (!textArea) return;

        // Initialize TinyMCE with Bootstrap configuration
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                license_key: 'gpl',
                selector: '#body-editor',
                menubar: true,
                menu: {
                    file: { title: 'File', items: 'preview | print' },
                    edit: { title: 'Edit', items: 'undo redo | cut copy paste | selectall | searchreplace' },
                    view: { title: 'View', items: 'visualblocks visualchars | fullscreen' },
                    insert: { title: 'Insert', items: 'image media table link | hr | charmap' },
                    format: { title: 'Format', items: 'bold italic underline strikethrough | formats blockformats fontformats fontsizes align | forecolor backcolor | removeformat' },
                    table: { title: 'Table', items: 'inserttable | cell row column | tableprops deletetable' },
                    help: { title: 'Help', items: 'help' }
                },
                min_height: 500,
                resize: true,
                statusbar: true,
                branding: false,

                // Plugins for comprehensive editing
                plugins: 'image code lists advlist media preview quickbars save visualblocks visualchars table link autolink searchreplace fullscreen wordcount help',

                // Main toolbar - WordPress-like arrangement
                toolbar: [
                    'undo redo | blocks styles | bold italic underline strikethrough | forecolor backcolor',
                    'alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | blockquote',
                    'link image media table | removeformat visualblocks | code fullscreen preview | help'
                ].join(' | '),

                // Quick selection toolbar
                quickbars_selection_toolbar: 'bold italic underline | quicklink blockquote | bullist numlist',
                quickbars_insert_toolbar: 'quickimage quicktable hr',

                // Block formats
                block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6; Blockquote=blockquote; Preformatted=pre',

                // Style formats for dropdown
                style_formats: [
                    {
                        title: 'Text Styles',
                        items: [
                            { title: 'Lead Paragraph', selector: 'p', classes: 'lead' },
                            { title: 'Small Text', inline: 'small' },
                            { title: 'Muted Text', selector: 'p,span', classes: 'text-muted' }
                        ]
                    },
                    {
                        title: 'Image Position',
                        items: [
                            { title: 'Float Left', selector: 'img,figure,picture', classes: 'img-float-left', styles: { float: 'left', margin: '0.5rem 1.5rem 1rem 0' } },
                            { title: 'Float Right', selector: 'img,figure,picture', classes: 'img-float-right', styles: { float: 'right', margin: '0.5rem 0 1rem 1.5rem' } },
                            { title: 'Center', selector: 'img,figure,picture', classes: 'img-center', styles: { display: 'block', margin: '1rem auto' } }
                        ]
                    }
                ],

                // Content styling - Bootstrap compatible
                content_css: '/css/blog-content.css',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 1.125rem; line-height: 1.8; padding: 1rem; max-width: 100%; } img { max-width: 100%; height: auto; border-radius: 6px; }',

                // Image handling
                image_title: true,
                automatic_uploads: true,
                images_upload_url: '/admin/images/upload',
                images_upload_credentials: true,
                images_reuse_filename: true,
                convert_urls: false,
                relative_urls: false,

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
                },

                // Table configuration - Bootstrap styles
                table_default_styles: { width: '100%' },
                table_class_list: [
                    { title: 'Default', value: '' },
                    { title: 'Striped', value: 'table table-striped' },
                    { title: 'Bordered', value: 'table table-bordered' },
                    { title: 'Hover', value: 'table table-hover' },
                    { title: 'Responsive', value: 'table-responsive' }
                ],
                table_responsive_width: true,

                // Link configuration
                link_assume_external_targets: true,
                link_default_target: '_blank',

                // Valid elements
                extended_valid_elements: 'img[class|src|srcset|sizes|alt|title|width|height|loading|style|data-*],picture[class|style],source[srcset|sizes|type|media],figure[class|style],figcaption[class|style],iframe[src|width|height|frameborder|allowfullscreen|class|style|title],video[src|controls|autoplay|loop|muted|poster|class|style|width|height]'
            });
        }

        // Inline image insertion with a direct stored image URL.
        const inlineField = document.getElementById(inlineFieldId);
        function insertInlineImage() {
            const val = inlineField?.value?.trim();
            if (!val || isNaN(parseInt(val, 10))) { return false; }

            const imageUrl = inlineField.dataset.selectedImageUrl || '';
            if (!imageUrl) { return false; }

            const editor = window.tinymce?.activeEditor;
            if (editor) {
                const html = '<picture>' +
                    '<img src="' + imageUrl + '" alt="" class="img-fluid" loading="lazy">' +
                    '</picture><p></p>';
                editor.insertContent(html);
                return true;
            }
            return false;
        }
        inlineField?.addEventListener('change', () => {
            if (insertInlineImage()) {
                inlineField.value = '';
                delete inlineField.dataset.selectedImageUrl;
                delete inlineField.dataset.selectedImageThumbnailUrl;
            }
        });
    }

    // Clean up TinyMCE before Turbo replaces content
    document.addEventListener('turbo:before-render', destroyTinyMCE);
    document.addEventListener('turbo:before-cache', destroyTinyMCE);

    // Initialize on DOMContentLoaded and turbo:load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBlogEditor);
    } else {
        initBlogEditor();
    }
    document.addEventListener('turbo:load', initBlogEditor);
})();
JS, ['block' => true]);
?>
