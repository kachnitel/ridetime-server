<?php
namespace RideTimeServer\Tests\API\Repositories;

use GuzzleHttp\Client;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\API\Repositories\BaseTrailforksRepository;
use RideTimeServer\Entities\Location;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\Tests\API\APITestCase;

use function GuzzleHttp\json_decode;

class BaseTrailforksRepositoryTest extends APITestCase
{
    public function testFindWithFallback()
    {
        $sampleUrl = TrailforksConnector::API_URL
            . 'region?id=1&api_key=docs';

        $client = new Client();
        $data = json_decode($client->get($sampleUrl)->getBody()->getContents());

        $mockConnector = $this->createMock(TrailforksConnector::class);
        $mockConnector->expects($this->exactly(2))
            ->method('getLocation')
            ->willReturnOnConsecutiveCalls(
                $data->data,
                null
            );

        $repo = $this->getMockForAbstractClass(
            BaseTrailforksRepository::class,
            [
                $this->entityManager,
                $this->entityManager->getClassMetadata(Location::class)
            ],
            'MockLocationRepository', // Mock class name
            true, // callOriginalConstructor
            true, // callOriginalClone
            true, // callAutoload
            [
                'getIdField'
            ]
        );
        $repo->method('getIdField')
            ->willReturn('rid');
        $repo->method('populateEntity')
            ->will($this->returnCallback(function (Location $location, $data) {
                /**
                 * REVIEW: Copied LocationEndpoint::populateEntity
                 */
                $location->setId($data->rid);
                $location->setName($data->title);
                $location->setGpsLat($data->latitude);
                $location->setGpsLon($data->longitude);
                $location->setAlias($data->alias);

                return $location;
            }));

        /** @var TrailforksConnector $mockConnector */
        /** @var BaseTrailforksRepository $repo */
        $repo->setConnector($mockConnector);

        // Location not in DB but found at API
        $fromApi = $repo->findWithFallback(1);
        $this->assertInstanceOf(Location::class, $fromApi);
        $this->assertEquals(1, $fromApi->getId());

        // Location known in DB
        $location = new Location();
        $location->setId(2);
        $location->setName('Test cached location');
        $location->setGpsLat(1.23);
        $location->setGpsLon(3.45);
        $location->setAlias('cached-location');
        $this->entityManager->persist($location);

        $cached = $repo->findWithFallback(2);
        $this->assertSame($location, $cached);

        // Non-existent location
        $this->expectException(EntityNotFoundException::class);
        $repo->findWithFallback(3);
    }
}
