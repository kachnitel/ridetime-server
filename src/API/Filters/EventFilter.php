<?php
namespace RideTimeServer\API\Filters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use RideTimeServer\Entities\Location;

class EventFilter
{
    const FILTER_METHODS = [
        'location',
        'difficulty',
        'dateStart',
        'dateEnd'
    ];

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Criteria $criteria = null) {
        $this->entityManager = $entityManager;
        $this->criteria = $criteria ?? Criteria::create();
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function apply(array $filters)
    {
        foreach (static::FILTER_METHODS as $filterMethod) {
            if (isset($filters[$filterMethod])) {
                $this->{$filterMethod}($filters[$filterMethod]);
            }
        }
    }

    /**
     * @param [string|int] $date Date string or unix timestamp
     * @return \DateTime
     */
    protected function getDateTimeObject($date): \DateTime
    {
        return is_numeric($date)
            ? (new \DateTime())->setTimestamp($date)
            : new \DateTime($date);
    }

    /**
     * @param int[] $locationIDs
     * @return void
     */
    public function location(array $locationIDs)
    {
        $locations = $this->entityManager->getRepository(Location::class)
            ->findBy(['id' => $locationIDs]);
        $this->criteria->andWhere(Criteria::expr()->in('location', $locations));
    }

    /**
     * @param int[] $difficulties
     * @return void
     */
    public function difficulty(array $difficulties)
    {
        $values = array_map('intval', $difficulties);
        $this->criteria->andWhere(Criteria::expr()->in('difficulty', $values));
    }

    /**
     * @param string|int $datetime
     * @return void
     */
    public function dateStart($datetime)
    {
        $this->criteria->andWhere(
            Criteria::expr()->gte('date', $this->getDateTimeObject($datetime))
        );
    }

    /**
     * @param string|int $datetime
     * @return void
     */
    public function dateEnd($datetime)
    {
        $this->criteria->andWhere(
            Criteria::expr()->lte('date', $this->getDateTimeObject($datetime))
        );
    }
}
