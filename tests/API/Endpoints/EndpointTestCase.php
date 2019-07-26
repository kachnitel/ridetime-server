<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\Tests\API\APITestCase;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EntityInterface;

class EndpointTestCase extends APITestCase
{
    /**
     * TODO: fill required values / use ep::createUser?
     *
     * @param [type] $id
     * @return User
     */
    protected function generateUser($id = null): User
    {
        /** @var User $user */
        $user = $this->generateEntity(User::class, $id);

        $name = uniqid('Joe');
        $user->applyProperties([
            // TODO:
            'name' => $name,
            'email' => $name . '@provider.place'
        ]);
        $user->setAuthId(uniqid($name . '-'));

        return $user;
    }

    protected function generateEvent($id = null, User $user = null): Event
    {
        /** @var Event $event */
        $event = $this->generateEntity(Event::class, $id);
        $event->setTitle(uniqid('Event'));
        $event->setDate(new \DateTime('2 hours'));
        $event->setDifficulty(rand(0, 4));
        $event->setTerrain('trail');
        if ($user === null) {
            $user = $this->generateUser();
            $this->entityManager->persist($user);
        }
        $event->setCreatedBy($user);

        return $event;
    }

    protected function generateEntity(string $class, $id = null): EntityInterface
    {
        $reflection = new \ReflectionClass($class);
        $entity = $reflection->newInstance();
        $this->entities[] = $entity;

        // Set private id
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id ?? uniqid());

        return $entity;
    }
}
