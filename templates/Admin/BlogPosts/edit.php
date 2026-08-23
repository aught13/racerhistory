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
$existingHeroId = (int)($post->hero_image_id ?? 0);
$existingHeroUrl = $existingHeroId > 0 ? $this->ImageServe->url($existingHeroId, ['variant' => 'hero']) : '';
?>
<?php $this->start('css'); ?>
<?= $this->Html->css('blog-content') ?>
<?php $this->end(); ?>

<div class="container py-4" data-controller="blog-post-form" data-blog-post-form-existing-hero-id-value="<?= $existingHeroId ?>" data-blog-post-form-existing-hero-url-value="<?= h($existingHeroUrl) ?>" data-blog-post-form-images-upload-url-value="/admin/images/upload">
    <?= $this->Form->create($post, [
        'url' => isset($post->id) ? ['action' => 'edit', $post->id] : ['action' => 'add'],
        'class' => 'needs-validation',
        'novalidate' => true,
        'data-turbo-cache' => 'false',
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
                                'data-blog-post-form-target' => 'editor',
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
                    <?php if ($canPinPosts ?? false) : ?>
                        <?= $this->Form->control('is_pinned', [
                            'type' => 'checkbox',
                            'label' => ['text' => 'Pin this post', 'class' => 'form-check-label'],
                            'class' => 'form-check-input',
                            'div' => ['class' => 'form-check form-switch mb-3'],
                        ]) ?>
                        <?php if ($canManagePinSettings ?? false) : ?>
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
                        <?php endif; ?>
                    <?php elseif (!empty($post->is_pinned)) : ?>
                        <div class="alert alert-secondary py-2 small mb-3">This post is currently pinned. Your role can edit the post body, but not its pin settings.</div>
                    <?php endif; ?>
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
                    <?php if ($canManagePostOwner ?? false) : ?>
                        <?= $this->Form->control('user_id', [
                            'type' => 'select',
                            'options' => $users ?? [],
                            'empty' => 'Auto (original author)',
                            'label' => ['text' => 'Owner', 'class' => 'form-label'],
                            'class' => 'form-select mb-3',
                        ]) ?>
                    <?php else : ?>
                        <div class="mb-3">
                            <label class="form-label">Owner</label>
                            <div class="form-control-plaintext border rounded px-2 py-2 bg-body-tertiary"><?= h($postOwnerLabel ?? 'Unassigned') ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Hero Image</h3></div>
                <div class="card-body">
                    <?= $this->Form->control('hero_image_id', [
                        'type' => 'text',
                        'label' => ['text' => 'Image ID', 'class' => 'form-label'],
                        'id' => $heroFieldId,
                        'data-blog-post-form-target' => 'heroField',
                        'class' => 'form-control',
                        'placeholder' => 'Select image',
                    ]) ?>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-secondary flex-grow-1" data-bs-toggle="modal" data-bs-target="#<?= h($heroModalId) ?>">Select/Upload Image</button>
                        <button type="button" id="unset-hero-btn" class="btn btn-outline-danger" title="Remove hero image" style="display: none;" data-blog-post-form-target="unsetHeroButton">&times; Remove</button>
                    </div>
                    <a
                        id="hero-variant-btn"
                        data-blog-post-form-target="heroVariantButton"
                        class="btn btn-outline-primary w-100 mt-2"
                        href="#"
                        target="_blank"
                        rel="noopener"
                        style="display: none;"
                    >Edit Hero Crop</a>
                    <div id="hero-image-preview" class="mt-2" style="display: none;" data-blog-post-form-target="heroPreview">
                        <img src="" alt="Hero preview" class="img-fluid rounded border" style="max-height: 200px;">
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="h6 mb-0">Insert Inline Image</h3></div>
                <div class="card-body">
                    <input type="text" id="<?= h($inlineFieldId) ?>" class="form-control mb-2" placeholder="Image ID to insert" data-blog-post-form-target="inlineField">
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#<?= h($inlineModalId) ?>">Choose Image</button>
                    <div class="form-text">On selection, the image will be inserted at the cursor in the editor.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="h6 mb-0">Tags</h3></div>
                <div class="card-body">
                    <?= $this->element('Admin/tag_modal_trigger', [
                        'subject' => 'blogposts',
                        'subjectId' => $post->id ?? 0,
                        'currentTags' => $currentTags ?? [],
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
echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', ['block' => true]);
echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', ['block' => true]);
?>
