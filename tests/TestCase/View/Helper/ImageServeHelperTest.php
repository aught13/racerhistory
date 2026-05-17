<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\ImageServeHelper;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class ImageServeHelperTest extends TestCase
{
    private ImageServeHelper $helper;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->helper = new ImageServeHelper(new View());
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->helper);
        parent::tearDown();
    }

    /**
     * Tests path.
     */
    public function testPath(): void
    {
        $this->assertSame('/images/serve/123', $this->helper->path(123));
        $this->assertSame('/images/serve/123', $this->helper->path('123'));
        $this->assertSame('', $this->helper->path(0));
    }

    /**
     * Tests query filters and builds.
     */
    public function testQueryFiltersAndBuilds(): void
    {
        $qs = $this->helper->query([
            'w' => 150,
            'h' => '150',
            'fit' => 'cover',
            'profile' => 'roster_avatar',
            'variant' => 'thumb',
            'q' => 90,
            'v' => 'ignored-version',
            'bogus' => 'nope',
            'fm' => '',
        ]);

        $this->assertNotSame('', $qs);
        $this->assertStringStartsWith('?', $qs);

        parse_str((string)parse_url($qs, PHP_URL_QUERY), $parsed);
        $this->assertSame('150', (string)$parsed['w']);
        $this->assertSame('150', (string)$parsed['h']);
        $this->assertSame('cover', $parsed['fit']);
        $this->assertSame('roster_avatar', $parsed['profile']);
        $this->assertSame('thumb', $parsed['variant']);
        $this->assertSame('90', (string)$parsed['q']);
        $this->assertArrayNotHasKey('bogus', $parsed);
        $this->assertArrayNotHasKey('fm', $parsed);
        $this->assertArrayNotHasKey('v', $parsed);
    }

    /**
     * Tests url.
     */
    public function testUrl(): void
    {
        $url = $this->helper->url(5, ['w' => 60, 'h' => 60, 'fit' => 'cover']);
        $this->assertStringStartsWith('/images/serve/5?', $url);

        $parts = parse_url($url);
        $this->assertSame('/images/serve/5', $parts['path'] ?? null);
        parse_str($parts['query'] ?? '', $parsed);
        $this->assertSame('60', (string)$parsed['w']);
        $this->assertSame('60', (string)$parsed['h']);
        $this->assertSame('cover', $parsed['fit']);
    }

    /**
     * Tests url for image builds without version.
     */
    public function testUrlForImageBuildsWithoutVersion(): void
    {
        $image = (object)[
            'id' => 9,
            'hash' => 'abc123',
            'modified' => new DateTime('2025-01-15 10:30:00'),
        ];

        $url = $this->helper->urlForImage($image, ['w' => 100, 'h' => 100, 'fit' => 'cover']);
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $parsed);

        $this->assertSame('100', (string)$parsed['w']);
        $this->assertArrayNotHasKey('v', $parsed);
    }

    /**
     * Tests url for image ignores explicit version.
     */
    public function testUrlForImageIgnoresExplicitVersion(): void
    {
        $image = (object)[
            'id' => 9,
            'hash' => 'abc123',
        ];

        $url = $this->helper->urlForImage($image, ['v' => 'explicit', 'w' => 10]);
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $parsed);

        $this->assertArrayNotHasKey('v', $parsed);
        $this->assertSame('10', (string)$parsed['w']);
    }

    // ==================== Picture Element Tests ====================

    public function testPictureWithIntegerId(): void
    {
        $html = $this->helper->picture(42, ['w' => 800]);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('</picture>', $html);
        $this->assertStringContainsString('<source', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('srcset="/images/serve/42?', $html);
        $this->assertStringContainsString('fm=webp', $html);
        $this->assertStringContainsString('<img src="/images/serve/42?', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('class="img-fluid"', $html);
    }

    /**
     * Tests picture with image object.
     */
    public function testPictureWithImageObjectBuildsWithoutVersion(): void
    {
        $modified = new DateTime('2025-02-01 12:00:00');
        $image = (object)[
            'id' => 15,
            'hash' => 'testhash123',
            'modified' => $modified,
        ];

        $html = $this->helper->picture($image, ['w' => 600, 'fit' => 'cover']);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringNotContainsString('v=' . $modified->getTimestamp(), $html);
        $this->assertStringNotContainsString('v=testhash123', $html);
        $this->assertStringNotContainsString('v=', $html);
        $this->assertStringContainsString('fm=webp', $html);
        $this->assertStringContainsString('w=600', $html);
        $this->assertStringContainsString('fit=cover', $html);
    }

    /**
     * Tests picture with custom attributes.
     */
    public function testPictureWithCustomAttributes(): void
    {
        $html = $this->helper->picture(7, ['w' => 400], [
            'alt' => 'Test Image',
            'class' => 'custom-class rounded',
            'loading' => 'eager',
            'data-lightbox' => 'gallery',
        ]);

        $this->assertStringContainsString('alt="Test Image"', $html);
        $this->assertStringContainsString('class="custom-class rounded"', $html);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('data-lightbox="gallery"', $html);
    }

    /**
     * Tests picture returns empty for invalid id.
     */
    public function testPictureReturnsEmptyForInvalidId(): void
    {
        $this->assertSame('', $this->helper->picture(0));
        $this->assertSame('', $this->helper->picture(-1));
        $this->assertSame('', $this->helper->picture('invalid'));
    }

    /**
     * Tests picture with date time modified.
     */
    public function testPictureWithDateTimeModifiedDoesNotAddVersion(): void
    {
        $modified = new DateTime('2025-01-15 10:30:00');
        $image = (object)[
            'id' => 20,
            'modified' => $modified,
        ];

        $html = $this->helper->picture($image, ['w' => 300]);

        $this->assertStringNotContainsString('v=' . $modified->getTimestamp(), $html);
        $this->assertStringNotContainsString('v=', $html);
    }

    /**
     * Tests picture keeps profile parameter for both source and fallback URLs.
     */
    public function testPictureWithProfileIncludesProfileInUrls(): void
    {
        $html = $this->helper->picture(42, ['profile' => 'roster_avatar']);

        $this->assertStringContainsString('/images/serve/42?fm=webp&amp;profile=roster_avatar', $html);
        $this->assertStringContainsString('/images/serve/42?profile=roster_avatar', $html);
    }

    /**
     * Tests picture handles special characters in alt.
     */
    public function testPictureHandlesSpecialCharactersInAlt(): void
    {
        $html = $this->helper->picture(5, [], ['alt' => 'Test <script> & "quotes"']);

        $this->assertStringContainsString('alt="Test &lt;script&gt; &amp; &quot;quotes&quot;"', $html);
    }

    /**
     * Tests picture uses configured WebP variant and derives non-WebP fallback params.
     */
    public function testPictureWithWebpVariantBuildsDerivedFallback(): void
    {
        $previous = Configure::read('Images.variants');
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
        ]);

        try {
            $html = $this->helper->picture(42, ['variant' => 'thumb']);

            $this->assertStringContainsString('<source srcset="/images/serve/42?variant=thumb" type="image/webp">', $html);
            $this->assertStringContainsString('<img src="/images/serve/42?w=150&amp;h=150&amp;fit=cover"', $html);
        } finally {
            Configure::write('Images.variants', $previous);
        }
    }

    // ==================== Responsive Picture Tests ====================

    public function testResponsivePictureGeneratesSrcset(): void
    {
        $html = $this->helper->responsivePicture(25, [400, 800, 1200]);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('</picture>', $html);

        // Check for WebP srcset with all widths
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('400w', $html);
        $this->assertStringContainsString('800w', $html);
        $this->assertStringContainsString('1200w', $html);

        // Check for sizes attribute
        $this->assertStringContainsString('sizes="', $html);

        // Check for fallback img
        $this->assertStringContainsString('<img src="/images/serve/25?', $html);
        $this->assertStringContainsString('class="img-fluid"', $html);
    }

    /**
     * Tests responsive picture with custom sizes.
     */
    public function testResponsivePictureWithCustomSizes(): void
    {
        $html = $this->helper->responsivePicture(
            30,
            [600, 1200],
            [],
            ['sizes' => '(max-width: 768px) 100vw, 50vw'],
        );

        $this->assertStringContainsString('sizes="(max-width: 768px) 100vw, 50vw"', $html);
    }

    /**
     * Tests responsive picture with image object.
     */
    public function testResponsivePictureWithImageObjectBuildsWithoutVersion(): void
    {
        $modified = new DateTime('2025-03-01 08:15:00');
        $image = (object)[
            'id' => 18,
            'hash' => 'responsive-hash',
            'modified' => $modified,
        ];

        $html = $this->helper->responsivePicture($image, [300, 600, 900]);

        $this->assertStringNotContainsString('v=' . $modified->getTimestamp(), $html);
        $this->assertStringNotContainsString('v=responsive-hash', $html);
        $this->assertStringNotContainsString('v=', $html);
        $this->assertStringContainsString('w=300', $html);
        $this->assertStringContainsString('w=600', $html);
        $this->assertStringContainsString('w=900', $html);
    }

    /**
     * Tests responsive picture returns empty for invalid id.
     */
    public function testResponsivePictureReturnsEmptyForInvalidId(): void
    {
        $this->assertSame('', $this->helper->responsivePicture(0));
        $this->assertSame('', $this->helper->responsivePicture(-5, [400, 800]));
    }

    /**
     * Tests responsive picture with additional params.
     */
    public function testResponsivePictureWithAdditionalParams(): void
    {
        $html = $this->helper->responsivePicture(
            12,
            [400, 800],
            ['fit' => 'cover', 'q' => 80],
        );

        $this->assertStringContainsString('fit=cover', $html);
        $this->assertStringContainsString('q=80', $html);
    }

    /**
     * Tests responsive picture retains profile parameter across generated srcset URLs.
     */
    public function testResponsivePictureWithProfileIncludesProfileAcrossSrcset(): void
    {
        $html = $this->helper->responsivePicture(
            12,
            [400, 800],
            ['profile' => 'blog_featured'],
        );

        $this->assertStringContainsString('profile=blog_featured', $html);
        $this->assertStringContainsString('fm=webp', $html);
    }

    /**
     * Tests responsive picture with custom attributes.
     */
    public function testResponsivePictureWithCustomAttributes(): void
    {
        $html = $this->helper->responsivePicture(
            8,
            [500, 1000],
            [],
            [
                'alt' => 'Responsive Test',
                'class' => 'hero-image',
                'decoding' => 'sync',
            ],
        );

        $this->assertStringContainsString('alt="Responsive Test"', $html);
        $this->assertStringContainsString('class="hero-image"', $html);
        $this->assertStringContainsString('decoding="sync"', $html);
    }

    /**
     * Tests responsive picture sorts widths.
     */
    public function testResponsivePictureSortsWidths(): void
    {
        $html = $this->helper->responsivePicture(99, [1200, 400, 800]);

        // Widths should be sorted ascending in srcset
        $pattern = '/400w.*800w.*1200w/s';
        $this->assertMatchesRegularExpression($pattern, $html);
    }

    /**
     * Tests responsive picture fallback uses middle width.
     */
    public function testResponsivePictureFallbackUsesMiddleWidth(): void
    {
        $html = $this->helper->responsivePicture(50, [300, 600, 900]);

        // Fallback img should use middle width (600)
        $this->assertStringContainsString('<img src="/images/serve/50?w=600', $html);
    }

    /**
     * Tests responsive picture has both source types.
     */
    public function testResponsivePictureHasBothSourceTypes(): void
    {
        $html = $this->helper->responsivePicture(33, [400, 800]);

        // Should have WebP source and non-WebP source
        $this->assertSame(2, substr_count($html, '<source'), 'Should have exactly 2 source elements');
        $this->assertStringContainsString('type="image/webp"', $html);
    }
}
