<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\RouteRepository;
use RideTimeServer\Entities\Route;
use RideTimeServer\Tests\API\APITestCase;

class RouteRepositoryTest extends APITestCase
{
    /**
     * @covers \RideTimeServer\API\Repositories\RouteRepository::populateEntity()
     */
    public function testUpsert()
    {
        $trail[1] = $this->entityManager->merge($this->generateTrail(1));
        $trail[2] = $this->entityManager->merge($this->generateTrail(2));
        $location = $this->entityManager->merge($this->generateLocation(1));
        $this->entityManager->flush();

        $data = json_decode('{
            "id": "1",
            "rid": "1",
            "title": "Classic Adv-Intermediate Loop",
            "difficulty": "5",
            "description": "Testing the new routes system.",
            "trails": [
                {
                    "trailid": "1"
                },
                {
                    "trailid": "2"
                }
            ],
            "stats": {
              "distance": "10520",
              "alt_climb": "464.3",
              "alt_descent": "-452.2"
            },
            "alias": "classic-loop"
        }');
        $repo = new RouteRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(Route::class)
        );
        /** @var Route $route */
        $route = $repo->upsert($data);

        $this->assertCount(2, $route->getTrails());
        $this->assertContains($trail[1], $route->getTrails());
        $this->assertSame($location, $route->getLocation());
        $this->assertEquals($data->title, $route->getTitle());
        $this->assertEquals($data->stats, $route->getProfile());
    }
}
