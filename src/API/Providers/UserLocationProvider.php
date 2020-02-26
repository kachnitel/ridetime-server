<?php
namespace RideTimeServer\API\Providers;

use Doctrine\ORM\EntityRepository;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\UserLocation;

class UserLocationProvider
{
    const DEFAULT_TTL = 15 * 60;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var EntityRepository
     */
    protected $repo;

    public function __construct(EntityRepository $repo) {
        $this->repo = $repo;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * List locations visible to user
     *
     * 1) user's friends' locations
     * 2) events where user is confirmed member
     * 3) emergency (filter nearby?) longer TTL or until disabled
     *
     * @param integer $ttl
     * @return void
     */
    public function list(int $ttl = self::DEFAULT_TTL)
    {
        /**
         */
        $qb = $this->repo->createQueryBuilder('ul');

        $conditions = [
            $qb->expr()->eq('ul.visibility', ':v_emergency')
        ];
        $parameters = [
            'v_emergency' => UserLocation::VISIBILITY_EMERGENCY
        ];

        // Friends' locations
        if (count($this->user->getConfirmedFriends()) > 0) {
            $conditions[] = $qb->expr()->andX(
                $qb->expr()->eq('ul.visibility', ':v_friends'),
                $qb->expr()->in('ul.user', $this->user->getConfirmedFriends())
            );
            $parameters['v_friends'] = UserLocation::VISIBILITY_FRIENDS;
        }

        // Event locations
        if (count($this->user->getEvents(Event::STATUS_CONFIRMED)) > 0) {
            $conditions[] = $qb->expr()->andX(
                $qb->expr()->eq('ul.visibility', ':v_event'),
                $qb->expr()->in('ul.event', $this->user->getEvents(Event::STATUS_CONFIRMED)->getValues())
            );
            $parameters['v_event'] = UserLocation::VISIBILITY_EVENT;
        }

        $qb->select(['ul'])
            ->where($qb->expr()->orX(...$conditions))
            ->andWhere($qb->expr()->gte('ul.timestamp', time() - $ttl))
            ->orderBy('ul.timestamp', 'DESC');

        $qb->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }
}
