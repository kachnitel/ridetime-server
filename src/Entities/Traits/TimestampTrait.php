<?php

namespace RideTimeServer\Entities\Traits;

trait TimestampTrait
{
    /**
     * @var \DateTime
     *
     * @Column(type="datetime")
     */
    private $timestamp;

    /**
     * Get the value of timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp
     *
     * @param \DateTime $timestamp
     *
     * @return self
     */
    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
