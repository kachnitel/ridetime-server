<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\Database\UserEndpoint;
use Monolog\Logger;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Location;
use RideTimeServer\Tests\API\APITestCase;

class UserEndpointTest extends APITestCase
{
    public function testGetDetailReturnsBasicInfo()
    {
        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $user = $this->generateUser();
        $user->setName('John Doe');
        $user->setHometown('Town, BC');
        $user->setEmail('a@b.com');

        $detail = $user->getDetail();

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
            'hometown' => 'Whistler, BC',
            'authId' => '123'
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
            'authId' => 123
        ]]);

        $endpoint->performUpdate($user, ['name' => 'Joseph'], '123');

        $this->assertEquals('Joseph', $user->getName());
        // Ensure this hasn't changed
        $this->assertEquals('e@mail.ca', $user->getEmail());
    }

    public function testGetLocations()
    {
        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        /** @var User $user */
        $user = $this->generateUser();
        $user->setName('Joe');
        $user->setEmail('e@mail.ca');
        $user->setAuthId('123a');

        $this->assertEquals([], $user->getDetail()->locations);

        $location = new Location();
        $location->setId(1);
        $user->addLocation($location);
        $this->assertEquals([1], $user->getDetail()->locations);
    }

    /**
     * Test we get IDs of users both `friendships` and `friendshipsWithMe`
     */
    public function testUserReturnsConfirmedFriends()
    {
        $user = $this->generateUser();
        $friend1 = $this->generateUser(1);
        $friend2 = $this->generateUser(2);

        $user->addFriend($friend1);
        $friend2->addFriend($user);

        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        $this->assertEquals([], $user->getDetail()->friends);

        $friend1->getFriendshipsWithMe()[0]->accept();
        $user->getFriendshipsWithMe()[0]->accept();

        $this->assertEqualsCanonicalizing(
            [$friend1->getId(), $friend2->getId()],
            $user->getDetail()->friends
        );
    }

    /**
     * Ensure IDs are a sequential array to ensure correct format in JSON
     *
     * @return void
     */
    public function testGetUserEventIdsFormat()
    {
        $user = $this->generateUser();
        $event1 = $this->generateEvent(1);
        $event2 = $this->generateEvent(2);
        $event3 = $this->generateEvent(3);

        $user->addEvent($event1->join($user));
        $user->addEvent($middle = $event2->join($user));
        $user->addEvent($event3->join($user));

        $endpoint = new UserEndpoint($this->entityManager, new Logger('test'));

        // Remove event from the middle to break array order
        $user->removeEvent($middle);

        $eventIDs = $user->getDetail()->events;
        $this->assertEquals(array_keys($eventIDs), array_keys(array_slice($eventIDs, 0)));
    }

    // TODO:
    // Need to test against actual DB
    // as per Doctrine recommendations
    // public function testSetLocations()
    // {

    // }
}
