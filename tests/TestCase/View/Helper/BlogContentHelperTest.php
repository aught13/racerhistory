<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Service\BlogContentService;
use App\View\Helper\BlogContentHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class BlogContentHelperTest extends TestCase
{
    /**
     * Excerpts decode entities before templates escape them for output.
     */
    public function testDecodeHtmlEntities(): void
    {
        $helper = new BlogContentHelper(new View(), [
            'blogContentService' => $this->createMock(BlogContentService::class),
        ]);

        $this->assertSame(
            'SIOUX FALLS, S.D. — & <script>',
            $helper->decodeHtmlEntities('SIOUX FALLS, S.D. &mdash; &amp; &lt;script&gt;'),
        );
    }
}
