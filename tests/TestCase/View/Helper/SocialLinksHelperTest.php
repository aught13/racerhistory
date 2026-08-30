<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\SocialLinksHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class SocialLinksHelperTest extends TestCase
{
    private SocialLinksHelper $helper;

    /**
     * Initialize helper under test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->helper = new SocialLinksHelper(new View());
    }

    /**
     * Null input should render no wrapper.
     */
    public function testRenderReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->helper->render(null));
    }

    /**
     * JSON payloads should decode, normalize missing schemes, and map host icons.
     */
    public function testRenderDecodesJsonAndMapsKnownIcons(): void
    {
        $html = $this->helper->render('["twitter.com/racerhistory","https://github.com/aught13"]');

        $this->assertStringContainsString('social-links', $html);
        $this->assertStringContainsString('href="https://twitter.com/racerhistory"', $html);
        $this->assertStringContainsString('fa-brands fa-twitter', $html);
        $this->assertStringContainsString('fa-brands fa-github', $html);
        $this->assertStringContainsString('@racerhistory', $html);
        $this->assertStringContainsString('@aught13', $html);
    }

    /**
     * Branded hosts should map to Font Awesome brand icons and unknown hosts should fall back to the site text.
     */
    public function testRenderUsesBrandIconsAndFallbackDomainText(): void
    {
        $html = $this->helper->render('["https://www.threads.com/@racerhistory","https://example.com/hello"]');

        $this->assertStringContainsString('fa-brands fa-threads', $html);
        $this->assertStringContainsString('example.com', $html);
        $this->assertStringContainsString('@racerhistory', $html);
    }

    /**
     * Newline payloads should parse and use fallback icon + sanitized labels.
     */
    public function testRenderParsesNewlineListAndSanitizesLabel(): void
    {
        $html = $this->helper->render("example.com\nhttps://foo.bar/@bad!name\n\n");

        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('href="https://foo.bar/@bad!name"', $html);
        $this->assertStringContainsString('example.com', $html);
        $this->assertStringContainsString('foo.bar', $html);
        $this->assertStringNotContainsString('@badname', $html);
    }

    /**
     * Arrays and scalar fallbacks should skip empty values and still render links.
     */
    public function testRenderAcceptsArrayAndScalarFallback(): void
    {
        $htmlFromArray = $this->helper->render(['', 'linkedin.com/in/example-user']);
        $this->assertStringContainsString('fa-brands fa-linkedin', $htmlFromArray);
        $this->assertStringContainsString('@example-user', $htmlFromArray);

        $htmlFromScalar = $this->helper->render(12345);
        $this->assertStringContainsString('href="https://12345"', $htmlFromScalar);
        $this->assertStringContainsString('12345', $htmlFromScalar);
        $this->assertStringNotContainsString('@12345', $htmlFromScalar);
    }
}
