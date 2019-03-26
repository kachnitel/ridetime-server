<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;

class UserTest extends TestCase
{
    public function testDeleteAuthId()
    {
        $user = new User();

        $user->addAuthId('1');
        $user->addAuthId('2');
        $user->addAuthId('3');

        $user->deleteAuthId('2');

        $this->assertEqualsCanonicalizing($user->getAuthIds(), ['1', '3']);
    }

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
}
