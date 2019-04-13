<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use RideTimeServer\Exception\EntityNotFoundException;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Event;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Entities\Friendship;

class UserEndpoint extends BaseEndpoint implements EndpointInterface
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
    public function list(?array $ids): array
    {
        $criteria = Criteria::create()
            ->setMaxResults(20);

        if ($ids) {
            $criteria->where(Criteria::expr()->in('id', $ids));
        }

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
     *
     * @param User $user
     * @param array $data
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
     * @return User
     */
    public function performUpdate(User $user, array $data): User
    {
        $user->applyProperties($data);
        if (!empty($data['locations'])) {
            $this->setLocations($user, $data['locations']);
        }

        return $user;
    }

    /**
     * @param integer $userId Logged in user
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
     * @param integer $friendId Logged in user
     * @return User
     */
    public function acceptFriend(int $userId, int $friendId): User
    {
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
        foreach (['name', 'email', 'authId'] as $prop) {
            if (empty($data[$prop])) {
                throw new UserException('User creation failed: property ' . $prop . ' is required.', 422);
            }
        }

        $user = new User();
        $user->setAuthId($data['authId']);
        $user->applyProperties($data);
        if (!empty($data['locations'])) {
            $this->setLocations($user, $data['locations']);
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
            'events' => $this->getUserEventIds($user),
            'friends' => $this->getFriends($user),
            'level' => $user->getLevel(),
            'bike' => $user->getBike(),
            'favourites' => $user->getFavourites(),
            'picture' => $user->getPicture(),
            'email' => $user->getEmail(),
            'locations' => $this->getLocationIds($user)
        ];
    }

    /**
     * Find events for user
     *
     * @param User $user
     * @return int[]
     */
    protected function getUserEventIds(User $user): array
    {
        return $user->getEvents()->map(function(Event $event) {
            return $event->getId();
        })->toArray();;
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
        $filter = function(Friendship $friendship) {
            return $friendship->getStatus() === 1;
        };

        /** @var Friendship $friendship */
        foreach ($user->getFriendships()->filter($filter) as $friendship) {
            $friends[] = $friendship->getFriend()->getId();
        }

        /** @var Friendship $friendship */
        foreach ($user->getFriendshipsWithMe()->filter($filter) as $friendship) {
            $friends[] = $friendship->getUser()->getId();
        }

        return $friends;
    }

    /**
     * @param User $user
     * @return int[]
     */
    protected function getLocationIds(User $user): array
    {
        return $user->getLocations()->map(function(Location $location) {
            return $location->getId();
        })->toArray();
    }

    /**
     * Find an user by $attribute
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
    protected function setLocations(User $user, array $locationIds)
    {
        !empty($user->getLocations()) && $user->getLocations()->clear();

        $locationEndpoint = new LocationEndpoint($this->entityManager, $this->logger);
        foreach ($locationIds as $locationId) {
            $user->addLocation($locationEndpoint->get($locationId));
        }
    }
}
