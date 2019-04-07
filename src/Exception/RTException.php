<?php
namespace RideTimeServer\Exception;

class RTException extends \Exception
{
    /**
     * @var []
     */
    protected $data;

    /**
     * Get the value of data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the value of data
     *
     * @return self
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
}