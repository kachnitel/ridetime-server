<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Exception\UserException;

class EventTest extends TestCase
{


    /**
     * TODO:
     * EventTest: public event join
     */

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