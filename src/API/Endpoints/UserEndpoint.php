<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\Friendship;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;
use Doctrine\ORM\EntityNotFoundException;

class UserEndpoint extends Endpoint implements EndpointInterface
{
    /**
     * FIXME: should return User rather than detail
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data): object
    {
        $user = $this->createUser($data);
        $this->saveEntity($user);

        return $this->getDetail($user);
    }

    /**
     * TODO: Validate input!
     *
     * @param array $data
     * @return User
     */
    protected function createUser(array $data): User
    {
        $user = new User();

        $user->setName($data['name']);
        $user->setEmail($data['email']);
        if (!empty($data['hometown'])) {
            $user->setHometown($data['hometown']);
        }
        if (!empty($data['picture'])) {
            $user->setPicture($data['picture']);
        }
        if (!empty($data['sub'])) {
            if (!in_array($data['sub'], $user->getAuthIds())) {
                $user->addAuthId($data['sub']);
            }
        }
        if (!empty($data['authId'])) {
            if (!in_array($data['authId'], $user->getAuthIds())) {
                $user->addAuthId($data['authId']);
            }
        }

        return $user;
    }

    /**
     * Get user detail
     *
     * @param User $user
     * @return object
     */
    public function getDetail(User $user): object
    {
        return (object) [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'hometown' => $user->getHometown(),
            'events' => $this->getUserEvents($user),
            'friends' => $this->getFriends($user),
            'level' => $user->getLevel(),
            'preferred' => $user->getFavTerrain(),
            'favourites' => $user->getFavourites(),
            'picture' => $user->getPicture(),
            'email' => $user->getEmail()
        ];
    }

    /**
     * Load user from database
     *
     * @param integer $userId
     * @return User
     */
    public function get(int $userId): User
    {
        return $this->getEntity(User::class, $userId);
    }

    /**
     * Find events for user
     *
     * @param User $user
     * @return array
     */
    protected function getUserEvents(User $user): array
    {
        $events = [];
        /** @var Event $event */
        foreach ($user->getEvents() as $event) {
            $events[] = (object) [
                'id' => $event->getId(),
                'datetime' => $event->getDate()->format(\DateTime::ATOM),
                'title' => $event->getTitle()
            ];
        }

        return $events;
    }

    /**
     * Get friends list for an user
     *
     * @param User $user
     * @return array
     */
    protected function getFriends(User $user): array
    {
        $friends = [];
        /** @var Friendship $friendship */
        foreach ($user->getFriendships() as $friendship) {
            $friend = $friendship->getFriend();
            $friends[] = (object) [
                'id' => $friend->getId(),
                'name' => $friend->getName()
            ];
        }

        return $friends;
    }

    public function findBy(string $attribute, string $value): User
    {
        if ($attribute === 'email') {
            $results = $this->entityManager->getRepository(User::class)->findByEmail($value);
            if (empty($results)) {
                throw new EntityNotFoundException('User with e-mail ' . $value . ' doesn\'t exist');
            }
            return $results[0];
        } else {
            throw new \Exception('User search by ' . $attribute . ' not supported');
        }
    }
}
