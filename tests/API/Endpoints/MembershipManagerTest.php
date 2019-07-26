<?php
namespace RideTimeServer\Tests\API\Endpoints;

use RideTimeServer\Entities\EventMember;
use RideTimeServer\API\Endpoints\Database\MembershipManager;

class MembershipManagerTest extends EndpointTestCase
{
    public function testRemoveMember()
    {
        $manager = new MembershipManager();

        $event = $this->generateEvent(200);
        $user1 = $this->generateUser(100);
        $user2 = $this->generateUser(101);

        $member1 = new EventMember();
        $member1->setEvent($event);
        $member1->setUser($user1);
        $event->addMember($member1);

        $member2 = new EventMember();
        $member2->setEvent($event);
        $member2->setUser($user2);
        $event->addMember($member2);

        $manager->removeMember($event, $user1);
        $this->assertCount(1, $event->getMembers());
    }
}
