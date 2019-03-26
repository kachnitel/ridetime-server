<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;

class FriendshipTest extends TestCase
{
    public function testFriendshipPropagatesToUser()
    {
        $user = new User();
        $friend = new User();

        $user->addFriend($friend);

        $this->assertInstanceOf(Friendship::class, $user->getFriendships()[0]);
        $this->assertSame($user->getFriendships()[0], $friend->getFriendshipsWithMe()[0]);

        $this->assertSame($friend, $user->getFriendships()[0]->getFriend());
        $this->assertSame($user, $user->getFriendships()[0]->getUser());
    }
}
