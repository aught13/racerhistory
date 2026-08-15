<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Sport;
use Cake\TestSuite\TestCase;

/**
 * Sport Entity Test Case
 */
class SportEntityTest extends TestCase
{
    /**
     * Test getDisplayName returns sport name
     */
    public function testGetDisplayName(): void
    {
        $sport = new Sport(['sport_name' => 'Basketball']);
        $this->assertSame('Basketball', $sport->getDisplayName());
    }

    /**
     * Test getDisplayName returns default when no name
     */
    public function testGetDisplayNameDefault(): void
    {
        $sport = new Sport();
        $this->assertSame('Unknown Sport', $sport->getDisplayName());
    }
}
