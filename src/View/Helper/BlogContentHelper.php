<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\BlogContentService;
use Cake\View\Helper;
use Cake\View\View;

/**
 * View helper for rendering blog body content with image credits.
 */
class BlogContentHelper extends Helper
{
    private BlogContentService $blogContentService;

    /**
     * @param \Cake\View\View $View
     * @param array<string,mixed> $config
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);
        $service = $config['blogContentService'] ?? null;
        $this->blogContentService = $service instanceof BlogContentService
            ? $service
            : new BlogContentService();
    }

    /**
     * Decode entities in plain-text excerpts before they are HTML-escaped for output.
     *
     * @param string $text Excerpt text.
     * @return string
     */
    public function decodeHtmlEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Render blog HTML with photo credits for stored images.
     *
     * @param string $html Blog body HTML.
     */
    public function render(string $html): string
    {
        return $this->blogContentService->renderWithPhotoCredits($html);
    }
}
