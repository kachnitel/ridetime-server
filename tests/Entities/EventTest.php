<?php
namespace RideTimeServer\Tests\Entities;

use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Location;

class EventTest extends EntityTestCase
{
    public function testGetDetail()
    {
        $location = new Location();
        $this->set($location, 1);
        $event = new Event();
        $this->set($event, 1);
        $member = new User();
        $this->set($member, 1);
        $inv = new User();
        $this->set($inv, 2);

        $event->setLocation($location);
        $event->setTerrain('trail');
        $dt = new \DateTime();
        $event->setDate($dt);

        $event->invite($inv);
        ($event->invite($member))->confirm();

        $detail = $event->getDetail();
        $this->assertEquals([$member->getId()], $detail->members);
        $this->assertEquals([$inv->getId()], $detail->invited);
        $this->assertEquals('trail', $detail->terrain);
        $this->assertEquals($location->getId(), $detail->location);
        $this->assertEquals($dt->getTimestamp(), $detail->datetime);
    }

    public function testJoinPublicEvent()
    {
        $this->doTestJoinEvent(false, Event::STATUS_CONFIRMED);
    }

    public function testJoinPrivateEvent()
    {
        $this->doTestJoinEvent(true, Event::STATUS_REQUESTED);
    }

    public function doTestJoinEvent(bool $private, string $expectedStatus)
    {
        $event = new Event();
        $event->setPrivate($private);
        $user = new User();

        $event->join($user);
        $this->assertCount(1, $event->getMembers());
        /** @var EventMember $membership */
        $membership = $event->getMembers()->first();
        $this->assertSame($user, $membership->getUser());
        $this->assertSame($event, $membership->getEvent());
        $this->assertEquals($expectedStatus, $membership->getStatus());
    }

    /**
     * Assert accept sets status to confirmed
     *
     * @return void
     */
    public function testInviteUser()
    {
        $event = new Event();
        $user = new User();
        $event->invite($user);

        $this->assertEquals(Event::STATUS_INVITED, $event->getMembers()->first()->getStatus());
    }

    public function testAcceptInvite()
    {
        $event = new Event;
        $user = new User();
        $event->invite($user);

        /** @var EventMember $membership */
        $membership = $event->getMembers()->first();
        $membership->accept();
        $this->assertEquals(Event::STATUS_CONFIRMED, $membership->getStatus());
    }
}