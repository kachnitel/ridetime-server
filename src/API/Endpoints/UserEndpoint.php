<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;

class UserEndpoint extends Endpoint implements EndpointInterface
{
    /**
     * @param array $data
     * @param Logger $logger
     * @return object
     */
    public function add(array $data, Logger $logger): object
    {
        $user = $this->createUser($data);
        $this->saveEntity($user);

        return $this->getDetail($user);
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
     * Get user detail
     *
     * @param User $user
     * @return object
     */
    public function getDetail(User $user): object
    {
        return (object) [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'events' => $this->getUserEvents($user)
        ];
    }

    /**
     * Load user from database
     *
     * @param integer $userId
     * @return User
     */
    public function get(int $userId): User
    {
        /** @var User $user */
        $user = $this->entityManager->find(User::class, $userId);

        if (empty($user)) {
            // TODO: Throw UserNotFoundException
            throw new \Exception('User ID:' . $userId . ' not found', 404);
        }

        return $user;
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
