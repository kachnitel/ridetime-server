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

    /**
     * @param User $user
     * @param Event $event
     * @param string $visibility
     * @param boolean $expected Whether the event is visible to $user
     * @param string $userRelation Description of user relation
     * @return void
     *
     * @dataProvider isVisibleProvider
     */
    public function testIsVisible(User $user, Event $event, string $visibility, bool $expected, string $userRelation)
    {
        $not = $expected ? '' : 'NOT';
        $event->setVisibility($visibility);

        $this->assertEquals(
            $expected,
            $event->isVisible($user),
            "Failed asserting that '{$userRelation}' CAN{$not} see '{$visibility}' event"
        );
    }

    public function isVisibleProvider()
    {
        $creator = new User();
        $friend = new User();
        $creator->addFriend($friend)->accept();
        $member = new User();
        $stranger = new User();
        $memberfriend = new User();
        $member->addFriend($memberfriend)->accept();
        $invited = new User();
        $invitedfriend = new User();
        $invited->addFriend($invitedfriend)->accept();

        $event = new Event();
        $event->setCreatedBy($creator);
        $event->addMember((new EventMember())->setUser($creator)->setEvent($event)->confirm());
        $event->addMember((new EventMember())->setUser($member)->setEvent($event)->confirm());
        $event->invite($invited);

        return [
            [ $creator, $event, Event::VISIBILITY_PUBLIC, true, 'creator' ],
            [ $creator, $event, Event::VISIBILITY_FRIENDS, true, 'creator' ],
            [ $creator, $event, Event::VISIBILITY_INVITED, true, 'creator' ],
            [ $creator, $event, Event::VISIBILITY_MEMBERS_FRIENDS, true, 'creator' ],
            [ $member, $event, Event::VISIBILITY_PUBLIC, true, 'member' ],
            [ $member, $event, Event::VISIBILITY_FRIENDS, true, 'member' ],
            [ $member, $event, Event::VISIBILITY_INVITED, true, 'member' ],
            [ $member, $event, Event::VISIBILITY_MEMBERS_FRIENDS, true, 'member' ],
            [ $friend, $event, Event::VISIBILITY_PUBLIC, true, 'friend' ],
            [ $friend, $event, Event::VISIBILITY_FRIENDS, true, 'friend' ],
            [ $friend, $event, Event::VISIBILITY_INVITED, false, 'friend' ],
            [ $friend, $event, Event::VISIBILITY_MEMBERS_FRIENDS, true, 'friend' ],
            [ $memberfriend, $event, Event::VISIBILITY_PUBLIC, true, 'memberfriend' ],
            [ $memberfriend, $event, Event::VISIBILITY_FRIENDS, false, 'memberfriend' ],
            [ $memberfriend, $event, Event::VISIBILITY_INVITED, false, 'memberfriend' ],
            [ $memberfriend, $event, Event::VISIBILITY_MEMBERS_FRIENDS, true, 'memberfriend' ],
            [ $stranger, $event, Event::VISIBILITY_PUBLIC, true, 'stranger' ],
            [ $stranger, $event, Event::VISIBILITY_FRIENDS, false, 'stranger' ],
            [ $stranger, $event, Event::VISIBILITY_INVITED, false, 'stranger' ],
            [ $stranger, $event, Event::VISIBILITY_MEMBERS_FRIENDS, false, 'stranger' ],
            [ $invited, $event, Event::VISIBILITY_PUBLIC, true, 'invited' ],
            [ $invited, $event, Event::VISIBILITY_FRIENDS, true, 'invited' ],
            [ $invited, $event, Event::VISIBILITY_INVITED, true, 'invited' ],
            [ $invited, $event, Event::VISIBILITY_MEMBERS_FRIENDS, true, 'invited' ],
            [ $invitedfriend, $event, Event::VISIBILITY_PUBLIC, true, 'invitedfriend' ],
            [ $invitedfriend, $event, Event::VISIBILITY_FRIENDS, false, 'invitedfriend' ],
            [ $invitedfriend, $event, Event::VISIBILITY_INVITED, false, 'invitedfriend' ],
            [ $invitedfriend, $event, Event::VISIBILITY_MEMBERS_FRIENDS, false, 'invitedfriend' ]
        ];
    }

    public function testIsMember()
    {
        $event = new Event();
        $member = new User();
        $invited = new User();
        $requested = new User();
        $stranger = new User();

        $event->setPrivate(true);
        $event->invite($member)->confirm();
        $event->invite($invited);
        $event->join($requested);

        $this->assertTrue($event->isMember($member));
        $this->assertFalse($event->isMember($invited));
        $this->assertFalse($event->isMember($requested));
        $this->assertFalse($event->isMember($stranger));
    }
}