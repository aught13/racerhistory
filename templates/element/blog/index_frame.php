<?php
declare(strict_types=1);
/** @var \App\Model\Entity\BlogPost[] $posts */
/** @var \App\Model\Entity\BlogPost[] $paginatedPosts */
/** @var int $page */
/** @var int $limit */
/** @var int $total */

$page = $page ?? 1;
$limit = $limit ?? 10;
$total = $total ?? count($posts ?? []);
$paginatedPosts = $paginatedPosts ?? $posts ?? [];
$hasMore = ($page * $limit) < $total;
$featured = null;
$listPosts = $paginatedPosts;
if ($page === 1 && !empty($posts)) {
    $featured = $posts[0];
    $listPosts = array_slice($paginatedPosts, 1);
}
?>
<turbo-frame id="blog">
    <div class="container py-4">
        <?php if (empty($paginatedPosts) && $page === 1): ?>
            <div class="alert alert-info mb-0">No posts yet.</div>
        <?php else: ?>
            <!-- Featured Hero Post (only on page 1) -->
            <?php if ($page === 1 && $featured): ?>
            <turbo-frame id="blog-post-<?= h($featured->slug) ?>" class="blog-featured-frame mb-5 pb-4 border-bottom">
                <!-- Main featured view: title → hero image → blurb (shown by default) -->
                <div class="blog-featured cursor-pointer" data-blog-post="<?= h($featured->slug) ?>" style="cursor: pointer;">
                    <h1 class="h2 mb-2 blog-hero-title"><?= h($featured->title) ?></h1>
                    <p class="text-muted small mb-3">
                        <?php if ($featured->published_at instanceof \DateTimeInterface): ?>
                            <time datetime="<?= h($featured->published_at->format('Y-m-d')) ?>">
                                <?= h($featured->published_at->format('F j, Y')) ?>
                            </time>
                        <?php else: ?>
                            <?= h($featured->published_at ?? '') ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($featured->hero_image_id)): ?>
                    <div class="blog-featured-hero mb-3">
                        <?= $this->ImageServe->responsivePicture(
                            $featured->hero_image_id,
                            [600, 900, 1200],
                            ['fit' => 'cover', 'h' => 720],
                            [
                                'alt' => h($featured->title),
                                'class' => 'img-fluid rounded blog-hero-image',
                                'sizes' => '(max-width: 991px) 100vw, 100%',
                            ]
                        ) ?>
                    </div>
                    <?php endif; ?>
                    <p class="lead mb-0 blog-hero-excerpt"><?= h($featured->excerpt ?: mb_substr(strip_tags((string)$featured->body), 0, 220) . '...') ?></p>
                </div>
                <!-- Thumb view: shown when another post is expanded (initially hidden) -->
                <div class="blog-featured-as-list cursor-pointer d-none d-flex gap-3 align-items-start" data-blog-post="<?= h($featured->slug) ?>" style="cursor: pointer;">
                    <?php if (!empty($featured->hero_image_id)): ?>
                    <figure style="flex-shrink: 0; width: 120px; height: 90px; margin: 0;">
                        <?= $this->ImageServe->picture(
                            $featured->hero_image_id,
                            ['w' => 200, 'h' => 150, 'fit' => 'cover'],
                            [
                                'alt' => h($featured->title),
                                'class' => 'img-fluid rounded',
                                'style' => 'object-fit: cover; width: 100%; height: 100%;',
                            ]
                        ) ?>
                    </figure>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h2 class="h6 mb-1"><?= h($featured->title) ?></h2>
                        <p class="text-muted small mb-2">
                            <?php if ($featured->published_at instanceof \DateTimeInterface): ?>
                                <time datetime="<?= h($featured->published_at->format('Y-m-d')) ?>">
                                    <?= h($featured->published_at->format('M j, Y')) ?>
                                </time>
                            <?php else: ?>
                                <?= h($featured->published_at ?? '') ?>
                            <?php endif; ?>
                        </p>
                        <p class="small mb-0 blog-list-excerpt"><?= h($featured->excerpt ?: mb_substr(strip_tags((string)$featured->body), 0, 120) . '...') ?></p>
                    </div>
                </div>
                <turbo-frame id="blog-post-view-<?= h($featured->slug) ?>" data-view-frame></turbo-frame>
            </turbo-frame>

            <?php endif; ?>

            <!-- Paginated Posts List -->
            <div class="blog-list" id="blog-list">
                <?= $this->element('blog/list_items', ['paginatedPosts' => $listPosts, 'page' => $page, 'limit' => $limit, 'total' => $total]) ?>
            </div>

            <!-- Load More Trigger -->
            <?php if ($hasMore): ?>
            <div id="load-more-trigger" class="text-center py-4">
                <button id="load-more-btn" class="btn btn-outline-secondary" data-page="<?= $page + 1 ?>" data-limit="<?= $limit ?>">
                    Load More Stories
                </button>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <style>
    .blog-hero-image {
        width: 100%;
        max-height: 60vh;
        object-fit: cover;
    }
    .blog-list-item-frame {
        max-height: 250px;
        overflow: hidden;
    }
    .blog-list-item {
        transition: background-color 0.2s ease;
    }
    .blog-list-item:hover {
        background-color: var(--rh-surface);
    }
    .blog-featured {
        transition: opacity 0.2s ease;
    }
    .blog-featured:hover {
        opacity: 0.9;
    }
    .blog-featured-as-list:hover {
        background-color: var(--rh-surface);
    }
    .cursor-pointer {
        cursor: pointer;
    }
    @media (max-width: 768px) {
        .blog-list-item-frame {
            max-height: none;
        }
        .blog-list-item img {
            display: none;
        }
        .blog-list-excerpt {
            display: none;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setupBlogInteractions();
        setupInfiniteScroll();
    });

    function setupBlogInteractions() {
        const blogFrame = document.querySelector('turbo-frame#blog');
        if (!blogFrame) return;

        // Handle featured post click (main hero view)
        const featured = blogFrame.querySelector('.blog-featured');
        if (featured && featured.dataset.blogClickBound !== 'true') {
            featured.dataset.blogClickBound = 'true';
            featured.addEventListener('click', function() {
                const slug = this.dataset.blogPost;
                if (slug) {
                    loadBlogPost(slug);
                }
            });
        }

        // Handle featured post click (thumb/list view shown when another post is expanded)
        const featuredAsList = blogFrame.querySelector('.blog-featured-as-list');
        if (featuredAsList && featuredAsList.dataset.blogClickBound !== 'true') {
            featuredAsList.dataset.blogClickBound = 'true';
            featuredAsList.addEventListener('click', function() {
                const slug = this.dataset.blogPost;
                if (slug) {
                    loadBlogPost(slug);
                }
            });
        }

        // Handle list items click
        const listItems = blogFrame.querySelectorAll('.blog-list-item');
        listItems.forEach(item => {
            if (item.dataset.blogClickBound === 'true') {
                return;
            }
            item.dataset.blogClickBound = 'true';
            item.addEventListener('click', function() {
                const slug = this.dataset.blogPost;
                if (slug) {
                    loadBlogPost(slug);
                }
            });
        });
    }

    function setupInfiniteScroll() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreTrigger = document.getElementById('load-more-trigger');

        if (!loadMoreBtn || !loadMoreTrigger) return;

        if (loadMoreBtn.dataset.infiniteScrollBound === 'true') {
            fillViewportIfNeeded();

            return;
        }
        loadMoreBtn.dataset.infiniteScrollBound = 'true';

        // Setup intersection observer for infinite scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadMoreBtn.click();
                }
            });
        }, {
            rootMargin: '100px'
        });

        observer.observe(loadMoreTrigger);

        // Handle load more button click
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page);
            const limit = parseInt(this.dataset.limit);

            const url = '<?= $this->Url->build(['action' => 'index']) ?>?page=' + page + '&limit=' + limit;

            // Fetch the next page and append items
            fetch(url, {
                headers: {
                    'Turbo-Frame': 'blog',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary container to parse the response
                const temp = document.createElement('div');
                temp.innerHTML = html;

                // Extract list items from the response
                const newItems = temp.querySelectorAll('.blog-list-item-frame');
                const blogList = document.getElementById('blog-list');

                if (newItems.length > 0) {
                    // Append new items
                    newItems.forEach(item => {
                        const cloned = item.cloneNode(true);
                        const clickable = cloned.querySelector('.blog-list-item');
                        if (clickable) {
                            clickable.addEventListener('click', function() {
                                const slug = this.dataset.blogPost;
                                if (slug) {
                                    loadBlogPost(slug);
                                }
                            });
                        }
                        blogList.appendChild(cloned);
                    });

                    // Check if there are more pages
                    const hasMore = temp.querySelector('#load-more-trigger');
                    if (hasMore) {
                        const newButton = temp.querySelector('#load-more-btn');
                        if (newButton) {
                            loadMoreBtn.dataset.page = newButton.dataset.page;
                            setupInfiniteScroll();
                        }
                    } else {
                        // No more pages, remove load more
                        loadMoreTrigger.remove();
                    }

                    // Ensure the list fills the viewport plus a couple of items
                    fillViewportIfNeeded();
                }
            })
            .catch(error => console.error('Error loading more posts:', error));
        });

        fillViewportIfNeeded();
    }

    function fillViewportIfNeeded() {
        const blogList = document.getElementById('blog-list');
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (!blogList || !loadMoreBtn) return;
        const threshold = window.innerHeight * 1.2;
        if (blogList.offsetHeight < threshold) {
            loadMoreBtn.click();
        }
    }

    function setExpandedState(frame, expanded) {
        frame.dataset.expanded = expanded ? 'true' : 'false';
        frame.classList.toggle('blog-post-expanded', expanded);
        const featured = frame.querySelector('.blog-featured');
        if (featured) featured.style.display = expanded ? 'none' : '';
        // Always hide the thumb view on explicit state changes; collapseFeaturedToThumb manages showing it
        const featuredAsList = frame.querySelector('.blog-featured-as-list');
        if (featuredAsList) featuredAsList.classList.add('d-none');
        const listItem = frame.querySelector('.blog-list-item');
        if (listItem) listItem.style.display = expanded ? 'none' : '';
        if (!expanded) {
            const viewFrame = frame.querySelector('turbo-frame[data-view-frame]');
            if (viewFrame) viewFrame.innerHTML = '';
        }
    }

    /**
     * Collapse the featured (first) post to a thumbnail+info row.
     * Called when any other post is being expanded.
     */
    function collapseFeaturedToThumb() {
        const featuredFrame = document.querySelector('turbo-frame.blog-featured-frame');
        if (!featuredFrame) return;
        const featured = featuredFrame.querySelector('.blog-featured');
        const featuredAsList = featuredFrame.querySelector('.blog-featured-as-list');
        if (featured) featured.style.display = 'none';
        if (featuredAsList) featuredAsList.classList.remove('d-none');
    }

    /**
     * Restore the featured (first) post to its full hero view.
     * Called when all other posts collapse.
     */
    function restoreFeaturedPost() {
        const featuredFrame = document.querySelector('turbo-frame.blog-featured-frame');
        if (!featuredFrame || featuredFrame.dataset.expanded === 'true') return;
        const featured = featuredFrame.querySelector('.blog-featured');
        const featuredAsList = featuredFrame.querySelector('.blog-featured-as-list');
        if (featured) featured.style.display = '';
        if (featuredAsList) featuredAsList.classList.add('d-none');
    }

    function collapseOtherPosts(activeContainerId) {
        const expandedFrames = document.querySelectorAll('turbo-frame[id^="blog-post-"][data-expanded="true"]');
        expandedFrames.forEach(frame => {
            if (frame.id !== activeContainerId) {
                setExpandedState(frame, false);
            }
        });
        // When any non-featured post becomes active, shrink the featured post to a thumb
        const featuredFrame = document.querySelector('turbo-frame.blog-featured-frame');
        if (featuredFrame && featuredFrame.id !== activeContainerId) {
            collapseFeaturedToThumb();
        }
    }

    function loadBlogPost(slug) {
        const containerId = 'blog-post-' + slug;
        const viewFrameId = 'blog-post-view-' + slug;
        const existingFrame = document.getElementById(containerId);
        const viewFrame = document.getElementById(viewFrameId);

        if (existingFrame && existingFrame.dataset.expanded === 'true') {
            setExpandedState(existingFrame, false);
            restoreFeaturedPost();
            return;
        }

        collapseOtherPosts(containerId);

        const viewUrl = '<?= $this->Url->build(['action' => 'view']) ?>' + '/' + slug;
        Turbo.visit(viewUrl, { frame: viewFrameId });

        if (existingFrame) {
            setExpandedState(existingFrame, true);
        }

        if (viewFrame) {
            viewFrame.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    window.loadBlogPost = loadBlogPost;

    // Restore the featured post hero when a collapse button closes any other post
    document.addEventListener('blog:post-collapsed', function(e) {
        const collapsedId = e.detail && e.detail.frameId;
        const featuredFrame = document.querySelector('turbo-frame.blog-featured-frame');
        if (featuredFrame && collapsedId !== featuredFrame.id) {
            restoreFeaturedPost();
        }
    });

    // Re-setup interactions when Turbo loads new content
    document.addEventListener('turbo:load', function() {
        setupBlogInteractions();
        setupInfiniteScroll();
    });
    </script>
</turbo-frame>
