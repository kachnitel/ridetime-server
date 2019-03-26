<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="friendship")
 */
class Friendship implements EntityInterface
{
    /**
     * @ManyToOne(targetEntity="User", inversedBy="friends")
     * @Id
     */
    private $user;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="friendsWithMe")
     * @Id
     */
    private $friend;

    /**
     * @Column(type="smallint")
     */
    private $status;

    /**
     * Get the value of friend
     */
    public function getFriend(): User
    {
        return $this->friend;
    }

    /**
     * Set the value of friend
     *
     * @return  self
     */
    public function setFriend($friend)
    {
        $this->friend = $friend;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser($user)
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
     * @return  self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}