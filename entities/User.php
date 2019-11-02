<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Exception\RTException;

/**
 * @Entity
 * @Table(name="user")
 */
class User extends PrimaryEntity implements PrimaryEntityInterface
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @Column(type="string")
     */
    private $name;

    /**
     * @Column(type="string", unique=true, length=60)
     * @var string
     */
    private $email;

    /**
     * @Column(type="string", unique=true)
     *
     * @var string
     */
    private $authId;

    /**
     * @Column(type="string", nullable=true, length=15)
     * @var string
     */
    private $phone;

    /**
     * Profile picture
     *
     * @Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $picture;

    /**
     * @var ArrayCollection|EventMember[]
     *
     * @OneToMany(targetEntity="EventMember", mappedBy="user")
     */
    private $events;

    /**
     * The people who I think are my friends.
     * @var ArrayCollection|Friendship[]
     *
     * @OneToMany(targetEntity="Friendship", mappedBy="user", cascade={"persist", "remove"})
     */
    private $friends;

    /**
     * The people who think that I’m their friend.
     * @var ArrayCollection|Friendship[]
     *
     * @OneToMany(targetEntity="Friendship", mappedBy="friend", cascade={"persist", "remove"})
     */
    private $friendsWithMe;

    /**
     * @Column(type="string", length=100, nullable=true)
     * @var string
     */
    private $hometown;

    /**
     * @Column(type="smallint", nullable=true)
     * @var int
     */
    private $level;

    /**
     * User's favourite terrain
     * FIXME:
     * A little odd setting
     * Could be better to list user's bike types
     *
     * @Column(type="string", nullable=true, length=15)
     * @var string
     */
    private $bike;

    /**
     * Favourite trails
     * TODO:
     *   - If we have a list of trails,
     *     make it a reference
     *
     * @Column(type="string", nullable=true)
     * @var string
     */
    private $favourites;

    /**
     * Home locations
     *
     * @var ArrayCollection|Location[]
     * @ManyToMany(targetEntity="Location")
     */
    private $locations;

    /**
     * Tokens for Push Notifications
     * @var ArrayCollection|NotificationsToken[]
     *
     * @OneToMany(targetEntity="NotificationsToken", mappedBy="user", cascade={"persist", "remove"})
     */
    private $notificationsTokens;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->friendsWithMe = new ArrayCollection();
        $this->locations = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add event.
     *
     * @param \RideTimeServer\Entities\Event $event
     *
     * @return User
     */
    public function addEvent(\RideTimeServer\Entities\EventMember $membership)
    {
        $this->events[] = $membership;

        return $this;
    }

    /**
     * Remove event.
     *
     * @param \RideTimeServer\Entities\Event $event
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEvent(\RideTimeServer\Entities\EventMember $membership)
    {
        return $this->events->removeElement($membership);
    }

    /**
     * Get events.
     *
     * @param string $status
     * @return ArrayCollection
     */
    public function getEvents(string $status)
    {
        $filter = function(EventMember $em) use ($status) {
            return $em->getStatus() === $status;
        };

        $map = function(EventMember $em) {
            return $em->getEvent();
        };

        return $this->events->filter($filter)->map($map);
    }

    /**
     * Adds friendship to both parties
     *
     * @param Friendship $friendship
     * @return void
     */
    public function addFriendship(Friendship $friendship)
    {
        $this->friends->add($friendship);
        $friendship->getFriend()->addFriendshipWithMe($friendship);
    }

    /**
     * @param Friendship $friendship
     * @return void
     */
    public function removeFriendship(Friendship $friendship)
    {
        $friendship->getFriend()->friendsWithMe->removeElement($friendship);
        $this->friends->removeElement($friendship);
    }

    /**
     * Where friend_id = myself
     *
     * @param Friendship $friendship
     * @return void
     */
    public function addFriendshipWithMe(Friendship $friendship)
    {
        $this->friendsWithMe->add($friendship);
    }

    /**
     * Create a friendship with status 0
     *
     * @param User $friend
     * @return Friendship
     */
    public function addFriend(User $friend): Friendship
    {
        if ($friend === $this) {
            throw new UserException('Cannot request friendship with yourself');
        }
        $fs = new Friendship();
        $fs->setUser($this);
        $fs->setFriend($friend);
        // set defaults
        $fs->setStatus(0);

        $this->addFriendship($fs);
        return $fs;
    }

    /**
     * @param User $user User which initiated the friend request
     * @return Friendship
     */
    public function acceptFriend(User $user): Friendship
    {
        /** @var Friendship $friendship */
        $friendship = $this->friendsWithMe->filter(function ($fs) use ($user) {
            /** @var Friendship $fs */
            return $fs->getUser() === $user;
        })->first();

        if ($friendship === false) {
            throw new UserException('Cannot accept friendship from ' . $user->getId() . ' - Not found', 404);
        }
        $friendship->accept();
        return $friendship;
    }

    /**
     * Look into both friends collections and delete
     * @param User $user
     * @return void
     */
    public function removeFriend(User $user)
    {
        /** @var Friendship $friendship */
        $friendship = $this->friends->filter(function (Friendship $fs) use ($user) {
            return $fs->getFriend() === $user;
        })->first();
        if (empty($friendship)) {
            $friendship = $this->friendsWithMe->filter(function (Friendship $fs) use ($user) {
                return $fs->getUser() === $user;
            })->first();
        }

        $this->removeFriendship($friendship);
        return $friendship;
    }

    /**
     * @return ArrayCollection|Friendship[]
     */
    public function getFriendships()
    {
        return $this->friends;
    }

    /**
     * @return ArrayCollection|Friendship[]
     */
    public function getFriendshipsWithMe()
    {
        return $this->friendsWithMe;
    }

    /**
     * Get the value of email
     *
     * @return  string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @param string $email
     *
     * @return self
     */
    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the value of phone
     *
     * @param string $phone
     *
     * @return self
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get the value of hometown
     *
     * @return  string
     */
    public function getHometown(): ?string
    {
        return $this->hometown;
    }

    /**
     * Set the value of hometown
     *
     * @param string $hometown
     *
     * @return self
     */
    public function setHometown(string $hometown)
    {
        $this->hometown = $hometown;

        return $this;
    }

    /**
     * Get the value of level
     *
     * @return  int
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * Set the value of level
     *
     * @param int $level
     *
     * @return self
     */
    public function setLevel(int $level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return  string
     */
    public function getBike(): ?string
    {
        return $this->bike;
    }

    /**
     * @param string $bike
     *
     * @return self
     */
    public function setBike(string $bike)
    {
        $this->bike = $bike;

        return $this;
    }

    /**
     * @return  string
     */
    public function getFavourites(): ?string
    {
        return $this->favourites;
    }

    /**
     * @param  string  $favourites
     *
     * @return  self
     */
    public function setFavourites(string $favourites)
    {
        $this->favourites = $favourites;

        return $this;
    }

    /**
     * Get the value of authId
     *
     * @return string
     */
    public function getAuthId(): string
    {
        return $this->authId;
    }

    /**
     * @param string $authId
     * @return void
     */
    public function setAuthId(string $authId)
    {
        $this->authId = $authId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPicture(): ?string
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     *
     * @return self
     */
    public function setPicture(string $picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Add location.
     *
     * @param \RideTimeServer\Entities\Location $location
     *
     * @return User
     */
    public function addLocation(\RideTimeServer\Entities\Location $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * Remove event.
     *
     * @param \RideTimeServer\Entities\Location $location
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeLocation(\RideTimeServer\Entities\Location $location)
    {
        return $this->locations->removeElement($location);
    }

    /**
     * Get events.
     *
     * @return ArrayCollection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Update User with $data
     * Only applies editable scalar properties
     *
     * @param array $data
     * @return void
     */
    public function applyProperties(array $data)
    {
        $properties = [
            'name',
            'email',
            'phone',
            'hometown',
            'picture',
            'level',
            'bike',
            'favourites'
        ];

        foreach ($properties as $property) {
            if (!empty($data[$property])) {
                $method = $this->getSetterMethod($property);
                $this->{$method}((string) $data[$property]);
            }
        }
    }

    protected function getSetterMethod(string $property): string
    {
        $method = 'set' . ucfirst($property);
        if (!method_exists($this, $method)) {
            throw new RTException('Trying to update User with non-existing method ' . $method);
        }

        return $method;
    }

    /**
     * Get tokens for Push Notifications
     *
     * @return  ArrayCollection|NotificationsToken[]
     */
    public function getNotificationsTokens()
    {
        return $this->notificationsTokens;
    }

    /**
     * Set tokens for Push Notifications
     *
     * @param  ArrayCollection|NotificationsToken[]  $notificationsTokens  Tokens for Push Notifications
     *
     * @return  self
     */
    public function setNotificationsTokens($notificationsTokens)
    {
        $this->notificationsTokens = $notificationsTokens;

        return $this;
    }

    public function addNotificationsToken(NotificationsToken $token)
    {
        $this->notificationsTokens[] = $token;
    }

    /**
     * May be used to get entity "thumbnail"
     * 21/10/2019 looks like IDs only and populating when needed is the way
     * although it won't be as fast on the client
     * @deprecated
     * 31/10/2019 but I **want** fast on the client
     * Let's keep the chat to issue [#51](https://github.com/kachnitel/RideTime/issues/51)
     *
     * @return object
     */
    public function getThumbnail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'picture' => $this->getPicture()
        ];
    }

    /**
     * Get user detail
     *
     * @return object
     */
    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'hometown' => $this->getHometown(),
            'events' => $this->extractIds($this->getUserEvents()),
            'friends' => $this->extractIds($this->getUserFriends()),
            'level' => $this->getLevel(),
            'bike' => $this->getBike(),
            'favourites' => $this->getFavourites(),
            'picture' => $this->getPicture(),
            'email' => $this->getEmail(),
            'locations' => $this->extractIds($this->getUserLocations())
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'event' => $this->extractDetails($this->getUserEvents()),
            'user' => $this->extractDetails($this->getUserFriends()),
            'location' => $this->extractDetails($this->getUserLocations())
        ];
    }

    /**
     * Find confirmed events for user
     *
     * @return int[]
     */
    protected function getUserEvents(): array
    {
        return $this->getEvents(Event::STATUS_CONFIRMED)->map(function(Event $event) {
            return $event;
        })->getValues();
    }

    /**
     * Get friends list for an user
     * Combines friendships and friendshipsWithMe
     *
     *
     * @return User[]
     */
    protected function getUserFriends(): array
    {
        $friends = [];
        $filter = function(Friendship $friendship) {
            return $friendship->getStatus() === Friendship::STATUS_ACCEPTED;
        };

        /** @var Friendship $friendship */
        foreach ($this->getFriendships()->filter($filter) as $friendship) {
            $friends[] = $friendship->getFriend();
        }

        /** @var Friendship $friendship */
        foreach ($this->getFriendshipsWithMe()->filter($filter) as $friendship) {
            $friends[] = $friendship->getUser();
        }

        return $friends;
    }

    /**
     * @return int[]
     */
    protected function getUserLocations(): array
    {
        return $this->getLocations()->getValues();
    }
}
