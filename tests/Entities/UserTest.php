<?php
namespace RideTimeServer\Tests\Entities;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Tests\API\APITestCase;

class UserTest extends APITestCase
{
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
}
