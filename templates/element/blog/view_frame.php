<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost $post */
?>
<turbo-frame id="blog-post-view-<?= h($post->slug) ?>" class="blog-expanded-frame" data-view-frame>
    <div class="blog-post-view p-4 rh-surface rounded mb-3" data-blog-post="<?= h($post->slug) ?>">
        <div class="blog-collapse-row mb-3">
            <button class="blog-collapse" type="button" aria-label="Collapse post">
                <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
            </button>
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <h1 class="mb-0"><?= h($post->title) ?></h1>
            <p class="text-muted mb-0">
                <?php if ($post->published_at instanceof \DateTimeInterface): ?>
                    <?= h($post->published_at->format('F j, Y')) ?>
                <?php else: ?>
                    <?= h($post->published_at ?? '') ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($post->hero_image_id)): ?>
        <div class="mb-4 text-center">
            <img src="/images/serve/<?= h($post->hero_image_id) ?>?w=1200&fit=contain" class="img-fluid rounded" alt="<?= h($post->title) ?>" style="object-fit: contain; max-height: 500px;">
        </div>
        <?php endif; ?>

        <div class="fs-5 lh-lg blog-content">
            <?= $this->Text->autoParagraph($post->body) ?>
        </div>

        <?php if (!empty($post->blog_tags)): ?>
        <div class="blog-tags">
            <?php foreach ((array)$post->blog_tags as $tag): ?>
                <span class="blog-tag"><?= h($tag->name) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function bindBlogCollapse() {
        const closeBtn = document.querySelector('.blog-collapse');
        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.dataset.bound = 'true';
            closeBtn.addEventListener('click', function() {
                const postContainer = this.closest('[data-blog-post]');
                const slug = postContainer ? postContainer.dataset.blogPost : '';
                if (slug && typeof window.loadBlogPost === 'function') {
                    window.loadBlogPost(slug);
                    return;
                }

                const viewFrame = this.closest('turbo-frame[data-view-frame]');
                if (!viewFrame) {
                    return;
                }

                const containerFrame = viewFrame.closest('turbo-frame[id^="blog-post-"]');
                if (containerFrame) {
                    containerFrame.dataset.expanded = 'false';
                    containerFrame.classList.remove('blog-post-expanded');
                    const featured = containerFrame.querySelector('.blog-featured');
                    if (featured) featured.style.display = '';
                    const listItem = containerFrame.querySelector('.blog-list-item');
                    if (listItem) listItem.style.display = '';
                }

                viewFrame.innerHTML = '';
            });
        }
    }

    function markBlogExpanded() {
        const postView = document.querySelector('.blog-post-view[data-blog-post]');
        if (!postView) {
            return;
        }

        const slug = postView.dataset.blogPost;
        if (!slug) {
            return;
        }

        const containerFrame = document.getElementById('blog-post-' + slug);
        if (!containerFrame) {
            return;
        }

        containerFrame.dataset.expanded = 'true';
        containerFrame.classList.add('blog-post-expanded');
        const featured = containerFrame.querySelector('.blog-featured');
        if (featured) featured.style.display = 'none';
        const listItem = containerFrame.querySelector('.blog-list-item');
        if (listItem) listItem.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', bindBlogCollapse);
    document.addEventListener('turbo:load', bindBlogCollapse);
    document.addEventListener('DOMContentLoaded', markBlogExpanded);
    document.addEventListener('turbo:frame-load', markBlogExpanded);
    </script>
</turbo-frame>
