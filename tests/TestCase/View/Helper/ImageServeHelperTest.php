<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\Model\Entity\Image;
use App\View\Helper\ImageServeHelper;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class ImageServeHelperTest extends TestCase
{
    public array $fixtures = ['app.Images'];

    private ImageServeHelper $helper;

    /**
     * @var \App\Model\Table\ImagesTable
     */
    private $imagesTable;

    /**
     * @var mixed
     */
    private $previousProfiles;

    /**
     * Set up helper and profile config for each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->helper = new ImageServeHelper(new View());
        $this->imagesTable = TableRegistry::getTableLocator()->get('Images');
        $this->previousProfiles = Configure::read('Images.profiles');
        Configure::write('Images.profiles', [
            'roster_avatar' => ['sourceVariant' => 'thumb'],
            'season_billboard' => ['sourceVariant' => 'hero'],
            'blog_featured' => ['sourceVariant' => 'hero'],
        ]);
    }

    /**
     * Restore profile config and release helper state.
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::write('Images.profiles', $this->previousProfiles);
        unset($this->helper, $this->imagesTable);
        parent::tearDown();
    }

    /**
     * Path helper should resolve stored original URLs by id.
     *
     * @return void
     */
    public function testPathBuildsStoredUrlForExistingImageId(): void
    {
        /** @var \App\Model\Entity\Image $image */
        $image = $this->imagesTable->get(1);
        $expected = '/img/storage/' . ltrim((string)$image->storage_path, '/');

        $this->assertSame($expected, $this->helper->path(1));
        $this->assertSame($expected, $this->helper->path('1'));
        $this->assertSame('', $this->helper->path(0));
    }

    /**
     * Query helper should only emit cache-bust timestamps.
     *
     * @return void
     */
    public function testQueryOnlyKeepsCacheBustTimestamp(): void
    {
        $this->assertSame('', $this->helper->query(['w' => 150, 'variant' => 'thumb']));
        $this->assertSame('?_ts=123', $this->helper->query(['_ts' => 123, 'w' => 150]));
    }

    /**
     * URL helper should resolve stored original URL for fixture image.
     *
     * @return void
     */
    public function testUrlUsesStoredOriginalForFixtureImage(): void
    {
        /** @var \App\Model\Entity\Image $image */
        $image = $this->imagesTable->get(1);
        $expected = '/img/storage/' . ltrim((string)$image->storage_path, '/');

        $this->assertSame($expected, $this->helper->url(1, ['w' => 60, 'h' => 60, 'fit' => 'cover']));
    }

    /**
     * URL helper should resolve existing stored variant when requested.
     *
     * @return void
     */
    public function testUrlForImageUsesStoredVariantWhenAvailable(): void
    {
        $image = new Image([
            'id' => 99,
            'filename' => 'photo.jpg',
            'storage_subdir' => '2026/05',
            'storage_path' => '2026/05/photo.jpg',
            'variants' => json_encode([
                'thumb' => ['file' => 'photo-thumb.webp'],
                'hero' => ['file' => 'photo-hero.webp'],
            ]),
        ]);

        $this->assertSame(
            '/img/storage/2026/05/photo-thumb.webp',
            $this->helper->urlForImage($image, ['variant' => 'thumb']),
        );
        $this->assertSame(
            '/img/storage/2026/05/photo-thumb.webp',
            $this->helper->urlForImage($image, ['profile' => 'roster_avatar']),
        );
        $this->assertSame(
            '/img/storage/2026/05/photo-hero.webp',
            $this->helper->urlForImage($image, ['profile' => 'season_billboard']),
        );
    }

    /**
     * URL helper should fall back to stored original when profile variant is missing.
     *
     * @return void
     */
    public function testUrlForImageFallsBackToStoredOriginalWhenProfileHasNoStoredVariant(): void
    {
        $image = new Image([
            'id' => 100,
            'filename' => 'hero.jpg',
            'storage_subdir' => '2026/05',
            'storage_path' => '2026/05/hero.jpg',
            'variants' => json_encode([]),
        ]);

        $this->assertSame(
            '/img/storage/2026/05/hero.jpg',
            $this->helper->urlForImage($image, ['profile' => 'blog_featured']),
        );
    }

    /**
     * URL helper should support id lookup from generic objects.
     *
     * @return void
     */
    public function testUrlForImageSupportsStdClassLookupById(): void
    {
        $image = (object)['id' => 1];

        $this->assertStringStartsWith('/img/storage/', $this->helper->urlForImage($image));
    }

    /**
     * URL helper should append only cache-bust timestamp query values.
     *
     * @return void
     */
    public function testUrlAppendsOnlyCacheBustTimestamp(): void
    {
        $image = new Image([
            'id' => 101,
            'filename' => 'detail.jpg',
            'storage_subdir' => '2026/05',
            'storage_path' => '2026/05/detail.jpg',
            'variants' => json_encode([]),
        ]);

        $this->assertSame(
            '/img/storage/2026/05/detail.jpg?_ts=123',
            $this->helper->urlForImage($image, ['_ts' => 123, 'w' => 400]),
        );
    }

    /**
     * Picture helper should render a single stored-image <img> without <source> tags.
     *
     * @return void
     */
    public function testPictureRendersStoredImageWithoutGeneratedSources(): void
    {
        $image = new Image([
            'id' => 102,
            'filename' => 'portrait.jpg',
            'storage_subdir' => '2026/05',
            'storage_path' => '2026/05/portrait.jpg',
            'variants' => json_encode([
                'thumb' => ['file' => 'portrait-thumb.webp'],
            ]),
        ]);

        $html = $this->helper->picture($image, ['variant' => 'thumb'], [
            'alt' => 'Test <script> & "quotes"',
            'class' => 'custom-class rounded',
            'loading' => 'eager',
            'data-lightbox' => 'gallery',
        ]);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('</picture>', $html);
        $this->assertStringContainsString('src="/img/storage/2026/05/portrait-thumb.webp"', $html);
        $this->assertStringContainsString('alt="Test &lt;script&gt; &amp; &quot;quotes&quot;"', $html);
        $this->assertStringContainsString('class="custom-class rounded"', $html);
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('data-lightbox="gallery"', $html);
        $this->assertSame(0, substr_count($html, '<source'));
    }

    /**
     * Picture helper should return empty output for invalid image identifiers.
     *
     * @return void
     */
    public function testPictureReturnsEmptyForInvalidId(): void
    {
        $this->assertSame('', $this->helper->picture(0));
        $this->assertSame('', $this->helper->picture(-1));
        $this->assertSame('', $this->helper->picture('invalid'));
    }

    /**
     * Responsive picture helper should reuse stored URL and include provided attrs.
     *
     * @return void
     */
    public function testResponsivePictureReusesStoredImageUrl(): void
    {
        $image = new Image([
            'id' => 103,
            'filename' => 'feature.jpg',
            'storage_subdir' => '2026/05',
            'storage_path' => '2026/05/feature.jpg',
            'variants' => json_encode([]),
        ]);

        $html = $this->helper->responsivePicture(
            $image,
            [400, 800, 1200],
            ['profile' => 'blog_featured'],
            [
                'alt' => 'Responsive Test',
                'class' => 'hero-image',
                'decoding' => 'sync',
                'sizes' => '(max-width: 768px) 100vw, 50vw',
            ],
        );

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('src="/img/storage/2026/05/feature.jpg"', $html);
        $this->assertStringContainsString('alt="Responsive Test"', $html);
        $this->assertStringContainsString('class="hero-image"', $html);
        $this->assertStringContainsString('decoding="sync"', $html);
        $this->assertStringContainsString('sizes="(max-width: 768px) 100vw, 50vw"', $html);
        $this->assertSame(0, substr_count($html, '<source'));
    }

    /**
     * Responsive picture helper should return empty output for invalid IDs.
     *
     * @return void
     */
    public function testResponsivePictureReturnsEmptyForInvalidId(): void
    {
        $this->assertSame('', $this->helper->responsivePicture(0));
        $this->assertSame('', $this->helper->responsivePicture(-5, [400, 800]));
    }
}
