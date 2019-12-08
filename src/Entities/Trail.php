<?php
namespace RideTimeServer\Entities;

use RideTimeServer\Entities\Traits\AliasTrait;
use RideTimeServer\Entities\Traits\DifficultyTrait;
use RideTimeServer\Entities\Traits\IdTrait;
use RideTimeServer\Entities\Traits\LocationTrait;
use RideTimeServer\Entities\Traits\TerrainProfileTrait;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\TrailRepository")
 * @Table(name="trail")
 */
class Trail extends PrimaryEntity implements PrimaryEntityInterface
{
    use IdTrait;
    use LocationTrait;
    use TerrainProfileTrait;
    use DifficultyTrait;
    use AliasTrait;

    const SCALAR_FIELDS = [
        'id',
        'title',
        'description',
        'difficulty',
        'alias'
    ];

    /**
     * Applies self::SCALAR_FIELDS listed properties
     * REVIEW: See User::applyProperties(array)
     *
     * @param object $data
     * @return object
     */
    public function applyProperties(object $data)
    {
        foreach (self::SCALAR_FIELDS as $property) {
            $method = $method = 'set' . ucfirst($property);
            $this->{$method}($data->{$property});
        }
    }

    /**
     * Get trail detail
     *
     * @returnobject
     */
    public function getDetail(): object
    {
        return (object) [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'difficulty' => $this->getDifficulty(),
            'location' => $this->getLocation()->getId(),
            'profile' => $this->getProfile(),
            'alias' => $this->getAlias()
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'location' => [$this->getLocation()]
        ];
    }

    /**
     * @Column(type="string")
     *
     * @var string
     */
    private $title;

    /**
     * @Column(type="text")
     *
     * @var string
     */
    private $description;

    /**
     * @ManyToOne(targetEntity="Location", inversedBy="trails")
     *
     * @var Location
     */
    private $location;

    /**
     * Get the value of title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }
}
