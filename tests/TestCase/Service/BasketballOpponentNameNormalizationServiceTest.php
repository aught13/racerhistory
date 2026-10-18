<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BasketballOpponentNameNormalizationService;
use Cake\TestSuite\TestCase;

class BasketballOpponentNameNormalizationServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Games',
        'app.StatBasketGameOpponent',
    ];

    private BasketballOpponentNameNormalizationService $service;

    /**
     * Initialize the service for each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BasketballOpponentNameNormalizationService();
    }

    /**
     * Preview reports changes without mutating stored records.
     *
     * @return void
     */
    public function testPreviewShowsConvertedNameWithoutChangingTheRecord(): void
    {
        $table = $this->fetchTable('StatBasketGameOpponent');
        $stat = $table->get(1);
        $stat->set('name', 'Smith, John Jr.');
        $table->saveOrFail($stat);

        $preview = $this->service->preview();

        $this->assertSame(1, $preview['total']);
        $this->assertSame([
            'id' => 1,
            'original' => 'Smith, John Jr.',
            'normalized' => 'John Smith',
        ], $preview['rows'][0]);
        $this->assertSame('Smith, John Jr.', $table->get(1)->get('name'));
    }

    /**
     * Normalization removes Jr. suffixes before converting the name.
     *
     * @return void
     */
    public function testNormalizeRemovesJuniorComponentAndUpdatesName(): void
    {
        $table = $this->fetchTable('StatBasketGameOpponent');
        $stat = $table->get(1);
        $stat->set('name', 'Smith, Jr., John');
        $table->saveOrFail($stat);

        $updated = $this->service->normalize();

        $this->assertSame(1, $updated);
        $this->assertSame('John Smith', $table->get(1)->get('name'));
    }

    /**
     * Ambiguous and already-normalized names are left unchanged.
     *
     * @return void
     */
    public function testMalformedAndNonCommaNamesAreNotChanged(): void
    {
        $table = $this->fetchTable('StatBasketGameOpponent');
        $stat = $table->get(1);
        $stat->set('name', 'Smith, John, Extra');
        $table->saveOrFail($stat);

        $this->assertSame(['total' => 0, 'rows' => []], $this->service->preview());
        $this->assertSame(0, $this->service->normalize());
        $this->assertSame('Smith, John, Extra', $table->get(1)->get('name'));
    }
}
