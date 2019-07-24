<?php
namespace RideTimeServer\Entities;

/**
 * @Entity
 * @Table(name="event_members")
 */
class EventMember implements EntityInterface
{
    /**
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="events")
     * @Id
     */
    private $user;

    /**
     * @var Event
     * @ManyToOne(targetEntity="Event", inversedBy="members")
     * @Id
     */
    private $event;

    /**
     * invited
     * requested
     * confirmed
     *
     * @Column(type="string", length=10)
     */
    private $status;

    /**
     * Get the value of event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * Set the value of event
     *
     * @return self
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return self
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return self
     */
    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Accept eventship
     *
     * @return self
     */
    public function accept()
    {
        return $this->confirm();
    }

    public function confirm()
    {
        $this->setStatus(Event::STATUS_CONFIRMED);

        return $this;
    }

    /**
     * DEBUG:
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->asObject());
    }

    public function asObject()
    {
        return (object) [
            'userId' => $this->user->getId(),
            'eventId' => $this->event->getId(),
            'status' => $this->status
        ];
    }
}