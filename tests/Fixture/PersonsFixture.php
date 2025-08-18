<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PersonsFixture extends TestFixture
{
    public string $table = 'persons';

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'first' => 'John',
                'last' => 'Doe',
                'full' => 'John Doe',
                'display' => 'John Doe',
                'birth' => '1990-01-01',
                'death' => null,
                'person_image' => null,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
                'bio' => 'Sample biography for John Doe.',
            ],
            [
                'id' => 2,
                'first' => 'Jane',
                'last' => 'Smith',
                'full' => 'Jane Smith',
                'display' => 'Jane Smith',
                'birth' => '1992-05-10',
                'death' => null,
                'person_image' => null,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
                'bio' => 'Sample biography for Jane Smith.',
            ],
        ];
        parent::init();
    }
}
