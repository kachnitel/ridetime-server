<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use Doctrine\ORM\EntityNotFoundException;

class UserEndpoint extends Endpoint implements EndpointInterface
{
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
     * FIXME: should return User(/Event/...) rather than detail
     * @param array $data
     * @return object
     */
    public function add(array $data): object
    {
        $user = $this->createUser($data);
        $this->saveEntity($user);

        return $this->getDetail($user);
    }

    /**
     * Update $user with $data
     * Uses $authId to verify identity
     *
     * @param User $user
     * @param array $data
     * @param string $authId
     *
     * @return User
     */
    public function update(User $user, array $data, string $authId): User
    {
        $result = $this->performUpdate($user, $data, $authId);

        $this->saveEntity($result);
        return $result;
    }

    /**
     * @param User $user
     * @param array $data
     * @param string $authId
     * @return User
     */
    public function performUpdate(User $user, array $data, string $authId): User
    {
        if (!in_array($authId, $user->getAuthIds())) {
            throw new \Exception('Trying to update other user than self.', 403);
        }

        $userEditableProperties = [
            'name',
            'email',
            'phone',
            'picture',
            'hometown',
            'level',
            'favTerrain',
            'favourites'
        ];

        foreach ($userEditableProperties as $property) {
            $method = $this->getSetterMethod($user, $property);

            if (!empty($data[$property])) {
                // TODO: validate
                $user->{$method}((string) $data[$property]);
            }
        }

        return $user;
    }

    public function addAuthId(User $user, string $authId)
    {
        $user->addAuthId($authId);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
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

        // Array, value is whether field is mandatory
        $properties = [
            'name' => true,
            'email' => true,
            'phone' => false,
            'hometown' => false,
            'picture' => false,
            'authIds' => false,
            'level' => false,
            'favTerrain' => false,
            'favourites' => false
        ];

        foreach ($properties as $property => $req) {
            $method = $this->getSetterMethod($user, $property);

            if (empty($data[$property])) {
                if ($req) {
                    throw new \Exception('User creation failed: property ' . $property . ' is required.', 400);
                }
                continue;
            }
            $user->{$method}((string) $data[$property]);
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

    protected function getSetterMethod(User $user, string $property): string
    {
        $method = 'set' . ucfirst($property);
        if (!method_exists($user, $method)) {
            throw new \RuntimeException('Trying to update User with non-existing method ' . $method);
        }

        return $method;
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
            /** @var User $friend */
            $friend = $friendship->getFriend();
            $friends[] = (object) [
                'id' => $friend->getId(),
                'name' => $friend->getName(),
                'picture' => $friend->getPicture()
            ];
        }

        return $friends;
    }

    public function findBy(string $attribute, string $value): User
    {
        if ($attribute === 'email') {
            $results = $this->entityManager->getRepository(User::class)->findByEmail($value);
            if (empty($results)) {
                throw new EntityNotFoundException('User with e-mail ' . $value . ' doesn\'t exist', 404);
            }
            return $results[0];
        } else {
            throw new \Exception('User search by ' . $attribute . ' not supported');
        }
    }
}
