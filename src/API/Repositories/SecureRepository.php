<?php
namespace RideTimeServer\API\Repositories;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\SecureEntityInterface;
use RideTimeServer\Exception\UserException;
use RideTimeServer\UserProvider;

class SecureRepository extends BaseRepository
{
    /**
     * @var UserProvider
     */
    protected $provider;

    public function get(int $id): PrimaryEntityInterface
    {
        $entity = parent::find($id);

        if (!$entity->isVisible($this->provider->getCurrentUser())) {
            throw new UserException("{$this->getClassShortName()} {$id} is not visible to current user", 403);
        }

        return $entity;
    }

    /**
     * @param array $ids
     * @return Collection
     */
    public function list(array $ids = null): Collection
    {
        return parent::list($ids)->filter(function (SecureEntityInterface $entity) {
            return $entity->isVisible($this->provider->getCurrentUser());
        });
    }

    public function matching(Criteria $criteria): Collection
    {
        return parent::matching($criteria)->filter(function (SecureEntityInterface $entity) {
            return $entity->isVisible($this->provider->getCurrentUser());
        });
    }

    public function setUserProvider(UserProvider $provider)
    {
        $this->provider = $provider;

        return $this;
    }
}
