<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
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

    protected function createEvent(array $data): Event
    {
        // Ride must be created by existing user
        $user = (new UserEndpoint($this->entityManager, $this->logger))
            ->get($data['created_by']);

        /** @var Event $event */
        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description']);
        $event->setDate(new \DateTime($data['datetime']));
        $event->setCreatedBy($user);
        // Creating user automatically joins
        $event->addUser($user);

        return $event;
    }

    /**
     * Get event detail
     *
     * @param integer $userId
     * @return object
     */
    public function getDetail(EntityInterface $event): object
    {
        return (object) [
            'id' => $event->getId(),
            'name' => $event->getTitle(),
            'members' => $this->getEventMembers($event)
        ];
    }

    /**
     * FIXME: WET as it gets.
     *
     * @param integer $eventId
     * @return Event
     */
    public function get(int $eventId): EntityInterface
    {
        /** @var Event $event */
        $event = $this->entityManager->find(Event::class, $eventId);

        if (empty($event)) {
            // TODO: Throw EventNotFoundException
            throw new \Exception('Event ID:' . $userId . ' not found', 404);
        }

        return $event;
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
                'name' => $user->getName(),
                'profilePic' => $user->getProfilePicUrl()
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

    public function list(): array
    {
        $events = $this->entityManager->getRepository(Event::class)->findAll();

        $result = [];
        foreach ($events as $event) {
            $result[] = $this->getDetail($event);
        }

        return $result;
    }
}
