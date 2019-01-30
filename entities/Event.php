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
}
