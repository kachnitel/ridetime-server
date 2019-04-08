<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use RideTimeServer\Exception\EntityNotFoundException;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Exception\RTException;

class UserEndpoint extends Endpoint implements EndpointInterface
{
    /**
     * Load user from database
     *
     * @param integer $userId
     * @return User
     */
    public function get(int $userId)
    {
        return $this->getEntity(User::class, $userId);
    }

    /**
     * @return array[User]
     */
    public function list(): array
    {
        $criteria = Criteria::create()
            // ->where(Criteria::expr()->gt('date', new \DateTime()))
            // ->orderBy(array('date' => Criteria::ASC))
            // ->setFirstResult(0)
            ->setMaxResults(20);

        return $this->listEntities(User::class, [$this, 'getDetail'], $criteria);
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
    public function update(User $user, array $data): User
    {
        $result = $this->performUpdate($user, $data);

        $this->saveEntity($result);
        return $result;
    }

    /**
     * @param User $user
     * @param array $data
     * @param string $authId
     * @return User
     */
    public function performUpdate(User $user, array $data): User
    {
        $userEditableProperties = [
            'name',
            'email',
            'phone',
            'picture',
            'hometown',
            'level',
            'bike',
            'favourites'
        ];

        foreach ($userEditableProperties as $property) {
            $method = $this->getSetterMethod($user, $property);

            if (!empty($data[$property])) {
                // TODO: validate
                $user->{$method}((string) $data[$property]);
            }
        }

        if (isset($data['locations'])) {
            $this->setHomeLocations($user, $data['locations']);
        }

        return $user;
    }

    /**
     * @param integer $userId
     * @param integer $friendId
     * @return User
     */
    public function addFriend(int $userId, int $friendId): User
    {
        $user = $this->get($userId);
        $friend = $this->get($friendId);

        $user->addFriend($friend);

        $this->saveEntity($user);

        return $user;
    }

    /**
     * @param integer $userId
     * @param integer $friendId
     * @return User
     */
    public function acceptFriend(int $userId, int $friendId): User
    {
        // To keep consistent, $user is requesting while $friend is logged in
        $user = $this->get($userId);
        $friend = $this->get($friendId);

        $friend->acceptFriend($user);

        $this->saveEntity($friend);

        return $friend;
    }

    public function removeFriend(int $userId, int $friendId)
    {
        $user = $this->get($userId);
        $friend = $this->get($friendId);

        $fs = $user->removeFriend($friend);

        $this->entityManager->remove($fs);
        $this->entityManager->flush();

        return true;
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

        // Basic (scalar) properties
        // Array, value is whether field is mandatory
        $properties = [
            'name' => true,
            'email' => true,
            'phone' => false,
            'hometown' => false,
            'picture' => false,
            'authId' => true,
            'level' => false,
            'bike' => false,
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

        if (!empty($data['locations'])) {
            $this->setHomeLocations($user, $data['locations']);
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
            'bike' => $user->getBike(),
            'favourites' => $user->getFavourites(),
            'picture' => $user->getPicture(),
            'email' => $user->getEmail(),
            'locations' => $this->getHomeLocations($user)
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
            $events[] = $event->getId();
        }

        return $events;
    }

    /**
     * Get friends list for an user
     *
     * @param User $user
     * @return User[]
     */
    protected function getFriends(User $user): array
    {
        $friends = [];
        /** @var Friendship $friendship */
        foreach ($user->getFriendships() as $friendship) {
            if ($friendship->getStatus() === 1) {
                $friends[] = $friendship->getFriend()->getId();
            }
        }

        /** @var Friendship $friendship */
        foreach ($user->getFriendshipsWithMe() as $friendship) {
            if ($friendship->getStatus() === 1) {
                $friends[] = $friendship->getUser()->getId();
            }
        }

        return $friends;
    }

    /**
     * @param User $user
     * @return int[]
     */
    protected function getHomeLocations(User $user): array
    {
        $locations = [];

        if (!empty($user->getHomeLocations())) {
            /** @var \RideTimeServer\Entities\Location $location */
            foreach ($user->getHomeLocations() as $location) {
                $locations[] = $location->getId();
            }
        }

        return $locations;
    }

    /**
     * Find an user by $attribute
     * currently only email is supported
     *
     * @param string $attribute
     * @param string $value
     * @return User
     */
    public function findBy(string $attribute, string $value): User
    {
        try {
            $result = $this->entityManager->getRepository(User::class)->findOneBy([$attribute => $value]);
        } catch (\Doctrine\ORM\ORMException $e) {
            throw new RTException("Error looking up User by {$attribute} = {$value}", 0, $e);
        }
        if (empty($result)) {
            throw new EntityNotFoundException("User with {$attribute} = {$value} not found", 404);
        }
        return $result;
    }

    /**
     * @param User $user
     * @param array $locationIds
     * @return void
     */
    protected function setHomeLocations(User $user, array $locationIds)
    {
        !empty($user->getHomeLocations()) && $user->getHomeLocations()->clear();

        $locationEndpoint = new LocationEndpoint($this->entityManager, $this->logger);
        foreach ($locationIds as $locationId) {
            $user->addHomeLocation($locationEndpoint->get($locationId));
        }
    }
}
