<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\Database\LocationEndpoint;
use Monolog\Logger;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\Entities\Location;
use RideTimeServer\Tests\API\APITestCase;

class LocationEndpointTest extends APITestCase
{
    public function testAddMultiple()
    {
        $endpoint = new LocationEndpoint(
            $this->entityManager,
            new Logger('LocationEndpointTest'),
            new TrailforksConnector('', '')
        );

        $locationData = [
            (object) [
                "id" => 1,
                "name" => "Mount Fromme",
                "coords" => [
                    49.356132,
                    -123.053226
                ],
                "difficulties" => [
                    "0" => 6,
                    "1" => 23,
                    "2" => 25,
                    "3" => 12
                ]
            ],
            (object) [
                "id" => 9,
                "name" => "Mount Seymour",
                "coords" => [
                    49.338761,
                    -122.977445
                ],
                "difficulties" => [
                    "0" => 13,
                    "1" => 33,
                    "2" => 24,
                    "3" => 6
                ]
            ]
        ];

        $locations = $endpoint->addMultiple($locationData);

        /** @var Location[] $entities */
        $entities = $this->entityManager->getRepository(Location::class)->findAll();

        $this->assertCount(2, $entities);
        $this->assertEquals($locationData[0]->id, $entities[0]->getId());
        $this->assertEquals($locationData[1]->id, $entities[1]->getId());
        $this->assertEquals($locations[0], $this->entityManager->find(Location::class, 1)->getDetail());
    }
}
