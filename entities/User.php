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
     * @Column(type="smallint")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $firstName;

    /**
     * @Column(type="string")
     */
    private $lastName;

    /**
     * One user can join many events
     * @OneToMany(targetEntity="Event", mappedBy="user", cascade={"all"})
     * @var Doctrine\Common\Collection\ArrayCollection
     */
    private $events;
}
