<?php
declare(strict_types=1);

/**
 * Blog Post View Frame Element
 *
 * @var \App\Model\Entity\BlogPost $post
 * @var \App\View\AppView $this
 */
?>
<turbo-frame id="blog-post-view" class="blog-expanded-frame" target="_top" data-controller="blog-interactions">
    <!-- Add standard Bootstrap 2-column wrappings to mimic news layout -->
    <div class="row">
        <div class="col-lg-8 pe-lg-4">
            <article class="blog-post-view p-4 rh-surface rounded mb-3 shadow-sm border-0" data-blog-post="<?= h($post->slug) ?>">
                <header class="d-flex flex-column gap-2 mb-4">
                    <h1 class="mb-0 fw-bold"><?= h($post->title) ?></h1>
                    <p class="text-muted mb-0">
                        <?php if ($post->published_at instanceof DateTimeInterface) : ?>
                            <time datetime="<?= h($post->published_at->format('Y-m-d')) ?>">
                                <?= h($post->published_at->format('F j, Y')) ?>
                            </time>
                        <?php else : ?>
                            <?= h($post->published_at ?? '') ?>
                        <?php endif; ?>
                    </p>
                </header>

                <?php if (!empty($post->hero_image_id)) : ?>
                <figure class="mb-4 text-center">
                    <?= $this->element('image_with_credit', [
                        'image' => $post->hero_image ?? null,
                        'imgContent' => $this->ImageServe->picture(
                            $post->hero_image_id,
                            ['profile' => 'blog_featured'],
                            [
                                'alt' => h($post->title),
                                'class' => 'img-fluid rounded shadow-sm',
                                'style' => 'object-fit: contain; max-height: 500px; width: 100%;',
                            ],
                        ),
                    ]) ?>
                </figure>
                <?php endif; ?>

                <div class="blog-content fs-5 lh-lg mb-5">
                    <?= $this->BlogContent->render((string)$post->body) ?>
                </div>

                <?php if (!empty($post->blog_tags)) : ?>
                <footer class="blog-tags mb-4">
                    <?php foreach ((array)$post->blog_tags as $tag) : ?>
                        <span class="blog-tag badge bg-secondary"><?= h($tag->name) ?></span>
                    <?php endforeach; ?>
                </footer>
                <?php endif; ?>

                <hr class="mb-4">

                <!-- Author Bio Box -->
                <?php if ($post->user) : ?>
                <div class="author-bio-box d-flex align-items-center gap-3 bg-light p-3 rounded mb-4 border">
                    <div class="author-avatar bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 60px; height: 60px; overflow: hidden;">
                        <?php if ($post->user->profile_image_id) : ?>
                            <?= $this->ImageServe->picture(
                                $post->user->profile_image_id,
                                ['profile' => 'roster_avatar'],
                                [
                                    'alt' => h($post->user->display_name ?: $post->user->username),
                                    'class' => 'img-fluid w-100 h-100 object-fit-cover',
                                ],
                            ) ?>
                        <?php else : ?>
                            <i class="bi bi-person-fill fs-3"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h5 class="h6 mb-1 fw-bold"><?= h($post->user->display_name ?: $post->user->username) ?></h5>
                        <?php if ($post->user->bio) : ?>
                            <p class="small text-muted mb-1"><?= h($post->user->bio) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($post->user->social_links)) : ?>
                            <?= $this->SocialLinks->render($post->user->social_links) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Next/Prev Navigation Block Placeholder -->
                <div class="post-navigation d-flex justify-content-between align-items-center border-top pt-4 mt-2">
                    <a href="<?= $this->Url->build(['controller' => 'Blog', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm" data-turbo-frame="_top">
                        <i class="bi bi-arrow-left"></i> Back to News
                    </a>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <?= $this->cell('BlogWidget::sidebar') ?>
        </div>
    </div>
</turbo-frame>
