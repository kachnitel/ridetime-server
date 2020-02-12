<?php
namespace RideTimeServer\Entities\Traits;

trait GpsTrait
{
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
}
