<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\User;
use RideTimeServer\Exception\EntityNotFoundException;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\API\Endpoints\EntityEndpointInterface;

class UserEndpoint extends BaseEndpoint implements EntityEndpointInterface
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

        return $this->listEntities(User::class, $criteria);
    }

    public function search(string $field, string $text): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('u')->from(User::class, 'u')->where(
            $queryBuilder->expr()->like('u.' . $field, ':text')
        )->setParameter('text', '%' . $text . '%');

        $results = [];
        foreach ($queryBuilder->getQuery()->getResult() as $item) {
            $results[] = $item->getDetail();
        }
        return $results;
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

        return $user->getDetail();
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
     * Friendship management
     */


    /**
     * @param integer $userId Logged in user
     * @param integer $friendId
     * @return Friendship
     */
    public function addFriend(int $userId, int $friendId): Friendship
    {
        $user = $this->get($userId);
        $friend = $this->get($friendId);

        $fs = $user->addFriend($friend);

        $this->saveEntity($user);

        return $fs;
    }

    /**
     * @param integer $userId
     * @param integer $friendId Logged in user
     * @return User
     */
    public function acceptFriend(int $userId, int $friendId): Friendship
    {
        $user = $this->get($userId);
        $friend = $this->get($friendId);

        $friendship = $friend->acceptFriend($user);

        $this->saveEntity($friend);

        return $friendship;
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
     * TODO:
     * Functions below would be better suited in User?
     */

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
