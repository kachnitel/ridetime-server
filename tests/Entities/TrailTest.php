<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Entities\Location;

class TrailTest extends TestCase
{
    public function testCreate()
    {
        $location = new Location();

        $trailData = $this->getRandomTrailData();

        $trail = Trail::create($trailData, $location);

        $this->assertInstanceOf(Trail::class, $trail);
        $this->assertSame($location, $trail->getLocation());

        $this->assertEquals($trailData->id, $trail->getId());
        $this->assertEquals($trailData->title, $trail->getTitle());
    }

    public function testSetProfile()
    {
        $trail = Trail::create($this->getRandomTrailData(), new Location());

        $trail->setProfile((object) [
            "distance" => 25.947,
            "alt_climb" => 0,
            "alt_descent" => -5.6,
            "useless_value" => 1
        ]);

        $this->assertEquals((object) [
            "distance" => 25.947,
            "alt_climb" => 0,
            "alt_descent" => -5.6
        ], $trail->getProfile());
    }

    protected function getRandomTrailData()
    {
        $randomText = uniqid();

        return (object) [
            'id' => random_int(0, 1000),
            'difficulty' => random_int(0, 4),
            'title' => 'Test ' . $randomText,
            'description' => 'Description test ' . $randomText,
            'alias' => $randomText
        ];
    }
}
