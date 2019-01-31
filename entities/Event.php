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
     * @Column(type="smallint")
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
     * Many events for one user FIXME: many to many!
     * @ManyToOne(targetEntity="User", inversedBy="events")
     * @JoinColumn(name="created_by_id", referencedColumnName="id", nullable=false)
     * @var \RideTimeServer\Entities\User
     */
    private $created_by;

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
     * @param \User $createdBy
     *
     * @return Event
     */
    public function setCreatedBy(\User $createdBy)
    {
        $this->created_by = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }
}
