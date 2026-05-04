<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PlaceService;
use Cake\TestSuite\TestCase;

/**
 * PlaceServiceTest
 *
 * Tests for PlaceService covering both Place and Site functionality.
 */
class PlaceServiceTest extends TestCase
{
    /**
     * @var array
     */
    public array $fixtures = [
        'app.Places',
        'app.Sites',
    ];

    /**
     * Test getPlaceById returns a place entity.
     */
    public function testGetPlaceById(): void
    {
        $service = new PlaceService();
        $place = $service->getPlaceById(1);

        $this->assertNotNull($place);
        if ($place) {
            $this->assertIsInt($place->id);
        }
    }

    /**
     * Test getPlaceById returns null for non-existent place.
     */
    public function testGetPlaceByIdNotFound(): void
    {
        $service = new PlaceService();
        $place = $service->getPlaceById(9999);

        $this->assertNull($place);
    }

    /**
     * Test getDisplayLabel for place.
     */
    public function testGetDisplayLabel(): void
    {
        $service = new PlaceService();
        $label = $service->getDisplayLabel(1);

        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    /**
     * Test getDisplayLabel returns fallback for non-existent place.
     */
    public function testGetDisplayLabelNotFound(): void
    {
        $service = new PlaceService();
        $label = $service->getDisplayLabel(9999);

        $this->assertEquals('Place #9999', $label);
    }

    /**
     * Test searchPlaces finds places by name.
     */
    public function testSearchPlaces(): void
    {
        $service = new PlaceService();
        $results = $service->searchPlaces('place', 10);

        $this->assertIsArray($results);
    }

    /**
     * Test searchPlaces returns empty for empty query.
     */
    public function testSearchPlacesEmptyQuery(): void
    {
        $service = new PlaceService();
        $results = $service->searchPlaces('', 10);

        $this->assertEquals([], $results);
    }

    /**
     * Test getAllPlaces returns array of places.
     */
    public function testGetAllPlaces(): void
    {
        $service = new PlaceService();
        $results = $service->getAllPlaces();

        $this->assertIsArray($results);
    }

    /**
     * Test getPlacesForSelect returns formatted array.
     */
    public function testGetPlacesForSelect(): void
    {
        $service = new PlaceService();
        $results = $service->getPlacesForSelect();

        $this->assertIsArray($results);
        foreach ($results as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('label', $item);
        }
    }

    /**
     * Tests get places list.
     */
    public function testGetPlacesList(): void
    {
        $service = new PlaceService();
        $list = $service->getPlacesList(50);

        $this->assertIsArray($list);
        $this->assertArrayHasKey(1, $list);
        $this->assertSame('Murray, KY', $list[1]);
    }

    /**
     * Test createPlace.
     */
    public function testCreatePlace(): void
    {
        $service = new PlaceService();
        $data = [
            'place_country' => 'USA',
            'place_city' => 'New City',
            'place_state' => 'CA',
        ];
        $place = $service->createPlace($data);

        $this->assertNotFalse($place);
        if ($place) {
            $this->assertEquals('USA', $place->place_country);
        }
    }

    /**
     * Test createPlace returns existing on duplicate.
     */
    public function testCreatePlaceDuplicateReturnsExisting(): void
    {
        $service = new PlaceService();
        // Fixture already has USA / Murray / KY as id=1
        $place = $service->createPlace([
            'place_country' => 'USA',
            'place_city' => 'Murray',
            'place_state' => 'KY',
        ]);

        $this->assertNotFalse($place);
        $this->assertSame(1, $place->id);
    }

    /**
     * Test updatePlace.
     */
    public function testUpdatePlace(): void
    {
        $service = new PlaceService();
        $place = $service->createPlace([
            'place_country' => 'USA',
            'place_city' => 'City',
            'place_state' => 'CA',
        ]);

        if ($place) {
            $updated = $service->updatePlace($place->id, ['place_country' => 'CAN']);
            $this->assertNotFalse($updated);
            if ($updated) {
                $this->assertEquals('CAN', $updated->place_country);
            }
        }
    }

    /**
     * Test deletePlace.
     */
    public function testDeletePlace(): void
    {
        $service = new PlaceService();
        $place = $service->createPlace([
            'place_country' => 'USA',
            'place_city' => 'City',
            'place_state' => 'CA',
        ]);

        if ($place) {
            $result = $service->deletePlace($place->id);
            $this->assertTrue($result);

            $deleted = $service->getPlaceById($place->id);
            $this->assertNull($deleted);
        }
    }

    /**
     * Test getSiteById returns a site entity with place.
     */
    public function testGetSiteById(): void
    {
        $service = new PlaceService();
        $site = $service->getSiteById(1);

        $this->assertNotNull($site);
        if ($site) {
            $this->assertIsInt($site->id);
            $this->assertNotNull($site->place);
        }
    }

    /**
     * Test getSiteById returns null for non-existent site.
     */
    public function testGetSiteByIdNotFound(): void
    {
        $service = new PlaceService();
        $site = $service->getSiteById(9999);

        $this->assertNull($site);
    }

    /**
     * Test getSiteDisplayLabel includes place info.
     */
    public function testGetSiteDisplayLabel(): void
    {
        $service = new PlaceService();
        $label = $service->getSiteDisplayLabel(1);

        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    /**
     * Test getSiteDisplayLabel returns fallback for non-existent site.
     */
    public function testGetSiteDisplayLabelNotFound(): void
    {
        $service = new PlaceService();
        $label = $service->getSiteDisplayLabel(9999);

        $this->assertEquals('Site #9999', $label);
    }

    /**
     * Test searchSites finds sites by name or place info.
     */
    public function testSearchSites(): void
    {
        $service = new PlaceService();
        $results = $service->searchSites('site', 10);

        $this->assertIsArray($results);
    }

    /**
     * Test searchSites returns empty for empty query.
     */
    public function testSearchSitesEmptyQuery(): void
    {
        $service = new PlaceService();
        $results = $service->searchSites('', 10);

        $this->assertEquals([], $results);
    }

    /**
     * Test getAllSites returns array of sites with place info.
     */
    public function testGetAllSites(): void
    {
        $service = new PlaceService();
        $results = $service->getAllSites();

        $this->assertIsArray($results);
        foreach ($results as $site) {
            $this->assertNotNull($site->place);
        }
    }

    /**
     * Test getSitesForSelect returns formatted array with place info.
     */
    public function testGetSitesForSelect(): void
    {
        $service = new PlaceService();
        $results = $service->getSitesForSelect();

        $this->assertIsArray($results);
        foreach ($results as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('label', $item);
            // Label should include place info
            $this->assertIsString($item['label']);
        }
    }

    /**
     * Test createSite.
     */
    public function testCreateSite(): void
    {
        $service = new PlaceService();
        $place = $service->createPlace([
            'place_country' => 'USA',
            'place_city' => 'Test City',
            'place_state' => 'CA',
        ]);

        if ($place) {
            $data = [
                'place_id' => $place->id,
                'site_name' => 'New Site',
            ];
            $site = $service->createSite($data);

            $this->assertNotFalse($site);
            if ($site) {
                $this->assertEquals('New Site', $site->site_name);
            }
        }
    }

    /**
     * Test updateSite.
     */
    public function testUpdateSite(): void
    {
        $service = new PlaceService();
        $site = $service->getSiteById(1);

        if ($site) {
            $updated = $service->updateSite($site->id, ['site_name' => 'Updated Site']);
            $this->assertNotFalse($updated);
            if ($updated) {
                $this->assertEquals('Updated Site', $updated->site_name);
            }
        }
    }

    /**
     * Test deleteSite.
     */
    public function testDeleteSite(): void
    {
        $service = new PlaceService();
        $place = $service->createPlace([
            'place_country' => 'USA',
            'place_city' => 'Delete City',
            'place_state' => 'CA',
        ]);

        if ($place) {
            $site = $service->createSite([
                'place_id' => $place->id,
                'site_name' => 'To Delete',
            ]);

            if ($site) {
                $result = $service->deleteSite($site->id);
                $this->assertTrue($result);

                $deleted = $service->getSiteById($site->id);
                $this->assertNull($deleted);
            }
        }
    }
}
