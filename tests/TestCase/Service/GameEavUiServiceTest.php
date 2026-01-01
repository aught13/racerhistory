<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\GameEavUiService;
use Cake\TestSuite\TestCase;

class GameEavUiServiceTest extends TestCase
{
    public function testMapLegacyKeysAddsTeamAndOpponentKeysWhenMissing(): void
    {
        $service = new GameEavUiService();

        $values = [
            'period_1_mur' => '10',
            'period_1_opp' => '8',
            'period_2_team' => '12',
        ];

        $mapped = $service->mapLegacyKeys($values);

        $this->assertSame('10', $mapped['period_1_team']);
        $this->assertSame('8', $mapped['period_1_opponent']);

        // Do not overwrite existing modern keys
        $this->assertSame('12', $mapped['period_2_team']);
    }

    public function testMergePostedPeriodAndOvertimeFieldsOnlyMergesEavKeys(): void
    {
        $service = new GameEavUiService();

        $existing = [
            'period_1_team' => '10',
            'official' => 'Ref',
        ];

        $posted = [
            'period_1_team' => '11',
            'overtime_1_team' => '2',
            'notes' => 'hello',
        ];

        $merged = $service->mergePostedPeriodAndOvertimeFields($existing, $posted);

        $this->assertSame('11', $merged['period_1_team']);
        $this->assertSame('2', $merged['overtime_1_team']);

        // Non-EAV keys should remain unchanged / not be added
        $this->assertSame('Ref', $merged['official']);
        $this->assertArrayNotHasKey('notes', $merged);
    }
}
