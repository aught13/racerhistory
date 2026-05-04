<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Model\Entity\Person;
use Cake\TestSuite\TestCase;

class PersonLabelTest extends TestCase
{
    /**
     * Tests label with display.
     */
    public function testLabelWithDisplay(): void
    {
        $person = new Person([
            'display' => 'John Doe',
            'first' => 'Johnny',
            'last' => 'Smith',
        ]);
        $this->assertEquals('John Doe', $person->getLabel());
    }

    /**
     * Tests label with first last.
     */
    public function testLabelWithFirstLast(): void
    {
        $person = new Person([
            'display' => '',
            'first' => 'Jane',
            'last' => 'Smith',
        ]);
        $this->assertEquals('Jane Smith', $person->getLabel());
    }

    /**
     * Tests label with no name uses id.
     */
    public function testLabelWithNoNameUsesId(): void
    {
        $person = new Person([
            'display' => '',
            'first' => '',
            'last' => '',
            'id' => 123,
        ]);
        $this->assertEquals('Person #123', $person->getLabel());
    }

    /**
     * Tests label with no name no id uses default.
     */
    public function testLabelWithNoNameNoIdUsesDefault(): void
    {
        $person = new Person([
            'display' => '',
            'first' => '',
            'last' => '',
        ]);
        $this->assertEquals('Unknown Person', $person->getLabel());
    }
}
