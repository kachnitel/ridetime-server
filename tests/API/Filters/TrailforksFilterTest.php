<?php
namespace RideTimeServer\Tests\API\Filters;

use PHPUnit\Framework\TestCase;
use RideTimeServer\API\Filters\TrailforksFilter;

class TrailforksFilterTest extends TestCase
{
    public function testGetTrailforksFilter()
    {
        $filters = [
            'difficulty' => [1, 2],
            'activitytype' => 1,
            'search' => 'txt'
        ];

        $filter = new TrailforksFilter($filters);
        $result = $filter->getTrailforksFilter();

        $this->assertEquals('difficulty::1,2;activitytype::1;search::txt;', $result);
    }
}
