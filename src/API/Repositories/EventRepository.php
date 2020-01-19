<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\User;

class EventRepository extends SecureRepository
{
    /**
     * TODO: validate
     *
     * @param object $data
     * @param User $createdBy
     * @return Event
     */
    public function create(object $data, User $createdBy): Event
    {
        $location = $this->getEntityManager()->find(Location::class, (int) $data->location);

        /** @var Event $event */
        $event = new Event();
        $event->setTitle($data->title);
        $event->setDescription($data->description);
        $event->setDate(new \DateTime($data->datetime));
        $event->setCreatedBy($createdBy);
        $event->setDifficulty($data->difficulty);
        $event->setTerrain($data->terrain);
        $event->setLocation($location);
        if (isset($data->route)) {
            $event->setRoute($data->route);
        }
        if (isset($data->visibility)) {
            $event->setVisibility($data->visibility);
        }
        if (isset($data->private)) {
            $event->setPrivate($data->private);
        }

        $membership = new EventMember();
        $membership->setEvent($event);
        $membership->setUser($createdBy);
        $membership->setStatus(Event::STATUS_CONFIRMED);
        // Creating user automatically joins
        $event->addMember($membership);

        return $event;
    }
}
