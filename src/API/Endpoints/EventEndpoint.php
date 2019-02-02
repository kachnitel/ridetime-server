<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use RideTimeServer\Entities\Event;

class EventEndpoint extends Endpoint implements EndpointInterface
{
    /**
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data, Logger $logger): object
    {
        throw new \Exception('event add not implemented');
    }

    /**
     * Get event detail
     *
     * @param integer $userId
     * @return object
     */
    public function getDetail(int $userId): object
    {
        $event = $this->getEvent($userId);

        return (object) [
            'id' => $event->getId(),
            'name' => $event->getTitle(),
            'members' => $members
        ];
    }

    /**
     * FIXME: WET as it gets.
     *
     * @param integer $eventId
     * @return Event
     */
    protected function getEvent(int $eventId): Event
    {
        /** @var Event $event */
        $event = $this->entityManager->find('RideTimeServer\Entities\Event', $eventId);

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
    }
}
