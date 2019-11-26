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
    public function testNearby()
    {
        $result = $this->getRepoMockRemoteFilter()->nearby([1, 2], 3);
        $this->assertEquals('nearby_range::3;lat::1;lon::2', $result[0]);
    }

    public function testBbox()
    {
        $result = $this->getRepoMockRemoteFilter()->bbox([1,2,3,4]);
        $this->assertEquals('bbox::1,2,3,4', $result[0]);
    }

    public function testSearch()
    {
        $result = $this->getRepoMockRemoteFilter()->search('Cypress');
        $this->assertEquals('search::Cypress', $result[0]);
    }

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
        $repo->setConnector($mockConnector);

        $result = $repo->remoteFilter($filter);

        $this->assertEquals(count($data->data), count($result));
        $this->containsOnlyInstancesOf(Location::class, $result);
        foreach ($result as $location) {
            $this->assertTrue($this->entityManager->contains($location));
        }
    }

    /**
     * @return LocationRepository
     */
    protected function getRepoMockRemoteFilter()
    {
        $mockRepo = $this->getMockBuilder(LocationRepository::class)
            ->setMethods(['remoteFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepo->expects($this->exactly(1))
            ->method('remoteFilter')
            ->will(
                $this->returnCallback(function() {
                    return func_get_args();
                 })
            );

        return $mockRepo;
    }
}
