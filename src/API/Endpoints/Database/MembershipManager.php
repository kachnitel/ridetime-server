<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Exception\EntityNotFoundException;
use Doctrine\Common\Collections\Criteria;

class MembershipManager
{
    /**
     * @param Event $event
     * @param User $user
     * @return EventMember
     */
    public function join(Event $event, User $user): EventMember
    {
        return $this->confirmMemberIfStatus($event, $user, Event::STATUS_INVITED) ?? $event->join($user);
    }

    /**
     * @param Event $event
     * @param User $user
     * @return EventMember
     */
    public function invite(Event $event, User $user): EventMember
    {
        return $this->confirmMemberIfStatus($event, $user, Event::STATUS_REQUESTED) ?? $event->invite($user);
    }

    /**
     * @param Event $event
     * @param User $user
     * @return EventMember
     */
    public function removeMember(Event $event, User $user): EventMember
    {
        $membership = $this->findEventMember($event, $user);
        if (!$membership) {
            throw new EntityNotFoundException("User {$user->getId()} is not a member of event {$event->getId()}.", 404);
        }
        $user->removeEvent($membership);
        $event->removeMember($membership);

        return $membership;
    }

    /**
     * Confirm request/invite if exists for user
     *
     * Returns EventMember or false if membership doesn't exist
     *
     * @param Event $event
     * @param User $user
     * @param string $status
     * @return boolean|EventMember
     */
    protected function confirmMemberIfStatus(Event $event, User $user, string $status)
    {
        $membership = $this->findEventMember($event, $user);
        if ($membership) {
            /** @var EventMember $membership */
            if ($membership->getStatus() === $status) {
                $membership->setStatus(Event::STATUS_CONFIRMED);
            }
            return $membership;
        }
        return null;
    }

    /**
     * @param Event $event
     * @param User $user
     * @return EventMember|null
     */
    protected function findEventMember(Event $event, User $user)
    {
        $existing = $event->getMembers()->matching(Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->andWhere(Criteria::expr()->eq('event', $event))
        );
        return $existing->isEmpty() ? null : $existing->first();
    }
}
