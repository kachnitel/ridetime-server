<?php
namespace RideTimeServer\Entities;

use RideTimeServer\Entities\Traits\LocationTrait;
use RideTimeServer\Entities\Traits\TerrainProfileTrait;

/**
 * @Entity(repositoryClass="RideTimeServer\API\Repositories\TrailRepository")
 * @Table(name="trail")
 */
class Trail extends PrimaryEntity implements PrimaryEntityInterface
{
    use LocationTrait;
    use TerrainProfileTrait;

    const SCALAR_FIELDS = [
        'id',
        'title',
        'description',
        'difficulty'
    ];

    /**
     * @deprecated ? May not be used outside tests
     *
     * @param object $data
     * @param Location $location
     * @return Trail
     */
    public static function create(object $data, Location $location): Trail
    {
        $trail = new static;

        $trail->applyProperties($data);
        $trail->setLocation($location);
        // FIXME: missing profile - remove method

        return $trail;
    }

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
            'profile' => $this->getProfile()
        ];
    }

    public function getRelated(): object
    {
        return (object) [
            'location' => [$this->getLocation()->getDetail()]
        ];
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
     * @Column(type="integer")
     *
     * @var int
     */
    private $difficulty;

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
     * Get the value of difficulty
     *
     * @return int
     */
    public function getDifficulty()
    {
        return $this->difficulty;
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
