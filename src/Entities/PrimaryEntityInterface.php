<?php
namespace RideTimeServer\Entities;

interface PrimaryEntityInterface extends EntityInterface
{
    public function getId(): int;

    public function getDetail(): object;

    public function getRelated(): object;
}