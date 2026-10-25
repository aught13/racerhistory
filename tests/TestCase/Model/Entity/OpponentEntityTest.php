<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Opponent;
use Cake\TestSuite\TestCase;

class OpponentEntityTest extends TestCase
{
    /**
     * The default opponent name combines the short name and mascot.
     */
    public function testGetDisplayName(): void
    {
        $opponent = new Opponent([
            'opponent_name' => 'Belmont University',
            'opponent_short' => 'Belmont',
            'opponent_mascot' => 'Bruins',
        ]);

        $this->assertSame('Belmont Bruins', $opponent->getDisplayName());
    }

    /**
     * The full opponent name combines the official name and mascot.
     */
    public function testGetFullDisplayName(): void
    {
        $opponent = new Opponent([
            'opponent_name' => 'Belmont University',
            'opponent_short' => 'Belmont',
            'opponent_mascot' => 'Bruins',
        ]);

        $this->assertSame('Belmont University Bruins', $opponent->getFullDisplayName());
    }

    /**
     * The compact opponent variant uses only the mascot when available.
     */
    public function testGetMascotDisplayName(): void
    {
        $opponent = new Opponent([
            'opponent_name' => 'Belmont University',
            'opponent_short' => 'Belmont',
            'opponent_mascot' => 'Bruins',
        ]);

        $this->assertSame('Bruins', $opponent->getMascotDisplayName());
    }

    /**
     * Incomplete records retain a readable existing name instead of abbreviations.
     */
    public function testDisplayNamesFallBackWhenMascotIsMissing(): void
    {
        $opponent = new Opponent([
            'opponent_name' => 'Belmont',
            'opponent_short' => 'BEL',
        ]);

        $this->assertSame('Belmont', $opponent->getDisplayName());
        $this->assertSame('Belmont', $opponent->getMascotDisplayName());
    }

    /**
     * The full name can fall back to the short name when the official name is absent.
     */
    public function testGetFullDisplayNameFallsBackToShortName(): void
    {
        $opponent = new Opponent(['opponent_short' => 'BEL']);

        $this->assertSame('BEL', $opponent->getFullDisplayName());
    }

    /**
     * Empty records have a stable fallback label.
     */
    public function testDisplayNamesUseUnknownFallback(): void
    {
        $opponent = new Opponent();

        $this->assertSame('Unknown Opponent', $opponent->getDisplayName());
        $this->assertSame('Unknown Opponent', $opponent->getFullDisplayName());
        $this->assertSame('Unknown Opponent', $opponent->getMascotDisplayName());
    }

    /**
     * Exact duplicate components are rendered only once.
     */
    public function testDisplayNamesRemoveExactDuplicates(): void
    {
        $opponent = new Opponent([
            'opponent_name' => 'Belmont',
            'opponent_short' => 'Belmont',
            'opponent_mascot' => 'Belmont',
        ]);

        $this->assertSame('Belmont', $opponent->getDisplayName());
        $this->assertSame('Belmont', $opponent->getFullDisplayName());
    }
}
