<?php
namespace RideTimeServer\Entities;

interface PrimaryEntityInterface extends EntityInterface
{
    /**
     * Returns ID of entity
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Returns detail of entity as simple object
     *
     * @return object
     */
    public function getDetail(): object;

    /**
     * Returns getDetail() of related entities
     *
     * @return object
     */
    public function getRelated(): object;
}