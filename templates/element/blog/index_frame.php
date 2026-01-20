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
                <div class="blog-featured cursor-pointer" data-blog-post="<?= h($featured->slug) ?>" style="cursor: pointer;">
                    <div class="row align-items-start g-4">
                        <?php if (!empty($featured->hero_image_id)): ?>
                        <div class="col-lg-7">
                            <img src="/images/serve/<?= h($featured->hero_image_id) ?>?w=1200&h=720&fit=cover" class="img-fluid rounded blog-hero-image" alt="<?= h($featured->title) ?>">
                        </div>
                        <?php endif; ?>
                        <div class="col-lg-<?= !empty($featured->hero_image_id) ? '5' : '12' ?>">
                            <h1 class="h2 mb-2 blog-hero-title"><?= h($featured->title) ?></h1>
                            <p class="text-muted small mb-3">
                                <?php if ($featured->published_at instanceof \DateTimeInterface): ?>
                                    <?= h($featured->published_at->format('F j, Y')) ?>
                                <?php else: ?>
                                    <?= h($featured->published_at ?? '') ?>
                                <?php endif; ?>
                            </p>
                            <p class="lead mb-0 blog-hero-excerpt"><?= h($featured->excerpt ?: mb_substr((string)$featured->body, 0, 220) . '...') ?></p>
                        </div>
                    </div>
                </div>
                <turbo-frame id="blog-post-view-<?= h($featured->slug) ?>" data-view-frame></turbo-frame>
            </turbo-frame>

            <!-- Recent Posts List Header -->
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

        // Handle featured post click
        const featured = blogFrame.querySelector('.blog-featured');
        if (featured) {
            featured.addEventListener('click', function() {
                const slug = this.dataset.blogPost;
                if (slug) {
                    loadBlogPost(slug);
                }
            });
        }

        // Handle list items click
        const listItems = blogFrame.querySelectorAll('.blog-list-item');
        listItems.forEach(item => {
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
        const listItem = frame.querySelector('.blog-list-item');
        if (listItem) listItem.style.display = expanded ? 'none' : '';
        if (!expanded) {
            const viewFrame = frame.querySelector('turbo-frame[data-view-frame]');
            if (viewFrame) viewFrame.innerHTML = '';
        }
    }

    function collapseOtherPosts(activeContainerId) {
        const expandedFrames = document.querySelectorAll('turbo-frame[id^="blog-post-"][data-expanded="true"]');
        expandedFrames.forEach(frame => {
            if (frame.id !== activeContainerId) {
                setExpandedState(frame, false);
            }
        });
    }

    function loadBlogPost(slug) {
        const containerId = 'blog-post-' + slug;
        const viewFrameId = 'blog-post-view-' + slug;
        const existingFrame = document.getElementById(containerId);
        const viewFrame = document.getElementById(viewFrameId);

        if (existingFrame && existingFrame.dataset.expanded === 'true') {
            setExpandedState(existingFrame, false);
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

    // Re-setup interactions when Turbo loads new content
    document.addEventListener('turbo:load', function() {
        setupBlogInteractions();
        setupInfiniteScroll();
    });
    </script>
</turbo-frame>
