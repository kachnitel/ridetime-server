<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\User;

class NotificationsTokenRepository extends BaseRepository
{
    public function setToken(User $user, string $tokenString)
    {
        $token = $this->find($tokenString) ?? new NotificationsToken($tokenString);
        $token->setUser($user);

        $this->saveEntity($token);
    }
}
