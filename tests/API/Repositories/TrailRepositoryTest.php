<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\TrailRepository;
use RideTimeServer\Tests\API\APITestCase;

class TrailRepositoryTest extends APITestCase
{
    public function testListByLocation()
    {
        /** @var TrailRepository $repo */
        $repo = $this->getRepoMockRemoteFilter(TrailRepository::class);
        $result = $repo->listByLocation(123);
        $this->assertEquals('rid::123', $result[0]);
    }
}
