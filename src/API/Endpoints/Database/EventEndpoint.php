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

    /**
     * @param integer $eventId
     * @param integer $memberId
     * @return string
     */
    public function invite(int $eventId, int $memberId): string
    {
        $event = $this->get($eventId);
        $user = $this->getUser($memberId);

        $membership = $this->confirmMemberIfStatus($event, $user, Event::STATUS_REQUESTED) ?? $event->invite($user);

        $this->saveEntity($event);
        return $membership->getStatus();
    }

    /**
     * Confirm request/invite if exists for user
     *
     * Returns EventMember or false if membership doesn't exist
     *
     * @param Event $event
     * @param User $user
     * @param string $status
     * @return boolean|EventMember
     */
    protected function confirmMemberIfStatus(Event $event, User $user, string $status)
    {
        $existing = $event->getMembers()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->andWhere(Criteria::expr()->eq('event', $event))
        );
        if (!$existing->isEmpty()) {
            /** @var EventMember $membership */
            $membership = $existing->first();
            if ($membership->getStatus() === $status) {
                $membership->setStatus(Event::STATUS_CONFIRMED);
            }
            return $membership;
        }
        return null;
    }

    protected function getUser(int $userId): User
    {
        return (new UserEndpoint($this->entityManager, $this->logger))
            ->get($userId);
    }
}