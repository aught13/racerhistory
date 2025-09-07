<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\PersonLabelHelper;
use Cake\TestSuite\TestCase;

class PersonLabelHelperTest extends TestCase
{
    public function testBuildLabelWithDisplay(): void
    {
        $person = (object)['display' => 'John Doe', 'first' => 'Johnny', 'last' => 'Smith'];
        $result = PersonLabelHelper::buildLabel($person);
        $this->assertEquals('John Doe', $result);
    }

    public function testBuildLabelWithFirstLast(): void
    {
        $person = (object)['display' => '', 'first' => 'Jane', 'last' => 'Smith'];
        $result = PersonLabelHelper::buildLabel($person);
        $this->assertEquals('Jane Smith', $result);
    }

    public function testBuildLabelWithNoNameUsesPersonId(): void
    {
        $person = (object)['display' => '', 'first' => '', 'last' => ''];
        $result = PersonLabelHelper::buildLabel($person, 123);
        $this->assertEquals('Person #123', $result);
    }

    public function testBuildLabelWithNoNameNoIdUsesDefault(): void
    {
        $person = (object)['display' => '', 'first' => '', 'last' => ''];
        $result = PersonLabelHelper::buildLabel($person);
        $this->assertEquals('Unknown Person', $result);
    }

    public function testBuildLabelWorksWithEntityGetMethod(): void
    {
        $mockEntity = $this->createMock(\App\Model\Entity\Person::class);
        $mockEntity->method('get')->willReturnMap([
            ['display', 'Mock Person'],
            ['first', 'Mock'],
            ['last', 'Person'],
        ]);

        $result = PersonLabelHelper::buildLabel($mockEntity);
        $this->assertEquals('Mock Person', $result);
    }

    public function testBuildLabelFromIdWithValidPerson(): void
    {
        $mockTable = $this->createMock(\Cake\ORM\Table::class);
        $mockPerson = $this->createMock(\App\Model\Entity\Person::class);
        $mockPerson->method('get')->willReturnMap([
            ['display', 'Database Person'],
            ['first', 'Database'],
            ['last', 'Person'],
        ]);

        $mockTable->expects($this->once())
            ->method('get')
            ->with(456)
            ->willReturn($mockPerson);

        $result = PersonLabelHelper::buildLabelFromId(456, $mockTable);
        $this->assertEquals('Database Person', $result);
    }

    public function testBuildLabelFromIdWithException(): void
    {
        $mockTable = $this->createMock(\Cake\ORM\Table::class);
        $mockTable->expects($this->once())
            ->method('get')
            ->with(999)
            ->willThrowException(new \Exception('Not found'));

        $result = PersonLabelHelper::buildLabelFromId(999, $mockTable);
        $this->assertEquals('Person #999', $result);
    }
}
