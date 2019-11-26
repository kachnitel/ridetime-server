<?php
namespace RideTimeServer\Entities;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\NotificationsTokenRepository")
 * @Table(name="notifications_token")
 */
class NotificationsToken implements EntityInterface
{
    /**
     * Owning user
     *
     * @var User
     * @ManyToOne(targetEntity="User", inversedBy="notificationsTokens")
     */
    private $user;

    /**
     * @var string
     *
     * @Column(type="string", unique=true)
     * @Id
     */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the value of token
     */
    public function getToken(): String
    {
        return $this->token;
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

    public function __toString()
    {
        return $this->token;
    }

    public function getDetail(): object
    {
        return (object) [
            'userId' => $this->getUser()->getId(),
            'token' => $this->getToken()
        ];
    }
}