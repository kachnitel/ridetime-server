<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\Exception\UserException;

class UserTest extends TestCase
{
    public function testAddFriend()
    {
        $user = new User();
        $friend = new User();

        $user->addFriend($friend);

        $this->assertInstanceOf(Friendship::class, $user->getFriendships()[0]);
        /** @var Friendship $friendship */
        $friendship = $user->getFriendships()[0];
        $this->assertSame($user, $friendship->getUser());
        $this->assertSame($friend, $friendship->getFriend());
        $this->assertEquals(0, $friendship->getStatus());
        $this->assertContains($friendship, $friend->getFriendshipsWithMe());
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
        $user = new User();
        $friend = new User();

        $this->expectException(UserException::class);
        $user->acceptFriend($friend);
    }
}
