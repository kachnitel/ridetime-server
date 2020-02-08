<?php
namespace RideTimeServer\API\Providers;

use Doctrine\Common\Collections\Criteria;
use RideTimeServer\API\Repositories\EventRepository;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\UserException;

class EventProvider
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var EventRepository
     */
    protected $repo;

    public function __construct(EventRepository $repo) {
        $this->repo = $repo;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function get(int $id)
    {
        $this->checkUser();

        /** @var Event $event */
        $event = $this->repo->get($id);
        if (!$event->isVisible($this->user)) {
            throw new UserException("Event {$id} is not visible to current user", 403);
        }

        return $event;
    }

    public function filter(Criteria $criteria)
    {
        $this->checkUser();

        return $this->repo
            ->matching($criteria)
            ->filter(function (Event $event) {
                return $event->isVisible($this->user);
            });
    }

    protected function checkUser()
    {
        if (!$this->user) {
            throw new RTException('Cannot access events without setting user');
        }
    }
}
