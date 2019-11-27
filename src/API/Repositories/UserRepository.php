<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\UserException;

class UserRepository extends BaseRepository
{
    /**
     * @param object $data
     * @return User
     */
    public function create(object $data): User
    {
        foreach (['name', 'email', 'authId'] as $prop) {
            if (empty($data->{$prop})) {
                throw new UserException('User creation failed: property ' . $prop . ' is required.', 422);
            }
        }

        $user = new User();
        $user->applyProperties($data);
        if (!empty($data->locations)) {
            $this->setLocations($user, $data->locations);
        }

        return $user;
    }

    public function update(User $user, object $data)
    {
        $user->applyProperties($data);
        if (!empty($data->locations)) {
            $this->setLocations($user, $data->locations);
        }
    }

    protected function setLocations(User $user, array $locations)
    {
        /** @var \RideTimeServer\API\Repositories\LocationRepository $locationRepo */
        $locationRepo = $this->getEntityManager()
            ->getRepository(Location::class);

        foreach ($locations as $locationId) {
            $user->addLocation($locationRepo->get($locationId));
        }
    }

    /**
     * @param string $field
     * @param string $searchTerm
     * @return User[]
     */
    public function search(string $field, string $searchTerm): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('u')->from(User::class, 'u')->where(
            $queryBuilder->expr()->like('u.' . $field, ':text')
        )->setParameter('text', '%' . $searchTerm . '%');

        return $queryBuilder->getQuery()->getResult();
    }
}
