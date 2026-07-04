<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Service\BlogPostService;
use Cake\View\Cell;

/**
 * BlogWidget Cell
 * Injects required Blog views into static pages & sidebars without cluttering controllers.
 */
class BlogWidgetCell extends Cell
{
    /**
     * Renders the home layout feed.
     *
     * @return void
     */
    public function homeFeed(): void
    {
        $service = new BlogPostService();
        $result = $service->getPublishedPostsPage(7, 0); // 1 hero + 6 grids

        $posts = $result['posts'] ?? [];
        $hero = !empty($posts) ? $posts[0] : null;
        $gridPosts = count($posts) > 1 ? array_slice($posts, 1) : [];

        $this->set(compact('hero', 'gridPosts'));
    }

    /**
     * Renders the minimal widget sidebar.
     *
     * @return void
     */
    public function sidebar(): void
    {
        $service = new BlogPostService();
        $result = $service->getPublishedPostsPage(5, 0); // 5 recent
        $recentPosts = $result['posts'] ?? [];
        $popularTags = $service->getPopularTags(20);

        $this->set(compact('recentPosts', 'popularTags'));
    }
}
