<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\User;

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
}
