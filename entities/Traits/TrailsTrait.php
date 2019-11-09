<?php
namespace RideTimeServer\Entities\Traits;

use RideTimeServer\Entities\Trail;

/**
 * Add Trail[] list
 */
trait TrailsTrait
{
    /**
     * Override to add `@OneToMany(targetEntity="Trail")`
     *
     * @ManyToMany(targetEntity="Trail")
     *
     * @var ArrayCollection|Trail[]
     */
    private $trails;

    /**
     * @return ArrayCollection|Trail[]
     */
    public function getTrails(): ArrayCollection
    {
        return $this->trails;
    }

    /**
     * @param Trail $trail
     * @return self
     */
    public function addTrail(Trail $trail)
    {
        $this->trails[] = $trail;

        return $this;
    }
}
