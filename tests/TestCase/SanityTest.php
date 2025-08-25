<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use PHPUnit\Framework\TestCase;

class SanityTest extends TestCase
{
    public function testTruth(): void
    {
        $this->assertTrue(true);
    }
}
