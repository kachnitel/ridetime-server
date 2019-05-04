<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\NotificationsToken;
use RideTimeServer\Entities\User;
use RideTimeServer\API\Endpoints\EndpointInterface;

class NotificationsEndpoint extends BaseEndpoint implements EndpointInterface
{
    public function setToken(User $user, string $tokenString)
    {
        $repo = $this->entityManager->getRepository(NotificationsToken::class);
        $token = $repo->find($tokenString) ?? new NotificationsToken($tokenString);
        $token->setUser($user);

        $this->saveEntity($token);
    }
}
