<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\GameEavMetaService;
use App\Service\GameEavUiService;
use App\Service\GameService;
use Cake\TestSuite\TestCase;

class GameEavMetaServiceTest extends TestCase
{
    public function testGetMetadataResultReturnsSuccessPayloadAndMetadata(): void
    {
        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => ['scoring_type' => 'cumulative'],
            'eavTemplate' => ['period_1_team' => ['label' => 'Period 1 - Team']],
            'values' => ['period_1_mur' => '35'],
        ];

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata'])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameEavMetadata')
            ->with(1, null)
            ->willReturn($metadata);

        $eavUi = $this->createMock(GameEavUiService::class);

        $service = new GameEavMetaService($gameService, $eavUi);
        $result = $service->getMetadataResult(1, null);

        $this->assertSame($metadata, $result['metadata']);
        $this->assertTrue($result['payload']['success']);
        $this->assertSame(1, $result['payload']['sportId']);
        $this->assertSame('Basketball', $result['payload']['sportName']);
        $this->assertSame($metadata['values'], $result['payload']['values']);
    }

    public function testGetMetadataResultReturnsFailureWhenNoParamsProvided(): void
    {
        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata'])
            ->getMock();
        $gameService->expects($this->never())
            ->method('getGameEavMetadata');

        $eavUi = $this->createMock(GameEavUiService::class);

        $service = new GameEavMetaService($gameService, $eavUi);
        $result = $service->getMetadataResult(null, null);

        $this->assertFalse($result['payload']['success']);
        $this->assertNull($result['metadata']);
    }

    public function testBuildSportSpecificFieldsElementVarsMapsLegacyKeys(): void
    {
        $metadata = [
            'sportName' => 'Basketball',
            'eavTemplate' => ['period_1_team' => ['label' => 'Period 1 - Team']],
            'values' => ['period_1_mur' => '35'],
        ];

        $gameService = $this->createMock(GameService::class);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mapLegacyKeys'])
            ->getMock();
        $eavUi->expects($this->once())
            ->method('mapLegacyKeys')
            ->with(['period_1_mur' => '35'])
            ->willReturn(['period_1_mur' => '35', 'period_1_team' => '35']);

        $service = new GameEavMetaService($gameService, $eavUi);
        $vars = $service->buildSportSpecificFieldsElementVars($metadata);

        $this->assertSame($metadata['eavTemplate'], $vars['eavTemplate']);
        $this->assertSame($metadata['values'], $vars['eav']);
        $this->assertSame('Basketball', $vars['sportName']);
        $this->assertSame(['period_1_mur' => '35', 'period_1_team' => '35'], $vars['legacyMappedEav']);
    }
}
