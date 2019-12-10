<?php
namespace RideTimeServer\Tests\Entities;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\Entities\Location;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Tests\API\APITestCase;

class UserTest extends APITestCase
{
    public function testGetDetail()
    {
        $user = $this->generateUser();
        $user->setName('John Doe');
        $user->setHometown('Town, BC');
        $user->setEmail('a@b.com');

        $detail = $user->getDetail();

        $this->assertEquals('John Doe', $detail->name);
        $this->assertEquals('Town, BC', $detail->hometown);
        $this->assertEquals('a@b.com', $detail->email);
    }

    public function testAddLocation()
    {
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

    public function testAddFriend()
    {
        $user = new User();
        $friend = new User();

        $user->addFriend($friend);

        $this->assertInstanceOf(Friendship::class, $user->getFriendships()[0]);
        /** @var Friendship $friendship */
        $friendship = $user->getFriendships()[0];
        $this->assertSame($user, $friendship->getUser());new User();
        $this->assertSame($friend, $friendship->getFriend());
        $this->assertEquals(0, $friendship->getStatus());
        $this->assertContains($friendship, $friend->getFriendshipsWithMe());

        $this->expectException(UserException::class);
        $user->addFriend($user);
    }

    public function testAcceptFriend()
    {
        $user = new User();
        $friend = new User();

        $user->addFriend($friend);
        $friend->acceptFriend($user);

        $friendship = $user->getFriendships()[0];
        $this->assertEquals(1, $friendship->getStatus());
    }

    public function testAcceptFriend404()
    {
        $user = $this->generateUser();
        $friend = $this->generateUser();

        $this->expectException(UserException::class);
        $user->acceptFriend($friend);
    }

    public function testRemoveFriend()
    {
        $user = new User();
        $friendA = new User();
        $friendB = new User();

        $user->addFriend($friendA);
        $this->assertEquals(
            $friendA,
            $user->getFriendships()->first()->getFriend()
        );

        $user->removeFriend($friendA);
        $this->assertEmpty($user->getFriendships());

        $friendB->addFriend($user);
        $this->assertEquals(
            $friendB,
            $user->getFriendshipsWithMe()->first()->getUser()
        );

        $user->removeFriend($friendB);
        $this->assertEmpty($user->getFriendshipsWithMe());
    }

    /**
     * Test we get IDs of users both `friendships` and `friendshipsWithMe`
     */
    public function testDetailReturnsConfirmedFriends()
    {
        $user = $this->generateUser();
        $friend1 = $this->generateUser(1);
        $friend2 = $this->generateUser(2);

        $user->addFriend($friend1);
        $friend2->addFriend($user);

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
     */
    public function testGetDetailEventIdsIsSequential()
    {
        $user = $this->generateUser();
        $event1 = $this->generateEvent(1);
        $event2 = $this->generateEvent(2);
        $event3 = $this->generateEvent(3);

        $user->addEvent($event1->join($user));
        $user->addEvent($middle = $event2->join($user));
        $user->addEvent($event3->join($user));

        // Remove event from the middle to break array order [0 => 1, 2 => 3]
        $user->removeEvent($middle);

        $this->assertEquals([1, 3], $user->getDetail()->events);
    }

    public function testGetFriendships()
    {
        $user = $this->generateUser();
        $friend = $this->generateUser();
        $requested = $this->generateUser();

        $user->addFriend($friend);
        $user->addFriend($requested);
        $friend->acceptFriend($user);

        $this->assertEquals(1, $user->getFriendships(Friendship::STATUS_ACCEPTED)->count());
        $this->assertSame($friend, $user->getFriendships(Friendship::STATUS_ACCEPTED)->first()->getFriend());
    }

    public function testGetFriendshipsWithMe()
    {
        $user = $this->generateUser();
        $friend = $this->generateUser();
        $requested = $this->generateUser();

        $friend->addFriend($user);
        $user->acceptFriend($friend);
        $requested->addFriend($user);

        $this->assertEquals(1, $user->getFriendshipsWithMe(Friendship::STATUS_ACCEPTED)->count());
        $this->assertSame($friend, $user->getFriendshipsWithMe(Friendship::STATUS_ACCEPTED)->first()->getUser());
    }

    public function testGetConfirmedFriends()
    {
        $friend = $this->generateUser();
        $requested = $this->generateUser();
        $user = $this->generateUser();
        $friendWithMe = $this->generateUser();
        $requestedWithMe = $this->generateUser();

        // Requests sent by user
        $user->addFriend($friend);
        $friend->acceptFriend($user);
        $user->addFriend($requested);

        // Requests received
        $friendWithMe->addFriend($user);
        $user->acceptFriend($friendWithMe);
        $requestedWithMe->addFriend($user);

        $friends = $user->getConfirmedFriends();
        $this->assertEqualsCanonicalizing([$friend, $friendWithMe], $friends);
    }
}
