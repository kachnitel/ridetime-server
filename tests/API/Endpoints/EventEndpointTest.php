<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\Database\EventEndpoint;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\Location;
use RideTimeServer\Tests\API\APITestCase;

class EventEndpointTest extends APITestCase
{
    public function testRemoveMember()
    {
        $endpoint = new EventEndpoint($this->entityManager, $this->getLogger());

        $event = $this->generateEvent(200);
        $user1 = $this->generateUser(100);
        $user2 = $this->generateUser(101);
        $this->entityManager->persist($event);
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        $member1 = new EventMember();
        $member1->setEvent($event);
        $member1->setUser($user1);
        $member1->setStatus(Event::STATUS_CONFIRMED);

        $event->addMember($member1);
        $user1->addEvent($member1);

        $member2 = new EventMember();
        $member2->setEvent($event);
        $member2->setUser($user2);
        $member2->setStatus(Event::STATUS_CONFIRMED);

        $event->addMember($member2);
        $user2->addEvent($member2);

        $endpoint->removeMember($event->getId(), $user1->getId());
        $this->assertCount(1, $event->getMembers());
    }

    public function testAdd()
    {
        $endpoint = new EventEndpoint($this->entityManager, $this->getLogger());

        $user = $this->generateUser();
        $this->entityManager->persist($user);

        $location = $this->generateLocation();
        $this->entityManager->persist($location);

        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'datetime' => '1.12.2019 11:00',
            'difficulty' => 2,
            'terrain' => 'trail',
            'route' => 'This way there',
            'location' => $location->getId()
        ];

        $event = $endpoint->add($eventData, $user);
        $events = $this->entityManager->getRepository(Event::class)->findAll();
        $this->assertContains($event, $events);
    }
}
