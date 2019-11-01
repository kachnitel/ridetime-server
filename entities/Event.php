<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Collection;
use \Doctrine\ORM\PersistentCollection;

/**
 * @Entity
 * @Table(name="event")
 */
class Event implements EntityInterface
{
    const STATUS_CONFIRMED = "confirmed";
    const STATUS_INVITED = "invited";
    const STATUS_REQUESTED = "requested";

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $title;

    /**
     * @Column(type="string", nullable=true)
     */
    private $description;

    /**
     * @Column(type="datetime")
     */
    private $date;

    /**
     * @var User
     *
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="created_by_id", referencedColumnName="id", nullable=false)
     */
    private $createdBy;

    /**
     * @var PersistentCollection|EventMember[]
     *
     * @OneToMany(targetEntity="EventMember", mappedBy="event", cascade={"persist", "remove"})
     */
    private $members;

    /**
     * @Column(type="smallint")
     */
    private $difficulty;

    /**
     * @Column(type="string")
     */
    private $terrain;

    /**
     * @Column(type="string", nullable=true)
     */
    private $route;

    /**
     * @ManyToOne(targetEntity="Location", inversedBy="events")
     *
     * @var Location
     */
    private $location;

    /**
     * @Column(type="boolean")
     *
     * @var bool
     */
    private $private = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Event
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Event
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set createdBy.
     *
     * @param User $createdBy
     *
     * @return Event
     */
    public function setCreatedBy(User $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Invite user
     */
    public function invite(User $user): EventMember
    {
        $ms = new EventMember();
        $ms->setUser($user);
        $ms->setStatus(Event::STATUS_INVITED);
        $ms->setEvent($this);

        $this->members->add($ms);
        return $ms;
    }

    /**
     * Request join
     * REVIEW: Decouple from EventMember
     * - Move to MembershipManager::join?
     *      {new EventMember();$user->addEvent;$event->addMember} ?
     */
    public function join(User $user): EventMember
    {
        $ms = new EventMember();
        $ms->setUser($user);
        $status = $this->getPrivate() ? Event::STATUS_REQUESTED : Event::STATUS_CONFIRMED;
        $ms->setStatus($status);
        $ms->setEvent($this);

        $this->members->add($ms);
        return $ms;
    }

    public function addMember(EventMember $membership)
    {
        $this->members->add($membership);
    }

    /**
     * @return PersistentCollection|EventMember[]
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function removeMember(EventMember $membership)
    {
        $this->members->removeElement($membership);
    }

    /**
     * Get the value of difficulty
     */
    public function getDifficulty()
    {
        return (int) $this->difficulty;
    }

    /**
     * Set the value of difficulty
     *
     * @return  self
     */
    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * Get the value of terrain
     */
    public function getTerrain(): string
    {
        return $this->terrain;
    }

    /**
     * Set the value of terrain
     *
     * @return  self
     */
    public function setTerrain($terrain)
    {
        $this->terrain = $terrain;

        return $this;
    }

    /**
     * Get the value of route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set the value of route
     *
     * @return  self
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get the value of location
     *
     * @return  Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @param  Location  $location
     *
     * @return  self
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the value of private
     *
     * @return bool
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * Set the value of private
     *
     * @param bool $private
     *
     * @return self
     */
    public function setPrivate(bool $private)
    {
        $this->private = $private;

        return $this;
    }

    /**
     * Get event detail
     *
     * @return object
     */
    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'members' => $this->getEventMembers(),
            'difficulty' => $this->getDifficulty(),
            'location' => (object) [ // TODO: thumbnail
                'id' => $this->getLocation()->getId(),
                'name' => $this->getLocation()->getName(),
                'gps' => [
                    $this->getLocation()->getGpsLat(),
                    $this->getLocation()->getGpsLon()
                ]
            ],
            'terrain' => $this->getTerrain(),
            'route' => $this->getRoute(),
            'datetime' => $this->getDate()->getTimestamp()
        ];
    }

    /**
     * Returns thumbnails of confirmed users
     *
     * @return array
     */
    protected function getEventMembers(): array
    {
        $members = [];
        /** @var \RideTimeServer\Entities\EventMember $member */
        foreach ($this->getMembers() as $member) {
            if ($member->getStatus() !== Event::STATUS_CONFIRMED) {
                continue;
            }
            // $members[] = $member->getUser()->getThumbnail();
            $members[] = $member->getUser()->getId();
        }

        return $members;
    }
}
