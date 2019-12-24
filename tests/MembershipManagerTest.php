<?php
namespace RideTimeServer\Tests;

use RideTimeServer\Entities\EventMember;
use RideTimeServer\MembershipManager;
use RideTimeServer\Tests\API\APITestCase;

class MembershipManagerTest extends APITestCase
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
        $this->assertCount(2, $event->getMembers()); // $user2 & createdBy
        $this->assertContains($member2, $event->getMembers());
        $this->assertNotContains($member1, $event->getMembers());
    }
}
