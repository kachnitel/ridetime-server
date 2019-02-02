<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class UserEndpoint implements EndpointInterface
{
    /**
     * Doctrine entity manager
     *
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data, Logger $logger): object
    {
        $user = $this->createUser($data);
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $errorId = uniqid();

            $logger->addWarning('User creation failed', [
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'errorId' => $errorId
            ]);

            /**
             * TODO: determine the conflicting column
             */
            throw new \Exception('Error creating user: user already exists', 409);
        }

        // Return full user detail to load in app after creation
        return $this->getDetail($user->getId());
    }

    /**
     * TODO: Validate input!
     *
     * @param array $data
     * @return User
     */
    protected function createUser(array $data): User
    {
        $user = new User();

        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));

        return $user;
    }

    /**
     * Load user from database
     *
     * @param integer $userId
     * @return User
     */
    protected function getUser(int $userId): User
    {
        /** @var User $user */
        $user = $this->entityManager->find('RideTimeServer\Entities\User', $userId);

        if (empty($user)) {
            // TODO: Throw UserNotFoundException
            throw new \Exception('User ID:' . $userId . ' not found', 404);
        }

        return $user;
    }

    /**
     * Get user detail
     *
     * @param integer $userId
     * @return object
     */
    public function getDetail(int $userId): object
    {
        $user = $this->getUser($userId);

        return (object) [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'events' => $this->getUserEvents($user)
        ];
    }

    /**
     * Find events for user
     *
     * @param User $user
     * @return array
     */
    protected function getUserEvents(User $user): array
    {
        $events = [];
        /** @var Event $event */
        foreach ($user->getEvents() as $event) {
            $events[] = (object) [
                'id' => $event->getId(),
                'datetime' => $event->getDate()->format(\DateTime::ATOM),
                'title' => $event->getTitle()
            ];
        }

        return $events;
    }
}
