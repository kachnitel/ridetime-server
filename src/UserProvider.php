<?php
namespace RideTimeServer;

use Doctrine\ORM\EntityManagerInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;

class UserProvider
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    protected $token;

    public function __construct(EntityManagerInterface $entityManager, array $token) {
        $this->entityManager = $entityManager;
        $this->token = $token;
    }

    public function getCurrentUser(): User
    {
        if (empty($this->token['sub'])) {
            throw new RTException('No token found in request');
        }

        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'authId' => $this->token['sub']
            ]);
    }
}
