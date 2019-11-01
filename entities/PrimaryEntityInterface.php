<?php
namespace RideTimeServer\Entities;

interface PrimaryEntityInterface extends EntityInterface
{
    public function getId(): int;
}