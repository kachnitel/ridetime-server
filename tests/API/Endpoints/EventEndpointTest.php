<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\API\Endpoints\Database\EventEndpoint;
use Monolog\Logger;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Event;
use RideTimeServer\Tests\API\APITestCase;

class EventEndpointTest extends APITestCase
{
    public function testRemoveMember()
    {
        $endpoint = new EventEndpoint($this->entityManager, new Logger('test'));

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
}
