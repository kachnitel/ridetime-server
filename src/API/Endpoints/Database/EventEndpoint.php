<?php
namespace RideTimeServer\API\Endpoints\Database;

use Doctrine\Common\Collections\Criteria;
use Monolog\Logger;
use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\User;

class EventEndpoint extends BaseEndpoint implements EntityEndpointInterface
{
    /**
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data): object
    {
        $event = $this->createEvent($data);
        $this->saveEntity($event);

        return $event->getDetail();
    }

    /**
     * TODO: Validate $data
     *
     * @param array $data
     * @return Event
     */
    protected function createEvent(array $data): Event
    {
        // Ride must be created by existing user
        // FIXME: must come from controller/request/current user
        $user = (new UserEndpoint($this->entityManager, $this->logger))
            ->get((int) $data['createdBy']);

        $location = (new LocationEndpoint($this->entityManager, $this->logger))
            ->get($data['location']);

        /** @var Event $event */
        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setDate(new \DateTime($data['datetime']));
        $event->setCreatedBy($user);
        $event->setDifficulty($data['difficulty']);
        $event->setTerrain($data['terrain']);
        $event->setLocation($location);
        if (isset($data['route'])) {
            $event->setRoute($data['route']);
        }

        $membership = new EventMember();
        $membership->setEvent($event);
        $membership->setUser($user);
        $membership->setStatus(Event::STATUS_CONFIRMED);
        // Creating user automatically joins
        $event->addMember($membership);

        return $event;
    }

    /**
     * @param integer $eventId
     * @return Event
     */
    public function get(int $eventId)
    {
        return $this->getEntity(Event::class, $eventId);
    }

    /**
     * @return array[Event]
     */
    public function list(?array $ids): array
    {
        $expr = $ids
            ? Criteria::expr()->in('id', $ids)
            : Criteria::expr()->gt('date', new \DateTime());

        $criteria = Criteria::create()
            ->where($expr)
            ->orderBy(array('date' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(20);

        return $this->listEntities(Event::class, $criteria);
    }

    /**
     * TODO: WIP - add more filters(date!), loop through (allowed) fields and addWhere for each
     *
     * @param array $filters
     * @return array
     */
    public function filter(array $filters): array
    {
        $criteria = Criteria::create()
            ->orderBy(array('date' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(20);

        if (isset($filters['location'])) {
            $locations = [];
            $locationEndpoint = new LocationEndpoint($this->entityManager, $this->logger);
            foreach ($filters['location'] as $locationId) {
                $locations[] = $locationEndpoint->get($locationId);
            }
            $criteria = $criteria->andWhere(Criteria::expr()->in('location', $locations));
        }

        if (isset($filters['difficulty'])) {
            $values = array_map(function ($value) { return (int) $value; }, $filters['difficulty']);
            $criteria = $criteria->andWhere(Criteria::expr()->in('difficulty', $values));
        }

        if (isset($filters['dateStart'])) {
            $this->dateFilter($criteria, $filters['dateStart'], true);
        }

        if (isset($filters['dateEnd'])) {
            $this->dateFilter($criteria, $filters['dateEnd'], false);
        }

        return $this->listEntities(Event::class, $criteria);
    }

    /**
     * Filter by date
     *
     * @param [string|int] $date
     * @param boolean $gte Whether to search for timestamp grater than $date (default **true**)
     * @return void
     */
    protected function dateFilter(Criteria &$criteria, $date, bool $gte = true)
    {
        $dtObject = is_numeric($date)
            ? (new \DateTime())->setTimestamp($date)
            : new \DateTime($date);
        $criteria = $criteria->andWhere($gte
            ? Criteria::expr()->gte('date', $dtObject)
            : Criteria::expr()->lte('date', $dtObject)
        );
    }

    /**
     * @return array[Event]
     */
    public function listInvites(User $user): array
    {
        return $user->getEvents(Event::STATUS_INVITED)
            ->map(function(Event $event) { return $event->getDetail(); })
            ->getValues();
    }

    public function join(int $eventId, int $userId): string
    {
        // REFACTOR: DB
        $event = $this->get($eventId);
        $user = $this->getUser($userId);

        $membershipManager = new MembershipManager();
        $membership = $membershipManager->join($event, $user);

        // REFACTOR: DB
        $this->saveEntity($event);
        return $membership->getStatus();
    }

    /**
     * @param integer $eventId
     * @param integer $memberId
     * @return string
     */
    public function invite(int $eventId, int $memberId): string
    {
        // REFACTOR: DB
        $event = $this->get($eventId);
        $user = $this->getUser($memberId);

        $membershipManager = new MembershipManager();
        $membership = $membershipManager->invite($event, $user);

        // REFACTOR: DB
        $this->saveEntity($event);
        return $membership->getStatus();
    }

    /**
     * TODO:
     * - use $event / $member rather than IDs
     * - three responsibilities here:
     *  - get entities from ids
     *  - remove membership
     *  - commit to db (flush shouldn't be necessary here, just as saveEntity - commit changes in ctrlr after all actions)
     *
     * @param integer $eventId
     * @param integer $memberId
     * @return void
     */
    public function removeMember(int $eventId, int $memberId)
    {
        // REFACTOR: DB
        $event = $this->get($eventId);
        $user = $this->getUser($memberId);

        $membershipManager = new MembershipManager();
        $membership = $membershipManager->removeMember($event, $user);

        // REFACTOR: DB
        $this->entityManager->remove($membership);
        $this->entityManager->flush();
    }

    public function acceptRequest(int $eventId, int $memberId)
    {
        // REFACTOR: DB
        $event = $this->get($eventId);
        $user = $this->getUser($memberId);

        $membershipManager = new MembershipManager();
        $membership = $membershipManager->acceptRequest($event, $user);

        // REFACTOR: DB
        $this->saveEntity($membership);
    }

    protected function getUser(int $userId): User
    {
        return (new UserEndpoint($this->entityManager, $this->logger))
            ->get($userId);
    }
}
