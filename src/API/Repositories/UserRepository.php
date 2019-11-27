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
        // TODO: Dedupe from UserEndpoint::update (user->applyProperties should handle location and all)
        if (!empty($data->locations)) {
            /** @var \RideTimeServer\API\Repositories\LocationRepository $locationRepo */
            $locationRepo = $this->getEntityManager()
                ->getRepository(Location::class);
            foreach ($data->locations as $locationId) {
                $user->addLocation($locationRepo->get($locationId));
            }
        }

        return $user;
    }
}
