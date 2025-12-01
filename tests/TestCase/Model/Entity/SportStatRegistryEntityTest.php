<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\SportStatRegistry;
use Cake\TestSuite\TestCase;

/**
 * SportStatRegistry Entity Test Case
 */
class SportStatRegistryEntityTest extends TestCase
{
    /**
     * Test mapped_fields virtual property
     */
    public function testMappedFieldsDecoded(): void
    {
        $registry = new SportStatRegistry([
            'field_mapping' => '{"pts":"Points","reb":"Rebounds"}',
        ]);
        $expected = ['pts' => 'Points', 'reb' => 'Rebounds'];
        $this->assertSame($expected, $registry->mapped_fields);
    }

    /**
     * Test mapped_fields returns empty for null
     */
    public function testMappedFieldsEmpty(): void
    {
        $registry = new SportStatRegistry();
        $this->assertSame([], $registry->mapped_fields);
    }

    /**
     * Test getFieldLabel returns mapped label
     */
    public function testGetFieldLabelMapped(): void
    {
        $registry = new SportStatRegistry([
            'field_mapping' => '{"pts":"Points"}',
        ]);
        $this->assertSame('Points', $registry->getFieldLabel('pts'));
    }

    /**
     * Test getFieldLabel returns field when not mapped
     */
    public function testGetFieldLabelNotMapped(): void
    {
        $registry = new SportStatRegistry(['field_mapping' => '{}']);
        $this->assertSame('unknown', $registry->getFieldLabel('unknown'));
    }

    /**
     * Test isTeamStat
     */
    public function testIsTeamStat(): void
    {
        $registry = new SportStatRegistry(['entity_type' => 'team']);
        $this->assertTrue($registry->isTeamStat());

        $registry2 = new SportStatRegistry(['entity_type' => 'player']);
        $this->assertFalse($registry2->isTeamStat());
    }

    /**
     * Test isPlayerStat
     */
    public function testIsPlayerStat(): void
    {
        $registry = new SportStatRegistry(['entity_type' => 'player']);
        $this->assertTrue($registry->isPlayerStat());

        $registry2 = new SportStatRegistry(['entity_type' => 'team']);
        $this->assertFalse($registry2->isPlayerStat());
    }

    /**
     * Test isOpponentStat
     */
    public function testIsOpponentStat(): void
    {
        $registry = new SportStatRegistry(['entity_type' => 'opponent']);
        $this->assertTrue($registry->isOpponentStat());

        $registry2 = new SportStatRegistry(['entity_type' => 'team']);
        $this->assertFalse($registry2->isOpponentStat());
    }

    /**
     * Test isGameStat
     */
    public function testIsGameStat(): void
    {
        $registry = new SportStatRegistry(['context' => 'game']);
        $this->assertTrue($registry->isGameStat());

        $registry2 = new SportStatRegistry(['context' => 'season']);
        $this->assertFalse($registry2->isGameStat());
    }

    /**
     * Test isSeasonStat
     */
    public function testIsSeasonStat(): void
    {
        $registry = new SportStatRegistry(['context' => 'season']);
        $this->assertTrue($registry->isSeasonStat());

        $registry2 = new SportStatRegistry(['context' => 'game']);
        $this->assertFalse($registry2->isSeasonStat());
    }
}
