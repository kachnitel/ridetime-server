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

        return $this->getDetail($event);
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
     * Get event detail
     *
     * @param Event $event
     * @return object
     */
    public function getDetail(Event $event): object
    {
        return (object) [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'members' => $this->getEventMembers($event),
            'difficulty' => $event->getDifficulty(),
            'location' => (object) [
                'id' => $event->getLocation()->getId(),
                'name' => $event->getLocation()->getName(),
                'gps' => [
                    $event->getLocation()->getGpsLat(),
                    $event->getLocation()->getGpsLon()
                ]
            ],
            'terrain' => $event->getTerrain(),
            'route' => $event->getRoute(),
            'datetime' => $event->getDate()->getTimestamp()
        ];
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

        return $this->listEntities(Event::class, [$this, 'getDetail'], $criteria);
    }

    /**
     * Returns thumbnails of confirmed users
     *
     * @param Event $event
     * @return array
     */
    protected function getEventMembers(Event $event): array
    {
        $members = [];
        /** @var \RideTimeServer\Entities\EventMember $member */
        foreach ($event->getMembers() as $member) {
            if ($member->getStatus() !== Event::STATUS_CONFIRMED) {
                continue;
            }
            /** @var \RideTimeServer\Entities\User $user */
            $user = $member->getUser();
            $members[] = (object) [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'picture' => $user->getPicture()
            ];
        }

        return $members;
    }

    public function join(int $eventId, int $userId): string
    {
        // REFACTOR: DB
        $event = $this->get($eventId);
        $user = $this->getUser($userId);

        // REFACTOR: Action MembershipManager
        $membership = $this->confirmMemberIfStatus($event, $user, Event::STATUS_INVITED) ?? $event->join($user);

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

        // REFACTOR: Action MembershipManager
        $membership = $this->confirmMemberIfStatus($event, $user, Event::STATUS_REQUESTED) ?? $event->invite($user);

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

        // REFACTOR: Action MembershipManager
        $membershipManager = new MembershipManager();
        $membership = $membershipManager->removeMember($event, $user);

        // REFACTOR: DB
        $this->entityManager->remove($membership);
        $this->entityManager->flush();
    }

    protected function getUser(int $userId): User
    {
        return (new UserEndpoint($this->entityManager, $this->logger))
            ->get($userId);
    }
}
