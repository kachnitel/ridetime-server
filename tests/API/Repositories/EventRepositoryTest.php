<?php
namespace RideTimeServer\Tests\API\Repositories;

use RideTimeServer\API\Repositories\EventRepository;
use RideTimeServer\Entities\Event;
use RideTimeServer\Tests\API\APITestCase;

class EventRepositoryTest extends APITestCase
{
    public function testCreate()
    {
        $user = $this->generateUser();
        $this->entityManager->persist($user);

        $location = $this->generateLocation();
        $this->entityManager->persist($location);
        $this->entityManager->flush();

        $eventData = (object) [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'datetime' => '1.12.2019 11:00',
            'difficulty' => 2,
            'terrain' => 'trail',
            'route' => 'This way there',
            'location' => $location->getId()
        ];

        $repo = new EventRepository(
            $this->entityManager,
            $this->entityManager->getClassMetadata(Event::class)
        );
        $event = $repo->create($eventData, $user);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $events = $this->entityManager->getRepository(Event::class)->findAll();
        $this->assertContains($event, $events);
        $this->assertEquals($eventData->title, $event->getTitle());
    }
}