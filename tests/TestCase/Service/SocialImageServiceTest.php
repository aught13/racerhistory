<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SocialImageService;
use Cake\TestSuite\TestCase;

class SocialImageServiceTest extends TestCase
{
    /**
     * Real content images should be preserved while ad slots are ignored.
     */
    public function testFilterCandidatesKeepsContentImagesAndDropsAdImages(): void
    {
        $candidates = [
            'https://pagead2.googlesyndication.com/pagead/ads?client=ca-pub-123',
            '/img/storage/real-content-image.jpg',
            'https://cdn.example.com/leaderboard-banner.jpg',
            '/img/logo.png',
        ];

        $this->assertSame([
            '/img/storage/real-content-image.jpg',
            '/img/logo.png',
        ], SocialImageService::filterCandidates($candidates));
    }

    /**
     * Ad-only candidate lists should not be used as social previews.
     */
    public function testFilterCandidatesReturnsEmptyArrayForAdOnlyInput(): void
    {
        $candidates = [
            'https://ads.example.com/leaderboard.png',
            'https://googleads.g.doubleclick.net/pagead/img/ads?foo=bar',
        ];

        $this->assertSame([], SocialImageService::filterCandidates($candidates));
    }

    /**
     * Empty or non-string values should be skipped cleanly.
     */
    public function testFilterCandidatesSkipsEmptyValues(): void
    {
        $candidates = ['', null, 0, '/img/logo.png', false];

        $this->assertSame(['/img/logo.png'], SocialImageService::filterCandidates($candidates));
    }
}
