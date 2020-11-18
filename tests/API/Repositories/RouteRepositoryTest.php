<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\RouteRepository;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Tests\API\APITestCase;

class RouteRepositoryTest extends APITestCase
{
    /**
     * @covers \RideTimeServer\API\Repositories\RouteRepository::populateEntity()
     */
    public function testUpsert()
    {
        $trails[1] = $this->generateTrail();
        $trails[1]->setRemoteId(1);
        $trails[1]->setSource('trailforks');
        $this->entityManager->persist($trails[1]);
        $trails[2] = $this->generateTrail();
        $trails[2]->setRemoteId(2);
        $trails[2]->setSource('trailforks');
        $this->entityManager->persist($trails[2]);
        $location = $this->generateLocation();
        $location->setRemoteId(1);
        $location->setSource('trailforks');
        $this->entityManager->persist($location);
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

        $this->assertEqualsCanonicalizing($trails, $route->getTrails()->getValues());
        $this->assertSame($location, $route->getLocation());
        $this->assertEquals($data->title, $route->getTitle());
        $this->assertEquals($data->stats, $route->getProfile());
    }
}
