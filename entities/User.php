<?php

namespace RideTimeServer\Entities;

/**
 * @Entity
 * @Table(name="user")
 */
class User
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
     * @Column(type="string", nullable=true)
     * @var string
     */
    private $profilePicUrl;

    /**
     * @Column(type="string", nullable=true)
     * @var string
     */
    private $coverPicUrl;

    /**
     * One user can join many events     *
     * @var \Doctrine\Common\Collections\ArrayCollection|Event[]
     *
     * @ManyToMany(targetEntity="Event", inversedBy="users")
     * @JoinTable(
     *  name="userEvent",
     *  joinColumns={
     *      @JoinColumn(name="user_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @JoinColumn(name="event_id", referencedColumnName="id")
     *  }
     * )
     */
    private $events;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return \Doctrine\Common\Collections\ArrayCollection
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
     * Get the value of profilePicUrl
     *
     * @return  string
     */
    public function getProfilePicUrl()
    {
        return $this->profilePicUrl;
    }

    /**
     * Set the value of profilePicUrl
     *
     * @param  string  $profilePicUrl
     *
     * @return  self
     */
    public function setProfilePicUrl(string $profilePicUrl)
    {
        $this->profilePicUrl = $profilePicUrl;

        return $this;
    }

    /**
     * Get the value of coverPicUrl
     *
     * @return  string
     */
    public function getCoverPicUrl()
    {
        return $this->coverPicUrl;
    }

    /**
     * Set the value of coverPicUrl
     *
     * @param  string  $coverPicUrl
     *
     * @return  self
     */
    public function setCoverPicUrl(string $coverPicUrl)
    {
        $this->coverPicUrl = $coverPicUrl;

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
}
