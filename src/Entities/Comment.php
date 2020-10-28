<?php
namespace RideTimeServer\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RideTimeServer\Entities\Traits\TimestampTrait;

/**
 * @Entity
 * @Table(name="comment")
 */
class Comment extends PrimaryEntity implements PrimaryEntityInterface
{
    use TimestampTrait;

    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @var User
     *
     * @ManyToOne(targetEntity="User")
     */
    private $author;

    /**
     * @var Event
     *
     * @ManyToOne(targetEntity="Event")
     */
    private $event;

    /**
     * @var string
     *
     * @Column(type="string", length=2048)
     */
    private $message;

    /**
     * @var ArrayCollection|User[]
     *
     * @ManyToMany(targetEntity="User")
     * @JoinTable(name="comment_seen_by")
     */
    private $seenBy;

    public function __construct()
    {
        $this->seenBy = new ArrayCollection();
    }

    /**
     * Get the value of id
     *
     * @return  int
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Get the value of author
     *
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * Set the value of author
     *
     * @param User $author
     *
     * @return self
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get the value of event
     *
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Set the value of event
     *
     * @param Event $event
     *
     * @return self
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get the value of message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message
     *
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of seenBy
     *
     * @return ArrayCollection|User[]
     */
    public function getSeenBy()
    {
        return $this->seenBy;
    }

    /**
     * Set the value of seenBy
     *
     * @param ArrayCollection|User[] $seenBy
     *
     * @return self
     */
    public function setSeenBy($seenBy)
    {
        $this->seenBy = $seenBy;

        return $this;
    }

    /**
     * @param User $user
     *
     * @return self
     */
    public function addSeenBy(User $user)
    {
        $this->seenBy->add($user);

        return $this;
    }

    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'author' => $this->getAuthor()->getId(),
            'event' => $this->getEvent()->getId(),
            'message' => $this->getMessage(),
            'timestamp' => $this->getTimestamp()->getTimestamp(),
            'seenBy' => $this->getSeenBy()
                ->map(function (User $user) { return $user->getId(); })
                ->getValues()
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'user' => [$this->getAuthor()->getDetail()],
            'event' => [$this->getEvent()->getDetail()]
        ];
    }
}
