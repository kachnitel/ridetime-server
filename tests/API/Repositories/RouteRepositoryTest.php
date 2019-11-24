<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\RouteRepository;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Tests\API\APITestCase;

class RouteRepositoryTest extends APITestCase
{
    public function testUpsert(Type $var = null)
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
            }
        }');
        $repo = new RouteRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(Route::class)
        );
        /** @var Route $route */
        $route = $repo->upsert($data);
        // $route->addTrail($trail[1]);

        // var_dump($route->getTrails()->getValues());
        $this->assertCount(2, $route->getTrails());
        $this->assertContains($trail[1], $route->getTrails());
        $this->assertSame($location, $route->getLocation());

        /**
         * TODO:
         * check values being set properly (and returned in getdetail?)
         */
    }

    /**
     * TODO:
     * testGetDetail() { compare trails list ids with input () }
     * testGetRelated() {
     *  - add location and trails to entityManager
     *      - would need to be fully set for getDetail - mock to do just getId, name
     *  - compare Location object and Trails[] with input in format:
     *    - populateEntity($route, {
     *      id: 1,
     *      rid: 1,
     *      trails: [
     *          {trailid: 1}
     *      ]
     * })
     */
}
