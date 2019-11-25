<?php
namespace RideTimeServer\Entities\Traits;

trait AliasTrait
{
    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $alias;

    /**
     * Get the value of alias
     *
     * @return  string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Set the value of alias
     *
     * @param  string  $alias
     *
     * @return  self
     */
    public function setAlias(string $alias)
    {
        $this->alias = $alias;

        return $this;
    }
}
