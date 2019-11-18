<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\NotificationsToken;
use RideTimeServer\Entities\User;
use RideTimeServer\API\Endpoints\EndpointInterface;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Exception\RTException;

class NotificationsEndpoint extends BaseEndpoint implements EndpointInterface
{
    const ENTITY_CLASS = NotificationsToken::class;

    public function setToken(User $user, string $tokenString)
    {
        $repo = $this->entityManager->getRepository(NotificationsToken::class);
        $token = $repo->find($tokenString) ?? new NotificationsToken($tokenString);
        $token->setUser($user);

        $this->saveEntity($token);
    }

    protected function create(array $data, User $currentUser): PrimaryEntity
    {
        throw new RTException('NotificationsEndpoint::create is not supported', 500);
        return new PrimaryEntity(); // Make IDE shut up for now
    }
}
