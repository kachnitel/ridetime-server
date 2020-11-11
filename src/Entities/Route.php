<?php
namespace RideTimeServer\Entities;

use RideTimeServer\Entities\Traits\LocationTrait;
use RideTimeServer\Entities\Traits\TerrainProfileTrait;
use RideTimeServer\Entities\Traits\TrailsTrait;
use RideTimeServer\Entities\Traits\IdTrait;
use RideTimeServer\Entities\Traits\DifficultyTrait;
use Doctrine\Common\Collections\ArrayCollection;
use RideTimeServer\Entities\Traits\AliasTrait;
use RideTimeServer\Entities\Traits\RemoteIdTrait;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\RouteRepository")
 * @Table(name="route")
 */
class Route extends PrimaryEntity implements PrimaryEntityInterface
{
    use IdTrait;
    use RemoteIdTrait;
    use LocationTrait;
    use TrailsTrait;
    use TerrainProfileTrait;
    use DifficultyTrait;
    use AliasTrait;

    public function __construct() {
        $this->trails = new ArrayCollection();
    }

    /**
     * Get trail detail
     *
     * @returnobject
     */
    public function getDetail(): object
    {
        $trailIds = array_map(function (Trail $trail) {
            return $trail->getId();
        }, $this->getTrails()->getValues());

        return (object) [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'difficulty' => $this->getDifficulty(),
            'location' => $this->getLocation()->getId(),
            'profile' => $this->getProfile(),
            'trails' => $trailIds,
            'alias' => $this->getAlias()
        ];
    }

    /**
     * @return object
     */
    public function getRelated(): object
    {
        return (object) [
            'location' => [$this->getLocation()],
            'trail' => $this->getTrails()->getValues()
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
     * @ManyToOne(targetEntity="Location")
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
