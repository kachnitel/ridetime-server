<?php
namespace RideTimeServer\Entities;

interface EntityInterface
{
    /**
     * Return JSON serializable object to be returned to the API
     *
     * @return object
     */
    public function getDetail(): object;
}