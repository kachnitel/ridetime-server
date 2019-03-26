<?php
namespace RideTimeServer\Entities;

/**
 * @Entity
 * @Table(name="friendship")
 */
class Friendship implements EntityInterface
{
    /**
     * Requesting user
     *
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="friends")
     * @Id
     */
    private $user;

    /**
     * Receiving user
     *
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="friendsWithMe")
     * @Id
     */
    private $friend;

    /**
     * 0: pending
     * 1: accepted
     *
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
     * @return self
     */
    public function setFriend(User $friend)
    {
        $this->friend = $friend;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return self
     */
    public function setUser(User $user)
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
     * @return self
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Accept friendship
     *
     * @return self
     */
    public function accept()
    {
        $this->setStatus(1);

        return $this;
    }
}