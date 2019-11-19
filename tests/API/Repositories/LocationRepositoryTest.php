<?php
namespace RideTimeServer\Tests\API\Repositories;

use GuzzleHttp\Client;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\API\Repositories\LocationRepository;
use RideTimeServer\Entities\Location;
use RideTimeServer\Tests\API\APITestCase;

use function GuzzleHttp\json_decode;

class LocationRepositoryTest extends APITestCase
{
    public function testRemoteFilter()
    {
        $filter = 'bbox::49.7,-123.1,49.8,-123.2;';
        $sampleUrl = TrailforksConnector::API_URL
            . 'regions'
            . '?filter=' . $filter
            . 'bottom::ridingarea&api_key=docs';

        $client = new Client();
        $data = json_decode($client->get($sampleUrl)->getBody()->getContents());

        /** @var TrailforksConnector $mockConnector */
        $mockConnector = $this->createMock(TrailforksConnector::class);
        $mockConnector->expects($this->any())
            ->method('locations')
            ->willReturn($data->data);

        $repo = new LocationRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(Location::class)
        );

        /** @var LocationRepository $repo */
        $repo->setConnector($mockConnector);

        $result = $repo->remoteFilter($filter);

        $this->assertEquals(count($data->data), count($result));
        $this->containsOnlyInstancesOf(Location::class, $result);
        foreach ($result as $location) {
            $this->assertTrue($this->entityManager->contains($location));
        }
    }

    public function _testFindWithFallback()
    {
        /**
         * TODO:
         * Mock $connector and inject
         * Mock upsert()
         * Add 1 Location to EM
         * Find the added location
         * Find one at *mocked* API
         * Search one that doesn't exist, check for exception
         */
    }
}
