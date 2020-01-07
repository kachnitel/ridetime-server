<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\NotificationsTokenRepository;
use RideTimeServer\Entities\NotificationsToken;
use RideTimeServer\Tests\API\APITestCase;

class NotificationsTokenRepositoryTest extends APITestCase
{
    public function testSetToken()
    {
        /** @var NotificationsTokenRepository $repo */
        $repo = $this->entityManager->getRepository(NotificationsToken::class);
        $tokenString = 'asdf';

        $user1 = $this->generateUser(1);
        $user2 = $this->generateUser(2);

        $repo->setToken($user1, $tokenString);
        /** @var NotificationsToken $token */
        $token = $repo->find($tokenString);

        $this->assertEquals($user1, $token->getUser());

        $repo->setToken($user2, $tokenString);
        $this->assertEquals($user2, $token->getUser());

        $this->assertCount(0, $repo->findBy(['user' => $user1]));
        $this->assertCount(1, $repo->findBy(['user' => $user2]));
    }
}
