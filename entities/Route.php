<?php
namespace RideTimeServer\Entities;

use RideTimeServer\Entities\Traits\LocationTrait;
use RideTimeServer\Entities\Traits\TerrainProfileTrait;
use RideTimeServer\Entities\Traits\TrailsTrait;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\RouteRepository")
 * @Table(name="route")
 */
class Route extends PrimaryEntity implements PrimaryEntityInterface
{
    use LocationTrait;
    use TrailsTrait;
    use TerrainProfileTrait;

    const SCALAR_FIELDS = [
        'id',
        'title',
        'description',
        // 'cover_photo'
    ];

    /**
     * Get trail detail
     *
     * @returnobject
     */
    public function getDetail(): object
    {
        $trailIds = array_map(function(Trail $trail) {
            return $trail->getId();
        }, $this->getTrails()->getValues());

        return (object) [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'location' => $this->getLocation()->getId(),
            'profile' => $this->getProfile(),
            'trails' => $trailIds
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'location' => [$this->getLocation()->getDetail()],
            // 'trail' => ..
        ];
    }

    /**
     * Applies self::SCALAR_FIELDS listed properties
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
     * @Id
     * @Column(type="integer", unique=true)
     *
     * @var int
     */
    private $id;

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
     * @ManyToOne(targetEntity="Location", inversedBy="routes")
     *
     * @var Location
     */
    private $location;

    /**
     * @param integer $id
     * @return self
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

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
