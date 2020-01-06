<?php
namespace RideTimeServer\Tests\API\Filters;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use PHPUnit\Framework\MockObject\MockObject;
use RideTimeServer\API\Filters\EventFilter;
use RideTimeServer\Tests\API\APITestCase;

class EventFilterTest extends APITestCase
{
    public function testApply()
    {
        $filter = new EventFilter($this->entityManager);

        $timestamp = time();
        $filters = [
            'location' => [1],
            'difficulty' => [1],
            'dateStart' => $timestamp,
            'dateEnd' => $timestamp + 1
        ];
        $filter->apply($filters);

        /** @var CompositeExpression $expression */
        $expression = null;
        /** @var ExpressionVisitor|MockObject $visitor */
        $visitor = $this->getMockForAbstractClass(ExpressionVisitor::class);
        $visitor->expects($this->exactly(1))
            ->method('walkCompositeExpression')
            ->will($this->returnCallback(function () use (&$expression) {
                $expression = func_get_arg(0);
             }));

        $filter->getCriteria()->getWhereExpression()->visit($visitor);
        $comparisons = $this->getComparisonsFromCompositeExpr($expression);
        $this->assertContainsOnlyInstancesOf(Comparison::class, $comparisons);
        $this->assertCount(4, $comparisons);
        $this->assertEquals( // No duplicates => 4 different comparisons
            $comparisons,
            array_unique($comparisons, SORT_REGULAR)
        );
    }

    /**
     * @param CompositeExpression $expression
     * @param array $comparisons
     * @return Comparison[]
     */
    protected function getComparisonsFromCompositeExpr(CompositeExpression $expression, array $comparisons = []): array
    {
        $this->assertEquals('AND', $expression->getType());
        foreach ($expression->getExpressionList() as $expr) {
            if ($expr instanceof CompositeExpression) {
                $comparisons = $this->getComparisonsFromCompositeExpr($expr, $comparisons);
                continue;
            }
            $comparisons[] = $expr;
        }
        return $comparisons;
    }

    public function testLocation()
    {
        $locations = [
            $this->generateLocation(1),
            $this->generateLocation(3)
        ];
        $this->entityManager->flush();
        $filter = new EventFilter($this->entityManager);
        $filter->location([1, 3]);

        $comparison = $this->getComparisonFromCriteria($filter->getCriteria());

        $this->assertEquals('location', $comparison->getField());
        $this->assertEquals('IN', $comparison->getOperator());
        $this->assertEquals($locations, $comparison->getValue()->getValue());
    }

    public function testDifficulty()
    {
        $filter = new EventFilter($this->entityManager);
        $filter->difficulty([1, 2]);

        $comparison = $this->getComparisonFromCriteria($filter->getCriteria());

        $this->assertEquals('difficulty', $comparison->getField());
        $this->assertEquals('IN', $comparison->getOperator());
        $this->assertEquals([1, 2], $comparison->getValue()->getValue());
    }

    public function testDateStart()
    {
        $filter = new EventFilter($this->entityManager);
        $dt = new \DateTime('@' . time());
        $filter->dateStart($dt->getTimestamp());

        $comparison = $this->getComparisonFromCriteria($filter->getCriteria());

        $this->assertEquals('date', $comparison->getField());
        $this->assertEquals('>=', $comparison->getOperator());
        $this->assertEquals($dt, $comparison->getValue()->getValue());
    }

    public function testDateEnd()
    {
        $filter = new EventFilter($this->entityManager);
        $dt = new DateTime('@' . time());
        $filter->dateEnd($dt->getTimestamp());

        $comparison = $this->getComparisonFromCriteria($filter->getCriteria());

        $this->assertEquals('date', $comparison->getField());
        $this->assertEquals('<=', $comparison->getOperator());
        $this->assertEquals($dt, $comparison->getValue()->getValue());
    }

    protected function getComparisonFromCriteria(Criteria $criteria): Comparison
    {

        /** @var Comparison $comparison */
        $comparison = null;
        /** @var ExpressionVisitor|MockObject $visitor */
        $visitor = $this->getMockForAbstractClass(ExpressionVisitor::class);
        $visitor->expects($this->exactly(1))
            ->method('walkComparison')
            ->will($this->returnCallback(function () use (&$comparison) {
                $comparison = func_get_arg(0);
             }));

        $criteria->getWhereExpression()->visit($visitor);

        return $comparison;
    }
}
