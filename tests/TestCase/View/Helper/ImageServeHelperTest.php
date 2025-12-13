<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\ImageServeHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class ImageServeHelperTest extends TestCase
{
    public function testPath(): void
    {
        $helper = new ImageServeHelper(new View());
        $this->assertSame('/images/serve/123', $helper->path(123));
        $this->assertSame('/images/serve/123', $helper->path('123'));
        $this->assertSame('', $helper->path(0));
    }

    public function testQueryFiltersAndBuilds(): void
    {
        $helper = new ImageServeHelper(new View());
        $qs = $helper->query([
            'w' => 150,
            'h' => '150',
            'fit' => 'cover',
            'variant' => 'thumb',
            'q' => 90,
            'bogus' => 'nope',
            'fm' => '',
        ]);

        $this->assertNotSame('', $qs);
        $this->assertStringStartsWith('?', $qs);

        parse_str((string)parse_url($qs, PHP_URL_QUERY), $parsed);
        $this->assertSame('150', (string)$parsed['w']);
        $this->assertSame('150', (string)$parsed['h']);
        $this->assertSame('cover', $parsed['fit']);
        $this->assertSame('thumb', $parsed['variant']);
        $this->assertSame('90', (string)$parsed['q']);
        $this->assertArrayNotHasKey('bogus', $parsed);
        $this->assertArrayNotHasKey('fm', $parsed);
    }

    public function testUrl(): void
    {
        $helper = new ImageServeHelper(new View());
        $url = $helper->url(5, ['w' => 60, 'h' => 60, 'fit' => 'cover']);
        $this->assertStringStartsWith('/images/serve/5?', $url);

        $parts = parse_url($url);
        $this->assertSame('/images/serve/5', $parts['path'] ?? null);
        parse_str($parts['query'] ?? '', $parsed);
        $this->assertSame('60', (string)$parsed['w']);
        $this->assertSame('60', (string)$parsed['h']);
        $this->assertSame('cover', $parsed['fit']);
    }

    public function testUrlForImageInjectsVersion(): void
    {
        $helper = new ImageServeHelper(new View());

        $image = (object)[
            'id' => 9,
            'hash' => 'abc123',
        ];

        $url = $helper->urlForImage($image, ['w' => 100, 'h' => 100, 'fit' => 'cover']);
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $parsed);

        $this->assertSame('abc123', $parsed['v'] ?? null);
        $this->assertSame('100', (string)$parsed['w']);
    }

    public function testUrlForImageDoesNotOverrideExplicitVersion(): void
    {
        $helper = new ImageServeHelper(new View());

        $image = (object)[
            'id' => 9,
            'hash' => 'abc123',
        ];

        $url = $helper->urlForImage($image, ['v' => 'explicit', 'w' => 10]);
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $parsed);

        $this->assertSame('explicit', $parsed['v'] ?? null);
        $this->assertSame('10', (string)$parsed['w']);
    }
}
