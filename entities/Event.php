<?php

namespace RideTimeServer\Entities;

/**
 * @Entity
 * @Table(name="event")
 */
class Event
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
     * @ManyToOne(targetEntity="User", inversedBy="events")
     * @JoinColumn(name="created_by_id", referencedColumnName="id", nullable=false)
     * @var \RideTimeServer\Entities\User
     */
    private $createdBy;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|User[]
     *
     * @ManyToMany(targetEntity="User", mappedBy="events")
     */
    protected $users;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param \RideTimeServer\Entities\User $createdBy
     *
     * @return Event
     */
    public function setCreatedBy(\RideTimeServer\Entities\User $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \RideTimeServer\Entities\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Add user.
     *
     * @param \RideTimeServer\Entities\User $user
     *
     * @return Event
     */
    public function addUser(\RideTimeServer\Entities\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     *
     * @param \RideTimeServer\Entities\User $user
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUser(\RideTimeServer\Entities\User $user)
    {
        return $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
