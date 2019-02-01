<?php
namespace RideTimeServer\API;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;

class UserEndpoint
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUserDetail(): object
    {
        return (object) [
            'id' => $this->user->getId(),
            'name' => $this->user->getName(),
            'events' => $this->getEvents($this->user)
        ];
    }

    protected function getEvents(User $user): array
    {
        $events = [];
        /** @var Event $event */
        foreach ($user->getEvents() as $event) {
            $events[] = (object) [
                'id' => $event->getId(),
                'datetime' => $event->getDate()->format(\DateTime::ATOM),
                'title' => $event->getTitle()
            ];
        }

        return $events;
    }
}
