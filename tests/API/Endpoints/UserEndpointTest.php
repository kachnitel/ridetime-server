<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\UserEndpoint;
use Monolog\Logger;
use RideTimeServer\Entities\User;

class UserEndpointTest extends EndpointTestCase
{
    public function testGetDetailReturnsBasicInfo()
    {
        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $user = new User();
        $user->setName('John Doe');
        $user->setHometown('Town, BC');
        $user->setEmail('a@b.com');

        $detail = $endpoint->getDetail($user);

        $this->assertEquals('John Doe', $detail->name);
        $this->assertEquals('Town, BC', $detail->hometown);
        $this->assertEquals('a@b.com', $detail->email);
    }
}
