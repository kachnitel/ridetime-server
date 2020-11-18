<?php
namespace RideTimeServer\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use RideTimeServer\Entities\Traits\AliasTrait;
use RideTimeServer\Entities\Traits\IdTrait;
use RideTimeServer\Entities\Traits\TrailsTrait;
use RideTimeServer\Entities\Traits\GpsTrait;
use RideTimeServer\Entities\Traits\RemoteIdTrait;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\LocationRepository")
 * @Table(name="location")
 */
class Location extends PrimaryEntity implements PrimaryEntityInterface
{
    use IdTrait;
    use RemoteIdTrait;
    use TrailsTrait;
    use AliasTrait;
    use GpsTrait;

    /**
     * Get location detail
     *
     * @return object
     */
    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'coords' => [
                $this->getGpsLat(),
                $this->getGpsLon()
            ],
            'difficulties' => $this->getDifficulties(),
            'alias' => $this->getAlias(),
            'coverPhoto' => $this->getCoverPhoto(),
            'source' => $this->getSource(),
            'remoteId' => $this->getRemoteId()
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'event' => $this->getEvents()->getValues()
        ];
    }

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * Difficulties available at the location
     *
     * @Column(type="array")
     *
     * TODO: type
     */
    private $difficulties;

    /**
     * One location has many events. This is the inverse side.
     * @OneToMany(targetEntity="Event", mappedBy="location")
     *
     * @var ArrayCollection|Event[]
     */
    private $events;

    /**
     * @OneToMany(targetEntity="Trail", mappedBy="location")
     *
     * @var ArrayCollection|Trail[]
     */
    private $trails;

    /**
     * @Column(type="integer",nullable=true)
     *
     * @var string
     */
    private $coverPhoto;

    public function __construct() {
        $this->events = new ArrayCollection();
        $this->trails = new ArrayCollection();
    }

    /**
     * Get the value of name
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param  string  $name
     *
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get difficulties available at the location
     */
    public function getDifficulties()
    {
        return $this->difficulties;
    }

    /**
     * Set difficulties available at the location
     *
     * @return  self
     */
    public function setDifficulties($difficulties)
    {
        $this->difficulties = $difficulties;

        return $this;
    }

    /**
     * Get events in location.
     *
     * @return Collection|int[]
     */
    public function getEvents(): Collection
    {
        return $this->events->map(function (Event $event) {
            return $event->getId();
        });
    }

    /**
     * Add event.
     *
     * @param Event $event
     *
     * @return Event
     */
    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event.
     *
     * @param Event $event
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEvent(Event $event)
    {
        return $this->events->removeElement($event);
    }

    /**
     * Get the value of coverPhoto
     *
     * @return int
     */
    public function getCoverPhoto(): ?int
    {
        return $this->coverPhoto;
    }

    /**
     * Set the value of coverPhoto
     *
     * @param int $coverPhoto
     *
     * @return self
     */
    public function setCoverPhoto(int $coverPhoto)
    {
        $this->coverPhoto = $coverPhoto;

        return $this;
    }
}
