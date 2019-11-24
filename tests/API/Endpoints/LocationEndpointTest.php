<?php
namespace RideTimeServer\Tests\API\Endpoints;

use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\Database\LocationEndpoint;
use Monolog\Logger;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\API\Repositories\LocationRepository;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Tests\API\APITestCase;

class LocationEndpointTest extends APITestCase
{
    /**
     * @var Location[]
     */
    protected $knownLocations = [];

    public function testNearby()
    {
        $result = $this->getEndpointMockEntityManager()->nearby([1, 2], 3);
        $this->assertCount(1, $result);
        $this->assertEquals('nearby_range::3;lat::1;lon::2', $result[0]);
    }

    public function testBbox()
    {
        $result = $this->getEndpointMockEntityManager()->bbox([1,2,3,4]);
        $this->assertEquals('bbox::1,2,3,4', $result[0]);
    }

    public function testSearch()
    {
        $result = $this->getEndpointMockEntityManager()->search('Cypress');
        $this->assertEquals('search::Cypress', $result[0]);
    }

    public function testList()
    {
        $this->knownLocations[] = $this->generateLocation();
        $this->knownLocations[] = $this->generateLocation();

        $result = $this->getEndpointMockEntityManager()->list();
        $this->assertContainsOnlyInstancesOf(Location::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(
            [
                $this->knownLocations[0],
                $this->knownLocations[1]
            ],
            $result
        );
    }

    /**
     * Initialize EntityManager with a mock repository
     * remoteFilter(): returns [args] including the filter string
     *
     * @return LocationEndpoint
     */
    protected function getEndpointMockEntityManager()
    {
        $mockRepo = $this->createMock(LocationRepository::class);
        $mockRepo->expects($this->any())
            ->method('remoteFilter')
            ->will(
                $this->returnCallback(function() { return func_get_args(); })
            );

        $mockRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($this->knownLocations);

        /** @var EntityManager $entityManager Fool VSCode linter */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        return new LocationEndpoint(
            $entityManager,
            $this->getLogger(),
            new TrailforksConnector('', '', $this->getLogger()));
    }
}
