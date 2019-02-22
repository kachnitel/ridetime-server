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

    public function testCreateUser()
    {
        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $user = $this->callMethod($endpoint, 'createUser', [[
            'name' => 'Joe',
            'email' => 'e@mail.ca',
            'hometown' => 'Whistler, BC'
        ]]);

        $this->assertNull($user->getLevel());
        $this->assertEquals('Joe', $user->getName());
        $this->assertEquals('e@mail.ca', $user->getEmail());
        $this->assertEquals('Whistler, BC', $user->getHometown());
    }

    public function testUpdate()
    {

        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $user = $this->callMethod($endpoint, 'createUser', [[
            'name' => 'Joe',
            'email' => 'e@mail.ca',
            'hometown' => 'Whistler, BC',
            'authIds' => '123'
        ]]);

        $endpoint->performUpdate($user, ['name' => 'Joseph'], '123');

        $this->assertEquals('Joseph', $user->getName());
        // Ensure this hasn't changed
        $this->assertEquals('e@mail.ca', $user->getEmail());
    }

    public function testUpdateWrongAuthId()
    {

        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $user = $this->callMethod($endpoint, 'createUser', [[
            'name' => 'Joe',
            'email' => 'e@mail.ca',
            'hometown' => 'Whistler, BC',
            'authIds' => '123'
        ]]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Trying to update other user than self.');
        $endpoint->performUpdate($user, ['name' => 'Joseph'], '12');
    }
}
