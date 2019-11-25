<?php
namespace RideTimeServer\Entities\Traits;

/**
 * Add ID with setter and getter
 */
trait DifficultyTrait
{

    /**
     * @Column(type="smallint")
     */
    private $difficulty;

    /**
     * Get the value of difficulty
     *
     * @return int
     */
    public function getDifficulty(): int
    {
        return (int) $this->difficulty;
    }

    /**
     * Set the value of difficulty
     *
     * @param int $difficulty
     *
     * @return self
     */
    public function setDifficulty(int $difficulty)
    {
        $this->difficulty = $difficulty;

        return $this;
    }
}
