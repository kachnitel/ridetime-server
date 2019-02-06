<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="user")
 */
class User implements EntityInterface
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
    private $name;

    /**
     * @Column(type="string", unique=true, length=60)
     * @var string
     */
    private $email;

    /**
     * @Column(type="string", nullable=true, length=15)
     * @var string
     */
    private $phone;

    /**
     * @Column(type="string", length=255)
     * @var string
     */
    private $password;

    /**
     * Many users can join many events
     * @var ArrayCollection|Event[]
     *
     * @ManyToMany(targetEntity="Event", inversedBy="users")
     * @JoinTable(name="users_events")
     */
    private $events;

    /**
     * @Column(type="string", length=100)
     * @var string
     */
    private $hometown;

    /**
     * @Column(type="smallint")
     * @var int
     */
    private $level;

    /**
     * User's favourite "style"
     * FIXME:
     * A little odd setting
     * Could be better to list user's bike types
     *
     * @Column(type="smallint")
     * @var int
     */
    private $favStyle;

    /**
     * Favourite trails
     * TODO:
     *   - If we have a list of trails,
     *     make it a reference
     *
     * @Column(type="string")
     * @var string
     */
    private $favourites;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add event.
     *
     * @param \RideTimeServer\Entities\Event $event
     *
     * @return User
     */
    public function addEvent(\RideTimeServer\Entities\Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event.
     *
     * @param \RideTimeServer\Entities\Event $event
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEvent(\RideTimeServer\Entities\Event $event)
    {
        return $this->events->removeElement($event);
    }

    /**
     * Get events.
     *
     * @return ArrayCollection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Get the value of email
     *
     * @return  string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @param  string  $email
     *
     * @return  self
     */
    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of phone
     *
     * @return  string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the value of phone
     *
     * @param  string  $phone
     *
     * @return  self
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get the value of password
     *
     * @return  string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @param  string  $password
     *
     * @return  self
     */
    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the value of hometown
     *
     * @return  string
     */
    public function getHometown(): string
    {
        return $this->hometown;
    }

    /**
     * Set the value of hometown
     *
     * @param  string  $hometown
     *
     * @return  self
     */
    public function setHometown(string $hometown)
    {
        $this->hometown = $hometown;

        return $this;
    }

    /**
     * Get the value of level
     *
     * @return  int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set the value of level
     *
     * @param  int  $level
     *
     * @return  self
     */
    public function setLevel(int $level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return  int
     */
    public function getFavStyle(): int
    {
        return $this->favStyle;
    }

    /**
     * @param  int  $favStyle
     *
     * @return  self
     */
    public function setFavStyle(int $favStyle)
    {
        $this->favStyle = $favStyle;

        return $this;
    }

    /**
     * @return  string
     */
    public function getFavourites(): string
    {
        return $this->favourites;
    }

    /**
     * @param  string  $favourites
     *
     * @return  self
     */
    public function setFavourites(string $favourites)
    {
        $this->favourites = $favourites;

        return $this;
    }
}
