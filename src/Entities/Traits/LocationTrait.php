<?php
namespace RideTimeServer\Entities\Traits;

use RideTimeServer\Entities\Location;

/**
 * Add location
 */
trait LocationTrait
{
    /**
     * Override to add ` , inversedBy="trails"`
     *
     * @ManyToOne(targetEntity="Location")
     *
     * @var Location
     */
    private $location;

    /**
     * Get the value of location
     *
     * @returnLocation
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @param Location $location
     *
     * @return self
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }
}
