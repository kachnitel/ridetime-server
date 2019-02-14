<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="event")
 */
class Event implements EntityInterface
{
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
     * @Column(type="string")
     */
    private $description;

    /**
     * @Column(type="datetime")
     */
    private $date;

    /**
     * @var User
     *
     * @ManyToOne(targetEntity="User", inversedBy="events")
     * @JoinColumn(name="created_by_id", referencedColumnName="id", nullable=false)
     */
    private $createdBy;

    /**
     * @var ArrayCollection|User[]
     *
     * @ManyToMany(targetEntity="User", mappedBy="events")
     */
    private $users;

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
     * Constructor
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
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
        return (int) $this->createdBy;
    }

    /**
     * Add user.
     *
     * @param User $user
     *
     * @return Event
     */
    public function addUser(User $user)
    {
        $user->addEvent($this);
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     *
     * @param User $user
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUser(User $user)
    {
        return $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
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
}
