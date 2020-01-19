<?php
namespace RideTimeServer\API\Repositories;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\SecureEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\UserException;

class SecureRepository extends BaseRepository
{
    /**
     * @var User
     */
    protected $currentUser;

    public function get(int $id): PrimaryEntityInterface
    {
        $entity = parent::find($id);

        if (!$entity->isVisible($this->currentUser)) {
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
            return $entity->isVisible($this->currentUser);
        });
    }

    public function matching(Criteria $criteria): Collection
    {
        return parent::matching($criteria)->filter(function (SecureEntityInterface $entity) {
            return $entity->isVisible($this->currentUser);
        });
    }

    public function setCurrentUser(User $currentUser)
    {
        $this->currentUser = $currentUser;

        return $this;
    }
}
