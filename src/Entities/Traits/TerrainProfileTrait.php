<?php
namespace RideTimeServer\Entities\Traits;

/**
 * Adds an object of the following shape:
 * { distance, alt_climb, alt_descent }
 */
trait TerrainProfileTrait
{
    /**
     * @Column(type="object")
     *
     * @var object
     */
    private $profile;

    /**
     * Get the value of profile
     *
     * @return object
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set the value of profile
     * Should contain keys {distance, alt_climb, alt_descent}
     *
     * @param object $profile
     *
     * @return self
     */
    public function setProfile(object $profile)
    {
        $this->profile = (object) [
            'distance' => $profile->distance ?? 0,
            'alt_climb' => $profile->alt_climb ?? 0,
            'alt_descent' => $profile->alt_descent ?? 0
        ];

        return $this;
    }
}
