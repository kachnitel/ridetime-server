<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Monolog\Logger;

use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EntityInterface;

class EventEndpoint extends Endpoint implements EndpointInterface
{
    /**
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data, Logger $logger): object
    {
        $event = $this->createEvent($data);
        $this->saveEntity($event);

        return $this->getDetail($event);
    }

    /**
     * @param array $data
     * @return Event
     */
    protected function createEvent(array $data): Event
    {
        // Ride must be created by existing user
        $user = (new UserEndpoint($this->entityManager, $this->logger))
            ->get($data['createdBy']);

        $location = (new LocationEndpoint($this->entityManager, $this->logger))
            ->get($data['locationId']);

        /** @var Event $event */
        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setDate(new \DateTime($data['datetime']));
        $event->setCreatedBy($user);
        $event->setDifficulty($data['difficulty']);
        $event->setRoute($data['route']);
        $event->setTerrain($data['terrain']);
        $event->setLocation($location);
        // Creating user automatically joins
        $event->addUser($user);

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
    public function get(int $eventId): Event
    {
        return $this->getEntity(Event::class, $eventId);
    }

    /**
     * @param Event $event
     * @return array
     */
    protected function getEventMembers(Event $event): array
    {
        $members = [];
        /** @var \RideTimeServer\Entities\User $user */
        foreach ($event->getUsers() as $user) {
            $members[] = (object) [
                'id' => $user->getId(),
                'name' => $user->getName()
            ];
        }

        return $members;
    }

    /**
     * Returns updated members list
     *
     * @param Event $event
     * @param integer $memberID
     * @return array
     */
    public function addEventMember(Event $event, int $memberID): object
    {
        $user = $user = (new UserEndpoint($this->entityManager, $this->logger))
            ->get($memberID);

        $event->addUser($user);

        $this->entityManager->persist($event);

        $this->entityManager->flush();

        return $this->getDetail($event);
    }

    /**
     * @return array[Event]
     */
    public function list(): array
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->gt('date', new \DateTime()))
            ->orderBy(array('date' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(20);

        return $this->listEntities(Event::class, [$this, 'getDetail'], $criteria);
    }
}
