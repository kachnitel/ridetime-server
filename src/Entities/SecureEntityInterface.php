<?php
namespace RideTimeServer\Entities;

interface SecureEntityInterface extends PrimaryEntityInterface
{
    /**
     * Check whether entity is visible to $user
     *
     * @return bool
     */
    public function isVisible(User $user): bool;
}