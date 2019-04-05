<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\UserEndpoint;
use Monolog\Logger;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Location;

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

    public function testGetLocations()
    {
        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        /** @var User $user */
        $user = $this->callMethod($endpoint, 'createUser', [[
            'name' => 'Joe',
            'email' => 'e@mail.ca',
        ]]);

        $this->assertEquals([], $endpoint->getDetail($user)->locations);

        $location = new Location();
        $location->setId(1);
        $user->addHomeLocation($location);
        $this->assertEquals([1], $endpoint->getDetail($user)->locations);
    }

    /**
     * Test we get IDs of users both `friendships` and `friendshipsWithMe`
     */
    public function testUserReturnsConfirmedFriends()
    {
        $user = new User();
        $friend1 = $this->generateUser();
        $friend2 = $this->generateUser();

        $user->addFriend($friend1);
        $friend2->addFriend($user);

        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $this->assertEquals([], $endpoint->getDetail($user)->friends);

        $friend1->getFriendshipsWithMe()[0]->accept();
        $user->getFriendshipsWithMe()[0]->accept();

        $this->assertEqualsCanonicalizing(
            [$friend1->getId(), $friend2->getId()],
            $endpoint->getDetail($user)->friends
        );
    }

    protected function generateUser(): User
    {
        $userClass = new \ReflectionClass(User::class);
        $user = $userClass->newInstance();

        $property = $userClass->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, uniqid());

        return $user;
    }

    // TODO:
    // Need to test against actual DB
    // as per Doctrine recommendations
    // public function testSetLocations()
    // {

    // }
}
