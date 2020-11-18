<?php
namespace RideTimeServer\Entities\Traits;

/**
 * Add ID with setter and getter
 */
trait IdTrait
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", unique=true)
     *
     * @var int
     */
    private $id;

    /**
     * Get the value of id
     *
     * @return  int
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Set the value of id
     *
     * @param  int  $id
     *
     * @return  self
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }
}
