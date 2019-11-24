<?php
namespace RideTimeServer\Entities;

use \Doctrine\Common\Collections\ArrayCollection;
use RideTimeServer\Entities\Traits\IdTrait;
use RideTimeServer\Entities\Traits\TrailsTrait;
use RideTimeServer\Exception\RTException;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\LocationRepository")
 * @Table(name="location")
 */
class Location extends PrimaryEntity implements PrimaryEntityInterface
{
    use IdTrait;
    use TrailsTrait;

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
            'difficulties' => $this->getDifficulties()
        ];
    }

    public function getRelated(): object
    {
        throw new RTException("Location::getRelated not implemented", 501);
        return new \Object();
    }

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @Column(type="decimal", precision=9, scale=6)
     *
     * @var float
     */
    private $gpsLat;

    /**
     * @Column(type="decimal", precision=9, scale=6)
     *
     * @var float
     */
    private $gpsLon;

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
     * Get the value of gpsLat
     *
     * @return  float
     */
    public function getGpsLat(): float
    {
        return $this->gpsLat;
    }

    /**
     * Set the value of gpsLat
     *
     * @param  float  $gpsLat
     *
     * @return  self
     */
    public function setGpsLat(float $gpsLat)
    {
        $this->gpsLat = $gpsLat;

        return $this;
    }

    /**
     * Get the value of gpsLon
     *
     * @return  float
     */
    public function getGpsLon(): float
    {
        return $this->gpsLon;
    }

    /**
     * Set the value of gpsLon
     *
     * @param  float  $gpsLon
     *
     * @return  self
     */
    public function setGpsLon(float $gpsLon)
    {
        $this->gpsLon = $gpsLon;

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
     * Get one location has many events. This is the inverse side.
     *
     * @return  ArrayCollection|Event[]
     */
    public function getEvents(): ArrayCollection
    {
        return $this->events;
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
}