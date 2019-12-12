<?php
namespace RideTimeServer\API\Filters;

use RideTimeServer\Exception\RTException;

class TrailforksFilter
{
    /**
     * @var array
     */
    protected $filters = [];

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function getTrailforksFilter(): string
    {
        $result = '';
        foreach ($this->filters as $key => $value) {
            $result .= $this->getFilterItem($key, $value);
        }
        return rtrim($result, ';');
    }

    /**
     * @param string $key
     * @param scalar|array $value
     * @return string
     */
    protected function getFilterItem(string $key, $value): string
    {
        if (is_array($value)) {
            $value = join(',', $value);
        }
        return $key . '::' . $value . ';';
    }
}
