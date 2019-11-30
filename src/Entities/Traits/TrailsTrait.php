<?php
namespace RideTimeServer\Entities\Traits;

use RideTimeServer\Entities\Trail;
use Doctrine\Common\Collections\Collection;

/**
 * Add Trail[] list
 * requires setting trails in constructor
 *
 *   public function __construct() {
 *       $this->trails = new ArrayCollection();
 *   }
 */
trait TrailsTrait
{
    /**
     * Override to add `@OneToMany(targetEntity="Trail")`
     *
     * @ManyToMany(targetEntity="Trail", indexBy="id")
     *
     * @var Collection|Trail[]
     */
    private $trails;

    /**
     * @return Collection|Trail[]
     */
    public function getTrails(): Collection
    {
        return $this->trails;
    }

    /**
     * @param Trail $trail
     * @return self
     */
    public function addTrail(Trail $trail)
    {
        $this->trails[$trail->getId()] = $trail;

        return $this;
    }

    /**
     * @param Collection $trails
     * @return static
     */
    public function setTrails(Collection $trails)
    {
        $this->trails = $trails;

        return $this;
    }
}
