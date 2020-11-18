<?php
namespace RideTimeServer\Entities;

use RideTimeServer\Entities\Traits\GpsTrait;
use RideTimeServer\Entities\Traits\IdTrait;
use RideTimeServer\Entities\Traits\TimestampTrait;

/**
 * @Entity
 * @Table(name="user_location_tracking")
 */
class UserLocation implements PrimaryEntityInterface
{
    use IdTrait;
    use GpsTrait;
    use TimestampTrait;

    const VISIBILITY_FRIENDS = 'friends';
    const VISIBILITY_EVENT = 'event';
    const VISIBILITY_EMERGENCY = 'emergency';

    /**
     * @var User
     *
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @Column(type="string", length=16)
     *
     * @var string
     */
    private $visibility = self::VISIBILITY_FRIENDS;

    /**
     * @Column(type="string")
     *
     * Generated when tracking is started
     * Allows to delete when tracking stops
     * {userId}_{eventId?}_hash
     *
     * @var string
     */
    private $sessionId;

    /**
     * @var Event
     *
     * @ManyToOne(targetEntity="Event")
     * @JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $event;

    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'coords' => [
                $this->getGpsLat(),
                $this->getGpsLon()
            ],
            'timestamp' => $this->getTimestamp()->getTimestamp(),
            'user' => $this->getUser()->getId(),
            'visibility' => $this->getVisibility(),
            'event' => $this->getEvent() ? $this->getEvent()->getId() : null
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'user' => [$this->getUser()->getDetail()],
            'event' => [$this->getEvent()->getDetail()]
        ];
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event)
    {
        $this->event = $event;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }
}
